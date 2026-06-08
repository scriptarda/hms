<?php
namespace App\Services;

use App\Repositories\SlaRepository;

class SlaMonitorService
{
    private SlaRepository $repo;

    public function __construct()
    {
        $this->repo = new SlaRepository();
    }

    public function dashboard(): array
    {
        return [
            'metrics' => $this->repo->metrics(),
            'rules' => $this->repo->rules(),
            'atRiskTickets' => $this->repo->atRiskTickets(),
            'events' => $this->repo->recentEvents(),
        ];
    }

    public function rules(): array
    {
        return $this->repo->rules();
    }

    public function createRule(array $input): array
    {
        $payload = $this->rulePayload($input);
        if (!$payload['success']) {
            return $payload;
        }

        $id = $this->repo->createRule($payload['data']);
        return ['success' => true, 'id' => $id, 'message' => 'SLA rule created.'];
    }

    public function updateRule(int $id, array $input): array
    {
        if (!$this->repo->findRule($id)) {
            return ['success' => false, 'message' => 'SLA rule not found.'];
        }

        $payload = $this->rulePayload($input);
        if (!$payload['success']) {
            return $payload;
        }

        $this->repo->updateRule($id, $payload['data']);
        return ['success' => true, 'message' => 'SLA rule updated.'];
    }

    public function applySlaToTicket(int $ticketId): void
    {
        $ticket = $this->repo->ticket($ticketId);
        if (!$ticket) {
            return;
        }

        $rule = $this->repo->activeRuleForPriority($ticket->priority);
        if (!$rule) {
            return;
        }

        $createdAt = $ticket->created_at ?: date('Y-m-d H:i:s');
        $responseDue = $this->targetFromMinutes($createdAt, (int)$rule->response_time);
        $resolutionDue = $this->targetFromMinutes($createdAt, (int)$rule->resolution_time);

        $responseStatus = $this->responseStatus($ticket, $responseDue, $rule);
        $resolutionStatus = $this->resolutionStatus($resolutionDue, $createdAt, (int)$rule->resolution_time, (int)$rule->warning_threshold);

        $this->repo->updateTicket($ticketId, [
            'sla_rule_id' => (int)$rule->id,
            'response_due_at' => $responseDue,
            'response_sla_status' => $responseStatus,
            'resolution_due_at' => $resolutionDue,
            'resolution_sla_status' => $resolutionStatus,
            'sla_due_at' => $resolutionDue,
            'sla_status' => $this->worstStatus($responseStatus, $resolutionStatus),
            'last_sla_checked_at' => date('Y-m-d H:i:s'),
        ]);

        $this->repo->addEvent([
            'ticket_id' => $ticketId,
            'rule_id' => (int)$rule->id,
            'event_type' => 'recalculated',
            'old_status' => $ticket->sla_status ?? null,
            'new_status' => $this->worstStatus($responseStatus, $resolutionStatus),
            'escalation_level' => (int)($ticket->escalation_level ?? 0),
            'notes' => 'SLA targets calculated from ' . $rule->name,
        ]);
    }

    public function markResponded(int $ticketId): void
    {
        $ticket = $this->repo->ticket($ticketId);
        if (!$ticket || !empty($ticket->responded_at)) {
            return;
        }

        if (empty($ticket->response_due_at) || empty($ticket->resolution_due_at)) {
            $this->applySlaToTicket($ticketId);
            $ticket = $this->repo->ticket($ticketId);
            if (!$ticket) {
                return;
            }
        }

        $now = date('Y-m-d H:i:s');
        $responseStatus = strtotime($now) > strtotime($ticket->response_due_at) ? SLA_BREACHED : SLA_ON_TRACK;
        $resolutionStatus = $ticket->resolution_sla_status ?? SLA_ON_TRACK;

        $this->repo->updateTicket($ticketId, [
            'responded_at' => $now,
            'response_sla_status' => $responseStatus,
            'sla_status' => $this->worstStatus($responseStatus, $resolutionStatus),
            'last_sla_checked_at' => $now,
        ]);

        $this->repo->addEvent([
            'ticket_id' => $ticketId,
            'rule_id' => $ticket->rule_id ? (int)$ticket->rule_id : null,
            'event_type' => 'recalculated',
            'old_status' => $ticket->response_sla_status ?? null,
            'new_status' => $responseStatus,
            'escalation_level' => (int)($ticket->escalation_level ?? 0),
            'notes' => 'First response recorded.',
        ]);
    }

