<?php
namespace App\Services;

use App\Models\MaintenanceTask;
use App\Repositories\MaintenanceRepository;

class MaintenanceService
{
    private MaintenanceRepository $repo;

    public function __construct()
    {
        $this->repo = new MaintenanceRepository();
    }

    public function dashboard(?int $userId = null): array
    {
        $this->repo->refreshOverdue();

        return [
            'metrics' => $this->repo->metrics(),
            'statusCounts' => $this->repo->statusCounts(),
            'typeCounts' => $this->repo->typeCounts(),
            'upcoming' => $this->repo->upcoming(),
            'atRisk' => $this->repo->atRisk(),
            'queue' => $this->repo->queue($userId, ['scope' => 'team']),
            'schedules' => $this->repo->schedules(['active' => 1, 'due_within_days' => 30]),
        ];
    }

    public function workOrders(array $filters = []): array
    {
        $this->repo->refreshOverdue();
        return $this->repo->getAll($filters);
    }

    public function queue(?int $userId = null, array $filters = []): array
    {
        $this->repo->refreshOverdue();
        return $this->repo->queue($userId, $filters);
    }

    public function history(array $filters = []): array
    {
        return $this->repo->history($filters);
    }

    public function detail(int $id): ?array
    {
        $task = $this->repo->findById($id);
        if (!$task) {
            return null;
        }

        return [
            'task' => $task,
            'logs' => $this->repo->logs($id),
            'checklist' => MaintenanceTask::decodeChecklist($task->checklist_json ?? null),
        ];
    }

    public function formData(): array
    {
        return [
            'assets' => $this->repo->assets(),
            'technicians' => $this->repo->technicians(),
            'departments' => $this->repo->departments(),
            'schedules' => $this->repo->schedules(['active' => 1]),
        ];
    }

    public function create(array $input, int $actorId): array
    {
        $validation = $this->validateTask($input);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->taskPayload($input);
        $data['work_order_number'] = $this->repo->generateWorkOrderNumber();
        $data['requested_by'] = $actorId;
        $data['status'] = $this->validStatus($input['status'] ?? 'scheduled');

        $this->repo->beginTransaction();
        try {
            $scheduleId = null;
            if (!empty($input['is_recurring']) && $data['asset_id'] && $data['type'] === 'preventive') {
                $scheduleId = $this->createScheduleRecord($input, $data);
                $data['schedule_id'] = $scheduleId;
            }

            $id = $this->repo->createTask($data);

            if ($scheduleId) {
                $this->repo->updateSchedule($scheduleId, ['last_generated_task_id' => $id]);
            }

            $this->repo->addLog([
                'task_id' => $id,
                'user_id' => $actorId,
                'action' => 'scheduled',
                'status_to' => $data['status'],
                'notes' => 'Work order scheduled',
            ]);

            if ($data['asset_id']) {
                $this->repo->addAssetHistory($data['asset_id'], $actorId, 'maintenance_scheduled', 'Work order ' . $data['work_order_number'] . ' scheduled: ' . $data['title']);
            }

            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to create work order: ' . $e->getMessage()];
        }

        $this->notifyAssigned($id);
        return ['success' => true, 'id' => $id, 'message' => 'Maintenance work order scheduled.'];
    }

    public function update(int $id, array $input, int $actorId): array
    {
        $task = $this->repo->findById($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Work order not found.'];
        }

        $validation = $this->validateTask($input, true);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->taskPayload($input);
        $data['status'] = $this->validStatus($input['status'] ?? $task->status);

        $this->repo->beginTransaction();
        try {
            $this->repo->updateTask($id, $data);
            $this->repo->addLog([
                'task_id' => $id,
                'user_id' => $actorId,
                'action' => 'updated',
                'status_from' => $task->status,
                'status_to' => $data['status'],
                'notes' => 'Work order parameters updated',
            ]);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to update work order: ' . $e->getMessage()];
        }

        $this->notifyAssigned($id);
        return ['success' => true, 'id' => $id, 'message' => 'Maintenance work order updated.'];
    }

