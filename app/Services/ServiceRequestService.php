<?php
namespace App\Services;

use App\Repositories\ServiceRequestRepository;
use App\Helpers\Session;

class ServiceRequestService
{
    private ServiceRequestRepository $repo;

    public function __construct()
    {
        $this->repo = new ServiceRequestRepository();
    }

    public function catalog(): array
    {
        return $this->repo->getCatalogItems();
    }

    public function getCatalogItem(string $type): ?object
    {
        return $this->repo->findCatalogItem($this->normalizeType($type));
    }

    public function getRequests(string $role, int $userId, array $filters = []): array
    {
        if (!empty($filters['type'])) {
            $filters['type'] = $this->normalizeType($filters['type']);
        }
        return $this->repo->getRequests($role, $userId, $filters);
    }

    public function getRequestBundle(int $requestId): ?array
    {
        $request = $this->repo->findRequest($requestId);
        if (!$request) {
            return null;
        }

        return [
            'request' => $request,
            'approvals' => $this->repo->getApprovals($requestId),
            'fieldValues' => $this->repo->getFieldValues($requestId),
            'activity' => $this->repo->getActivity($requestId),
            'catalogItem' => $this->repo->findCatalogItem($request->type),
        ];
    }

    public function create(array $input, int $userId): array
    {
        $type = $this->normalizeType($input['type'] ?? '');
        $item = $this->repo->findCatalogItem($type);
        if (!$item) {
            return ['success' => false, 'message' => 'Unknown service request type.'];
        }

        $title = trim($input['title'] ?? '');
        $priority = $input['priority'] ?? $item->default_priority;
        $departmentId = !empty($input['department_id']) ? (int)$input['department_id'] : null;
        $description = trim($input['description'] ?? '');
        $fields = is_array($input['spec'] ?? null) ? $input['spec'] : [];

        if ($title === '') {
            return ['success' => false, 'message' => 'Request title is required.'];
        }

        if (!in_array($priority, ['critical', 'high', 'medium', 'low'], true)) {
            return ['success' => false, 'message' => 'Priority is not valid.'];
        }

        $validation = $this->validateFields($item->schema, $fields);
        if (!$validation['success']) {
            return $validation;
        }

        $approverId = $this->repo->resolveApprover($departmentId, $item->approval_mode);
        $status = $approverId ? 'pending_approval' : 'approved';
        $requestNumber = $this->repo->generateRequestNumber();

        $this->repo->beginTransaction();
        try {
            $requestId = $this->repo->createRequest([
                'request_number' => $requestNumber,
                'type' => $type,
                'title' => $title,
                'description' => $description,
                'requester_id' => $userId,
                'department_id' => $departmentId,
                'priority' => $priority,
                'status' => $status,
                'approved_by' => $approverId ? null : $userId,
                'approved_at' => $approverId ? null : date('Y-m-d H:i:s'),
            ]);

            $this->storeFieldValues($requestId, $item->schema, $fields);
            $this->repo->addActivity($requestId, $userId, 'submitted', 'Request submitted', "Submitted {$item->name}.");

            if ($approverId) {
                $this->repo->createApproval($requestId, $approverId);
                $this->repo->addActivity($requestId, null, 'approval_requested', 'Approval requested', 'A reviewer was assigned for authorization.', ['approver_id' => $approverId]);
            } else {
                $this->createFulfillment($requestId, $userId, 'Auto-approved by workflow.');
            }

            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to submit request: ' . $e->getMessage()];
        }

        if ($approverId) {
            $this->repo->notify(
                $approverId,
                'approval_required',
                'Service Request Approval Required',
                "Request {$requestNumber}: {$title} requires your approval.",
                '/service-requests/' . $requestId
            );
        }

        return ['success' => true, 'id' => $requestId, 'request_number' => $requestNumber];
    }

    public function approve(int $requestId, int $userId, string $role, array $permissions, string $comments = ''): array
    {
        $request = $this->repo->findRequest($requestId);
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.'];
        }