    public function run(int $limit = 500): array
    {
        $checked = 0;
        $warnings = 0;
        $breaches = 0;
        $escalations = 0;
        $updated = 0;

        foreach ($this->repo->openTicketsForMonitor($limit) as $ticket) {
            $checked++;
            $ticket = $this->ensureTargets($ticket);
            if (!$ticket) {
                continue;
            }

            $rule = $this->ruleFromTicket($ticket);
            if (!$rule) {
                continue;
            }

            $createdAt = $ticket->created_at ?: date('Y-m-d H:i:s');
            $responseStatus = $this->responseStatus($ticket, $ticket->response_due_at, $rule);
            $resolutionStatus = $this->resolutionStatus(
                $ticket->resolution_due_at ?: $ticket->sla_due_at,
                $createdAt,
                (int)$rule->resolution_time,
                (int)$rule->warning_threshold
            );
            $overallStatus = $this->worstStatus($responseStatus, $resolutionStatus);

            $updates = ['last_sla_checked_at' => date('Y-m-d H:i:s')];
            if ($responseStatus !== ($ticket->response_sla_status ?? SLA_ON_TRACK)) {
                $updates['response_sla_status'] = $responseStatus;
                $this->recordStatusEvent($ticket, $rule, 'response', $ticket->response_sla_status ?? SLA_ON_TRACK, $responseStatus);
                $responseStatus === SLA_BREACHED ? $breaches++ : $warnings++;
            }
            if ($resolutionStatus !== ($ticket->resolution_sla_status ?? SLA_ON_TRACK)) {
                $updates['resolution_sla_status'] = $resolutionStatus;
                $this->recordStatusEvent($ticket, $rule, 'resolution', $ticket->resolution_sla_status ?? SLA_ON_TRACK, $resolutionStatus);
                $resolutionStatus === SLA_BREACHED ? $breaches++ : $warnings++;
            }
            if ($overallStatus !== ($ticket->sla_status ?? SLA_ON_TRACK)) {
                $updates['sla_status'] = $overallStatus;
            }

            if ($this->shouldEscalate($ticket, $rule, $responseStatus, $resolutionStatus)) {
                $newLevel = (int)($ticket->escalation_level ?? 0) + 1;
                $updates['escalation_level'] = $newLevel;
                $updates['last_escalated_at'] = date('Y-m-d H:i:s');
                $this->recordEscalationEvent($ticket, $rule, $newLevel);
                $escalations++;
            }

            $this->repo->updateTicket((int)$ticket->id, $updates);
            if (count($updates) > 1) {
                $updated++;
            }
        }

        return [
            'success' => true,
            'checked' => $checked,
            'updated' => $updated,
            'warnings' => $warnings,
            'breaches' => $breaches,
            'escalations' => $escalations,
            'ran_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function ensureTargets(object $ticket): ?object
    {
        if (!empty($ticket->response_due_at) && !empty($ticket->resolution_due_at) && !empty($ticket->rule_id)) {
            return $ticket;
        }

        $this->applySlaToTicket((int)$ticket->id);
        return $this->repo->ticket((int)$ticket->id);
    }

    private function recordStatusEvent(object $ticket, object $rule, string $target, string $oldStatus, string $newStatus): void
    {
        if ($newStatus === SLA_ON_TRACK) {
            return;
        }

        $eventType = $target . '_' . ($newStatus === SLA_BREACHED ? 'breached' : 'warning');
        $title = $newStatus === SLA_BREACHED ? 'SLA Breached' : 'SLA Warning';
        $targetLabel = ucfirst($target);
        $message = "{$targetLabel} SLA {$newStatus} for ticket {$ticket->ticket_number}: {$ticket->title}";
        $type = $newStatus === SLA_BREACHED ? NOTIFY_SLA_BREACHED : NOTIFY_SLA_WARNING;

        $this->repo->addEvent([
            'ticket_id' => (int)$ticket->id,
            'rule_id' => (int)$rule->id,
            'event_type' => $eventType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'escalation_level' => (int)($ticket->escalation_level ?? 0),
            'notes' => $message,
        ]);

        $this->notifyStakeholders($ticket, $rule, $type, $title, $message);
    }

    private function recordEscalationEvent(object $ticket, object $rule, int $level): void
    {
        $message = "Ticket {$ticket->ticket_number} escalated to SLA level {$level}: {$ticket->title}";

        $this->repo->addEvent([
            'ticket_id' => (int)$ticket->id,
            'rule_id' => (int)$rule->id,
            'event_type' => 'escalated',
            'old_status' => $ticket->sla_status ?? SLA_BREACHED,
            'new_status' => SLA_BREACHED,
            'escalation_level' => $level,
            'notes' => $message,
        ]);

        $this->notifyStakeholders($ticket, $rule, NOTIFY_SLA_BREACHED, 'SLA Escalation', $message, true);
    }

    private function notifyStakeholders(object $ticket, object $rule, string $type, string $title, string $message, bool $escalationOnly = false): void
    {
        $roles = $escalationOnly ? '' : (string)($rule->notify_roles ?? 'technician,manager,administrator');
        $escalationRole = $escalationOnly ? (string)($rule->escalation_role ?? 'manager') : null;
        $users = $this->repo->usersForNotification(
            !empty($ticket->assigned_to) ? (int)$ticket->assigned_to : null,
            $roles,
            $escalationRole
        );

        foreach ($users as $userId) {
            try {
                $this->repo->notify((int)$userId, $type, $title, $message, '/tickets/' . $ticket->id);
            } catch (\Exception $e) {
                // Notification failures must not block the SLA monitor.
            }
        }
    }

    private function shouldEscalate(object $ticket, object $rule, string $responseStatus, string $resolutionStatus): bool
    {
        $escalationTime = (int)($rule->escalation_time ?? 0);
        if ($escalationTime <= 0 || ($responseStatus !== SLA_BREACHED && $resolutionStatus !== SLA_BREACHED)) {
            return false;
        }

        $breachedDueTimes = [];
        if ($responseStatus === SLA_BREACHED && !empty($ticket->response_due_at)) {
            $breachedDueTimes[] = strtotime($ticket->response_due_at);
        }
        if ($resolutionStatus === SLA_BREACHED && !empty($ticket->resolution_due_at)) {
            $breachedDueTimes[] = strtotime($ticket->resolution_due_at);
        }
        if (empty($breachedDueTimes)) {
            return false;
        }

        $base = !empty($ticket->last_escalated_at) ? strtotime($ticket->last_escalated_at) : min($breachedDueTimes);
        return time() >= ($base + ($escalationTime * 60));
    }

    private function responseStatus(object $ticket, ?string $responseDueAt, ?object $rule = null): string
    {
        if (!$responseDueAt) {
            return SLA_ON_TRACK;
        }
        if (!empty($ticket->responded_at)) {
            return strtotime($ticket->responded_at) > strtotime($responseDueAt) ? SLA_BREACHED : SLA_ON_TRACK;
        }

        $rule = $rule ?: $this->ruleFromTicket($ticket);
        $createdAt = $ticket->created_at ?: date('Y-m-d H:i:s');
        $warningAt = $this->warningAt($createdAt, (int)($rule->response_time ?? 60), (int)($rule->warning_threshold ?? 80));

        if (time() > strtotime($responseDueAt)) {
            return SLA_BREACHED;
        }
        if (time() >= strtotime($warningAt)) {
            return SLA_WARNING;
        }
        return SLA_ON_TRACK;
    }

    private function resolutionStatus(?string $dueAt, string $createdAt, int $resolutionMinutes, int $warningThreshold): string
    {
        if (!$dueAt) {
            return SLA_ON_TRACK;
        }

        $warningAt = $this->warningAt($createdAt, $resolutionMinutes, $warningThreshold);
        if (time() > strtotime($dueAt)) {
            return SLA_BREACHED;
        }
        if (time() >= strtotime($warningAt)) {
            return SLA_WARNING;
        }
        return SLA_ON_TRACK;
    }

    private function ruleFromTicket(object $ticket): ?object
    {
        if (!empty($ticket->rule_id)) {
            return (object)[
                'id' => (int)$ticket->rule_id,
                'name' => $ticket->rule_name,
                'response_time' => (int)$ticket->rule_response_time,
                'resolution_time' => (int)$ticket->rule_resolution_time,
                'escalation_time' => (int)($ticket->rule_escalation_time ?? 0),
                'warning_threshold' => (int)($ticket->rule_warning_threshold ?? 80),
                'notify_roles' => $ticket->rule_notify_roles ?? 'technician,manager,administrator',
                'escalation_role' => $ticket->rule_escalation_role ?? 'manager',
            ];
        }

        return $this->repo->activeRuleForPriority($ticket->priority);
    }

    private function targetFromMinutes(string $createdAt, int $minutes): string
    {
        return date('Y-m-d H:i:s', strtotime($createdAt) + ($minutes * 60));
    }

    private function warningAt(string $createdAt, int $minutes, int $threshold): string
    {
        $threshold = max(1, min(99, $threshold));
        return date('Y-m-d H:i:s', strtotime($createdAt) + (int)floor($minutes * 60 * ($threshold / 100)));
    }

    private function worstStatus(string ...$statuses): string
    {
        if (in_array(SLA_BREACHED, $statuses, true)) {
            return SLA_BREACHED;
        }
        if (in_array(SLA_WARNING, $statuses, true)) {
            return SLA_WARNING;
        }
        return SLA_ON_TRACK;
    }

    private function rulePayload(array $input): array
    {
        $name = trim($input['name'] ?? '');
        $priority = $input['priority'] ?? 'medium';
        $response = (int)($input['response_time'] ?? 0);
        $resolution = (int)($input['resolution_time'] ?? 0);
        $warning = (int)($input['warning_threshold'] ?? 80);

        if ($name === '') {
            return ['success' => false, 'message' => 'SLA rule name is required.'];
        }
        if (!in_array($priority, ['critical', 'high', 'medium', 'low'], true)) {
            return ['success' => false, 'message' => 'Priority is not valid.'];
        }
        if ($response <= 0 || $resolution <= 0) {
            return ['success' => false, 'message' => 'Response and resolution targets must be greater than zero minutes.'];
        }
        if ($resolution < $response) {
            return ['success' => false, 'message' => 'Resolution target cannot be shorter than response target.'];
        }

        return [
            'success' => true,
            'data' => [
                'name' => $name,
                'priority' => $priority,
                'response_time' => $response,
                'resolution_time' => $resolution,
                'escalation_time' => max(0, (int)($input['escalation_time'] ?? 0)),
                'warning_threshold' => max(1, min(99, $warning ?: 80)),
                'escalation_role' => preg_replace('/[^a-z0-9_, -]/i', '', trim($input['escalation_role'] ?? 'manager')),
                'notify_roles' => preg_replace('/[^a-z0-9_, -]/i', '', trim($input['notify_roles'] ?? 'technician,manager,administrator')),
                'business_hours_only' => isset($input['business_hours_only']) ? 1 : 0,
                'sort_order' => (int)($input['sort_order'] ?? 0),
                'is_active' => isset($input['is_active']) ? 1 : 0,
            ],
        ];
    }
}