    public function start(int $id, int $actorId, string $notes = ''): array
    {
        $task = $this->repo->findById($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Work order not found.'];
        }
        if (in_array($task->status, ['completed', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'Closed work orders cannot be started.'];
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->updateTask($id, ['status' => 'in_progress']);
            $this->repo->addLog([
                'task_id' => $id,
                'user_id' => $actorId,
                'action' => 'started',
                'status_from' => $task->status,
                'status_to' => 'in_progress',
                'notes' => $notes ?: 'Technician started work',
            ]);

            if ($task->asset_id) {
                $this->repo->updateAssetMaintenanceState((int)$task->asset_id, ['status' => 'maintenance']);
                $this->repo->addAssetHistory((int)$task->asset_id, $actorId, 'maintenance_started', 'Work started on ' . MaintenanceTask::workOrderLabel($task));
            }

            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to start work order: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Work order moved to in progress.'];
    }

    public function complete(int $id, array $input, int $actorId): array
    {
        $task = $this->repo->findById($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Work order not found.'];
        }
        if ($task->status === 'completed') {
            return ['success' => false, 'message' => 'Work order is already completed.'];
        }

        if (trim($input['notes'] ?? '') === '' || (float)($input['actual_hours'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Actual hours and completion notes are required.'];
        }

        $completedDate = date('Y-m-d');
        $hours = (float)$input['actual_hours'];
        $cost = isset($input['cost']) && $input['cost'] !== '' ? (float)$input['cost'] : 0.00;
        $parts = trim($input['parts_used'] ?? '');
        $notes = trim($input['notes']);

        $this->repo->beginTransaction();
        try {
            $nextDue = null;
            if ($task->schedule_id) {
                $schedule = $this->repo->findSchedule((int)$task->schedule_id);
                if ($schedule) {
                    $nextDue = MaintenanceTask::nextDueDate($schedule->frequency, $completedDate);
                    $this->repo->updateSchedule((int)$schedule->id, [
                        'last_performed' => $completedDate,
                        'next_due' => $nextDue,
                    ]);
                }
            }

            $this->repo->updateTask($id, [
                'status' => 'completed',
                'completed_date' => $completedDate,
                'completed_by' => $actorId,
                'actual_hours' => $hours,
                'cost' => $cost,
                'downtime_minutes' => (int)($input['downtime_minutes'] ?? 0),
                'failure_code' => trim($input['failure_code'] ?? ''),
                'notes' => $notes,
            ]);

            $this->repo->addLog([
                'task_id' => $id,
                'user_id' => $actorId,
                'action' => 'completed',
                'status_from' => $task->status,
                'status_to' => 'completed',
                'notes' => $notes,
                'parts_used' => $parts,
                'labor_hours' => $hours,
                'cost' => $cost,
            ]);

            if ($task->asset_id) {
                $assetNextDue = $nextDue ?: date('Y-m-d', strtotime('+3 months'));
                $this->repo->updateAssetMaintenanceState((int)$task->asset_id, [
                    'last_maintenance_date' => $completedDate,
                    'next_maintenance_date' => $assetNextDue,
                    'status' => 'active',
                ]);
                $this->repo->addAssetHistory((int)$task->asset_id, $actorId, 'maintenance_completed', 'Completed ' . MaintenanceTask::workOrderLabel($task) . '. Cost: $' . number_format($cost, 2));
            }

            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to complete work order: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Work order completed and service history updated.'];
    }

    public function cancel(int $id, int $actorId, string $reason = ''): array
    {
        $task = $this->repo->findById($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Work order not found.'];
        }
        if ($task->status === 'completed') {
            return ['success' => false, 'message' => 'Completed work orders cannot be cancelled.'];
        }

        $this->repo->beginTransaction();
        try {
            $this->repo->updateTask($id, ['status' => 'cancelled']);
            $this->repo->addLog([
                'task_id' => $id,
                'user_id' => $actorId,
                'action' => 'cancelled',
                'status_from' => $task->status,
                'status_to' => 'cancelled',
                'notes' => $reason ?: 'Work order cancelled',
            ]);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to cancel work order: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Work order cancelled.'];
    }

    public function createSchedule(array $input, int $actorId): array
    {
        if (empty($input['asset_id']) || trim($input['title'] ?? '') === '' || empty($input['frequency'])) {
            return ['success' => false, 'message' => 'Asset, title, and frequency are required for a preventive schedule.'];
        }

        try {
            $id = $this->repo->createSchedule([
                'asset_id' => (int)$input['asset_id'],
                'department_id' => !empty($input['department_id']) ? (int)$input['department_id'] : null,
                'title' => trim($input['title']),
                'description' => trim($input['description'] ?? ''),
                'frequency' => $this->validFrequency($input['frequency']),
                'priority' => $this->validPriority($input['priority'] ?? 'medium'),
                'estimated_hours' => $this->decimalOrNull($input['estimated_hours'] ?? null),
                'lead_time_days' => (int)($input['lead_time_days'] ?? 7),
                'next_due' => !empty($input['next_due']) ? $input['next_due'] : date('Y-m-d'),
                'assigned_to' => !empty($input['assigned_to']) ? (int)$input['assigned_to'] : null,
                'checklist_json' => MaintenanceTask::checklistFromText(trim($input['checklist'] ?? '')),
                'is_active' => isset($input['is_active']) ? (int)(bool)$input['is_active'] : 1,
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create preventive schedule: ' . $e->getMessage()];
        }

        return ['success' => true, 'id' => $id, 'message' => 'Preventive maintenance schedule created.'];
    }

    public function generateFromSchedule(int $scheduleId, int $actorId): array
    {
        $schedule = $this->repo->findSchedule($scheduleId);
        if (!$schedule || !(int)$schedule->is_active) {
            return ['success' => false, 'message' => 'Active preventive schedule not found.'];
        }

        $open = $this->repo->openTaskForSchedule($scheduleId);
        if ($open) {
            return ['success' => true, 'id' => (int)$open->id, 'message' => 'An open work order already exists for this schedule.'];
        }

        $scheduledDate = date('Y-m-d');
        $dueDate = $schedule->next_due ?: MaintenanceTask::nextDueDate($schedule->frequency);

        $this->repo->beginTransaction();
        try {
            $id = $this->repo->createTask([
                'work_order_number' => $this->repo->generateWorkOrderNumber(),
                'title' => $schedule->title,
                'description' => $schedule->description,
                'asset_id' => $schedule->asset_id,
                'schedule_id' => $schedule->id,
                'type' => 'preventive',
                'priority' => $schedule->priority,
                'status' => 'scheduled',
                'assigned_to' => $schedule->assigned_to,
                'department_id' => $schedule->department_id,
                'scheduled_date' => $scheduledDate,
                'due_date' => $dueDate,
                'estimated_hours' => $schedule->estimated_hours,
                'checklist_json' => $schedule->checklist_json,
                'requested_by' => $actorId,
            ]);
            $this->repo->updateSchedule($scheduleId, ['last_generated_task_id' => $id]);
            $this->repo->addLog([
                'task_id' => $id,
                'user_id' => $actorId,
                'action' => 'generated_from_schedule',
                'status_to' => 'scheduled',
                'notes' => 'Preventive work order generated from recurring schedule',
            ]);
            $this->repo->addAssetHistory((int)$schedule->asset_id, $actorId, 'maintenance_scheduled', 'Preventive work order generated: ' . $schedule->title);
            $this->repo->commit();
        } catch (\Exception $e) {
            $this->repo->rollback();
            return ['success' => false, 'message' => 'Failed to generate work order: ' . $e->getMessage()];
        }

        $this->notifyAssigned($id);
        return ['success' => true, 'id' => $id, 'message' => 'Preventive work order generated.'];
    }

    public function schedules(array $filters = []): array
    {
        return $this->repo->schedules($filters);
    }

    public function calendarEvents(?string $start = null, ?string $end = null, string $baseUrl = ''): array
    {
        $colors = [
            'completed' => '#198754',
            'overdue' => '#dc3545',
            'in_progress' => '#f59e0b',
            'scheduled' => '#0d6efd',
            'cancelled' => '#6c757d',
        ];

        return array_map(function (object $task) use ($colors, $baseUrl): array {
            $label = MaintenanceTask::workOrderLabel($task);
            $color = $colors[$task->status] ?? '#0d6efd';
            if ($task->priority === 'critical' && $task->status !== 'completed') {
                $color = '#dc3545';
            }

            return [
                'id' => (int)$task->id,
                'title' => $label . ' - ' . $task->title,
                'start' => $task->scheduled_date,
                'end' => $task->due_date ? date('Y-m-d', strtotime($task->due_date . ' +1 day')) : null,
                'url' => rtrim($baseUrl, '/') . '/maintenance/' . $task->id,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'allDay' => true,
                'extendedProps' => [
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'type' => $task->type,
                    'asset' => $task->asset_tag,
                ],
            ];
        }, $this->repo->calendarEvents($start, $end));
    }

    private function createScheduleRecord(array $input, array $taskData): int
    {
        return $this->repo->createSchedule([
            'asset_id' => $taskData['asset_id'],
            'department_id' => $taskData['department_id'],
            'title' => $taskData['title'],
            'description' => $taskData['description'],
            'frequency' => $this->validFrequency($input['frequency'] ?? 'quarterly'),
            'priority' => $taskData['priority'],
            'estimated_hours' => $taskData['estimated_hours'],
            'lead_time_days' => (int)($input['lead_time_days'] ?? 7),
            'next_due' => $taskData['due_date'],
            'assigned_to' => $taskData['assigned_to'],
            'checklist_json' => $taskData['checklist_json'],
            'is_active' => 1,
        ]);
    }

    private function notifyAssigned(int $taskId): void
    {
        $task = $this->repo->findById($taskId);
        if (!$task || empty($task->assigned_to)) {
            return;
        }

        try {
            $this->repo->notifyUser(
                (int)$task->assigned_to,
                'Maintenance Work Order Assigned',
                MaintenanceTask::workOrderLabel($task) . ': ' . $task->title,
                '/maintenance/' . $task->id
            );
        } catch (\Exception $e) {
            // Notifications should not block maintenance workflow actions.
        }
    }

    private function validateTask(array $input, bool $updating = false): array
    {
        foreach (['title', 'type', 'priority', 'scheduled_date', 'due_date'] as $field) {
            if (trim((string)($input[$field] ?? '')) === '') {
                return ['success' => false, 'message' => ucwords(str_replace('_', ' ', $field)) . ' is required.'];
            }
        }

        if (strtotime($input['due_date']) < strtotime($input['scheduled_date'])) {
            return ['success' => false, 'message' => 'Due date cannot be before scheduled date.'];
        }

        if ($updating && !in_array($input['status'] ?? 'scheduled', ['scheduled', 'in_progress', 'completed', 'overdue', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'Maintenance status is not valid.'];
        }

        return ['success' => true];
    }

    private function taskPayload(array $input): array
    {
        return [
            'title' => trim($input['title']),
            'description' => trim($input['description'] ?? ''),
            'asset_id' => !empty($input['asset_id']) ? (int)$input['asset_id'] : null,
            'source_ticket_id' => !empty($input['source_ticket_id']) ? (int)$input['source_ticket_id'] : null,
            'type' => $this->validType($input['type'] ?? 'preventive'),
            'priority' => $this->validPriority($input['priority'] ?? 'medium'),
            'assigned_to' => !empty($input['assigned_to']) ? (int)$input['assigned_to'] : null,
            'department_id' => !empty($input['department_id']) ? (int)$input['department_id'] : null,
            'scheduled_date' => $input['scheduled_date'],
            'due_date' => $input['due_date'],
            'estimated_hours' => $this->decimalOrNull($input['estimated_hours'] ?? null),
            'downtime_minutes' => (int)($input['downtime_minutes'] ?? 0),
            'failure_code' => trim($input['failure_code'] ?? ''),
            'checklist_json' => MaintenanceTask::checklistFromText(trim($input['checklist'] ?? '')),
            'notes' => trim($input['notes'] ?? ''),
        ];
    }

    private function decimalOrNull(mixed $value): ?float
    {
        return $value !== null && $value !== '' ? (float)$value : null;
    }

    private function validType(string $type): string
    {
        return in_array($type, ['preventive', 'corrective', 'emergency', 'inspection'], true) ? $type : 'preventive';
    }

    private function validPriority(string $priority): string
    {
        return in_array($priority, ['critical', 'high', 'medium', 'low'], true) ? $priority : 'medium';
    }

    private function validStatus(string $status): string
    {
        return in_array($status, ['scheduled', 'in_progress', 'completed', 'overdue', 'cancelled'], true) ? $status : 'scheduled';
    }

    private function validFrequency(string $frequency): string
    {
        return in_array($frequency, ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'semi_annual', 'annual'], true) ? $frequency : 'quarterly';
    }
}