        if ($request->status !== 'pending_approval') {
            return ['success' => false, 'message' => 'Only pending requests can be approved.'];
        }

        $approval = $this->repo->findPendingApproval($requestId, $userId);
        if (!$approval && !$this->canOverrideApproval($role, $permissions)) {
            return ['success' => false, 'message' => 'You are not authorized to approve this request.'];
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->updateRequest($requestId, [
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);

            if ($approval) {
                $this->repo->updateApproval($approval->id, 'approved', $comments);
            } else {
                $approvalId = $this->repo->createApproval($requestId, $userId);
                $this->repo->updateApproval($approvalId, 'approved', trim($comments . ' (Overridden by authorized user)'));
            }

            $this->repo->addActivity($requestId, $userId, 'approved', 'Request approved', $comments);
            $ticketId = $this->createFulfillment($requestId, $userId, $comments);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to approve request: ' . $e->getMessage()];
        }

        $this->repo->notify(
            $request->requester_id,
            'ticket_updated',
            'Service Request Approved',
            "Your request {$request->request_number} was approved and fulfillment ticket #{$ticketId} was opened.",
            '/service-requests/' . $requestId
        );

        $this->notifyFulfillmentTeam($requestId, $request->request_number, $request->title);

        return ['success' => true, 'message' => 'Request approved and fulfillment task generated.'];
    }

    public function reject(int $requestId, int $userId, string $role, array $permissions, string $comments = ''): array
    {
        $request = $this->repo->findRequest($requestId);
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.'];
        }

        if ($request->status !== 'pending_approval') {
            return ['success' => false, 'message' => 'Only pending requests can be rejected.'];
        }

        $approval = $this->repo->findPendingApproval($requestId, $userId);
        if (!$approval && !$this->canOverrideApproval($role, $permissions)) {
            return ['success' => false, 'message' => 'You are not authorized to reject this request.'];
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->updateRequest($requestId, ['status' => 'rejected']);

            if ($approval) {
                $this->repo->updateApproval($approval->id, 'rejected', $comments);
            } else {
                $approvalId = $this->repo->createApproval($requestId, $userId);
                $this->repo->updateApproval($approvalId, 'rejected', trim($comments . ' (Overridden by authorized user)'));
            }

            $this->repo->addActivity($requestId, $userId, 'rejected', 'Request rejected', $comments);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to reject request: ' . $e->getMessage()];
        }

        $this->repo->notify(
            $request->requester_id,
            'ticket_updated',
            'Service Request Rejected',
            "Your request {$request->request_number} was rejected.",
            '/service-requests/' . $requestId
        );

        return ['success' => true, 'message' => 'Request rejected.'];
    }

    public function startFulfillment(int $requestId, int $userId, string $role): array
    {
        if (!$this->canFulfill($role)) {
            return ['success' => false, 'message' => 'You are not authorized to start fulfillment.'];
        }

        $request = $this->repo->findRequest($requestId);
        if (!$request || !in_array($request->status, ['approved', 'fulfilling'], true)) {
            return ['success' => false, 'message' => 'Request is not ready for fulfillment.'];
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->updateRequest($requestId, ['status' => 'fulfilling']);
            $this->repo->updateFulfillmentTask($requestId, [
                'status' => 'in_progress',
                'assigned_to' => $userId,
                'started_at' => date('Y-m-d H:i:s'),
            ]);
            $this->repo->addActivity($requestId, $userId, 'fulfillment_started', 'Fulfillment started');
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to start fulfillment: ' . $e->getMessage()];
        }

        $this->repo->notify($request->requester_id, 'ticket_updated', 'Service Request In Progress', "Fulfillment has started for {$request->request_number}.", '/service-requests/' . $requestId);
        return ['success' => true, 'message' => 'Fulfillment started.'];
    }

    public function completeFulfillment(int $requestId, int $userId, string $role, string $notes = ''): array
    {
        if (!$this->canFulfill($role)) {
            return ['success' => false, 'message' => 'You are not authorized to complete fulfillment.'];
        }

        $request = $this->repo->findRequest($requestId);
        if (!$request || !in_array($request->status, ['approved', 'fulfilling'], true)) {
            return ['success' => false, 'message' => 'Request is not in a fulfillable state.'];
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->updateRequest($requestId, [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            $this->repo->updateFulfillmentTask($requestId, [
                'status' => 'completed',
                'assigned_to' => $request->assigned_to ?: $userId,
                'notes' => $notes,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            $this->repo->addActivity($requestId, $userId, 'completed', 'Request completed', $notes);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to complete request: ' . $e->getMessage()];
        }

        $this->repo->notify($request->requester_id, 'ticket_resolved', 'Service Request Completed', "Your request {$request->request_number} has been completed.", '/service-requests/' . $requestId);
        return ['success' => true, 'message' => 'Request completed.'];
    }

    public function cancel(int $requestId, int $userId, string $role, string $reason = ''): array
    {
        $request = $this->repo->findRequest($requestId);
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.'];
        }

        if ($request->requester_id !== $userId && !in_array($role, ['administrator', 'super_administrator'], true)) {
            return ['success' => false, 'message' => 'You are not authorized to cancel this request.'];
        }

        if (in_array($request->status, ['completed', 'rejected', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'This request cannot be cancelled.'];
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->updateRequest($requestId, ['status' => 'cancelled']);
            $this->repo->updateFulfillmentTask($requestId, ['status' => 'cancelled', 'notes' => $reason]);
            $this->repo->addActivity($requestId, $userId, 'cancelled', 'Request cancelled', $reason);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to cancel request: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Request cancelled.'];
    }

    public function normalizeType(string $type): string
    {
        return $type === 'email_setup' ? 'email_creation' : $type;
    }

    private function validateFields(array $schema, array $fields): array
    {
        foreach ($schema as $field) {
            $key = $field['key'];
            $value = trim((string)($fields[$key] ?? ''));

            if (!empty($field['required']) && $value === '') {
                return ['success' => false, 'message' => "{$field['label']} is required."];
            }

            if ($value !== '' && ($field['type'] ?? '') === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => "{$field['label']} must be a valid email address."];
            }

            if ($value !== '' && ($field['type'] ?? '') === 'number' && !is_numeric($value)) {
                return ['success' => false, 'message' => "{$field['label']} must be a number."];
            }

            if ($value !== '' && ($field['type'] ?? '') === 'select') {
                $allowed = array_column($field['options'] ?? [], 'value');
                if ($allowed && !in_array($value, $allowed, true)) {
                    return ['success' => false, 'message' => "{$field['label']} is not a valid option."];
                }
            }
        }

        return ['success' => true];
    }

    private function storeFieldValues(int $requestId, array $schema, array $fields): void
    {
        foreach ($schema as $index => $field) {
            $key = $field['key'];
            $value = $fields[$key] ?? '';
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $this->repo->insertFieldValue([
                'request_id' => $requestId,
                'field_key' => $key,
                'field_label' => $field['label'],
                'field_type' => $field['type'] ?? 'text',
                'field_value' => trim((string)$value),
                'sort_order' => $index,
            ]);
        }
    }

    private function createFulfillment(int $requestId, int $userId, string $notes = ''): int
    {
        $request = $this->repo->findRequest($requestId);
        $fields = $this->repo->getFieldValues($requestId);
        $item = $this->repo->findCatalogItem($request->type);
        $categoryId = $this->fulfillmentCategoryId($request->type, $item->fulfillment_category_id ?? null);

        $ticketNumber = $this->generateTicketNumber();
        $ticketId = $this->repo->createFulfillmentTicket([
            'ticket_number' => $ticketNumber,
            'title' => '[SERVICE] ' . $request->title,
            'description' => $this->buildTicketDescription($request, $fields, $notes),
            'category_id' => $categoryId,
            'priority' => $request->priority,
            'status' => 'new',
            'requester_id' => $request->requester_id,
            'department_id' => $request->department_id,
            'sla_due_at' => $this->slaDueAt($request->priority, (int)($item->sla_hours ?? 48)),
        ]);

        $this->repo->addTicketHistory($ticketId, $userId, 'created_from_service_request', $request->request_number);
        try {
            (new SlaMonitorService())->applySlaToTicket($ticketId);
        } catch (\Exception $e) {
            // SLA sync should not block service request fulfillment.
        }

        if (!$this->repo->findFulfillmentTask($requestId)) {
            $this->repo->createFulfillmentTask([
                'request_id' => $requestId,
                'ticket_id' => $ticketId,
                'status' => 'queued',
                'summary' => 'Fulfillment ticket ' . $ticketNumber . ' queued',
                'notes' => $notes,
            ]);
        }

        $this->repo->addActivity($requestId, $userId, 'fulfillment_created', 'Fulfillment ticket created', 'Ticket ' . $ticketNumber . ' is queued for the technical team.', ['ticket_id' => $ticketId]);
        return $ticketId;
    }

    private function buildTicketDescription(object $request, array $fields, string $notes): string
    {
        $lines = [
            "Fulfillment task for Service Request: {$request->request_number}",
            '',
            $request->description ?: 'No additional context provided.',
            '',
            'Request specifications:',
        ];

        foreach ($fields as $field) {
            $lines[] = "- {$field->field_label}: {$field->field_value}";
        }

        if ($notes !== '') {
            $lines[] = '';
            $lines[] = 'Approval notes: ' . $notes;
        }

        return implode("\n", $lines);
    }

    private function fulfillmentCategoryId(string $type, ?int $fallback): ?int
    {
        return match ($type) {
            'new_computer' => $this->repo->findTicketCategoryId(['Hardware'], $fallback),
            'software_install' => $this->repo->findTicketCategoryId(['Software'], $fallback),
            'email_creation', 'access_request' => $this->repo->findTicketCategoryId(['Access', 'Security'], $fallback),
            'network_access' => $this->repo->findTicketCategoryId(['Network'], $fallback),
            'equipment_request' => $this->repo->findTicketCategoryId(['Medical Equipment', 'Hardware'], $fallback),
            default => $fallback,
        };
    }

    private function generateTicketNumber(): string
    {
        $last = \App\Helpers\Database::getInstance()->fetchColumn("SELECT MAX(id) FROM tickets");
        return 'HEMS-' . str_pad(((int)$last) + 9400, 4, '0', STR_PAD_LEFT);
    }

    private function slaDueAt(string $priority, int $catalogHours): string
    {
        $minutes = $GLOBALS['appConfig']['sla_defaults'][$priority] ?? null;
        $seconds = $minutes ? ($minutes * 60) : ($catalogHours * 3600);
        return date('Y-m-d H:i:s', time() + $seconds);
    }

    private function canOverrideApproval(string $role, array $permissions): bool
    {
        return in_array($role, ['administrator', 'super_administrator'], true)
            || in_array('service_requests.approve', $permissions, true);
    }

    private function canFulfill(string $role): bool
    {
        return in_array($role, ['technician', 'biomedical_engineer', 'manager', 'administrator', 'super_administrator'], true);
    }

    private function notifyFulfillmentTeam(int $requestId, string $requestNumber, string $title): void
    {
        foreach ($this->repo->getFulfillmentUsers() as $user) {
            try {
                $this->repo->notify(
                    (int)$user->id,
                    'ticket_assigned',
                    'Service Fulfillment Queued',
                    "Service request {$requestNumber}: {$title} is ready for fulfillment.",
                    '/service-requests/' . $requestId
                );
            } catch (\Exception $e) {
                // Notification fan-out should not fail the workflow.
            }
        }
    }
}
