<?php
namespace App\Repositories;

use App\Helpers\Database;

class SlaRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function rules(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM sla_rules WHERE deleted_at IS NULL";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, FIELD(priority, 'critical','high','medium','low'), id ASC";

        return $this->db->fetchAll($sql);
    }

    public function findRule(int $id): ?object
    {
        return $this->db->fetch("SELECT * FROM sla_rules WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function activeRuleForPriority(string $priority): ?object
    {
        return $this->db->fetch(
            "SELECT * FROM sla_rules
             WHERE priority = ? AND is_active = 1 AND deleted_at IS NULL
             ORDER BY sort_order ASC, id DESC
             LIMIT 1",
            [$priority]
        );
    }

    public function createRule(array $data): int
    {
        return $this->db->insert('sla_rules', $data);
    }

    public function updateRule(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('sla_rules', $data, 'id = ?', [$id]);
    }

    public function ticket(int $id): ?object
    {
        return $this->db->fetch(
            $this->ticketSelect() . "
             WHERE t.id = ? AND t.deleted_at IS NULL",
            [$id]
        );
    }

    public function openTicketsForMonitor(int $limit = 500): array
    {
        return $this->db->fetchAll(
            $this->ticketSelect() . "
             WHERE t.deleted_at IS NULL
             AND t.status NOT IN ('resolved','closed')
             ORDER BY FIELD(t.sla_status, 'breached','warning','on_track'),
                      COALESCE(t.response_due_at, t.resolution_due_at, t.sla_due_at, t.created_at) ASC
             LIMIT ?",
            [$limit]
        );
    }

    public function updateTicket(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('tickets', $data, 'id = ?', [$id]);
    }

    public function addEvent(array $data): int
    {
        return $this->db->insert('sla_events', $data);
    }

    public function recentEvents(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT se.*, t.ticket_number, t.title, t.priority,
                    sr.name as rule_name
             FROM sla_events se
             JOIN tickets t ON se.ticket_id = t.id
             LEFT JOIN sla_rules sr ON se.rule_id = sr.id
             ORDER BY se.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function metrics(): array
    {
        $openWhere = "deleted_at IS NULL AND status NOT IN ('resolved','closed')";
        $resolvedWindow = "deleted_at IS NULL
            AND status IN ('resolved','closed')
            AND COALESCE(resolved_at, closed_at, updated_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

        $resolved = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE {$resolvedWindow}");
        $met = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM tickets
             WHERE {$resolvedWindow}
             AND (
                 COALESCE(resolution_due_at, sla_due_at) IS NULL
                 OR COALESCE(resolved_at, closed_at, updated_at) <= COALESCE(resolution_due_at, sla_due_at)
             )"
        );

        return [
            'open_tickets' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE {$openWhere}"),
            'response_warning' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE {$openWhere} AND response_sla_status = 'warning'"),
            'response_breached' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE {$openWhere} AND response_sla_status = 'breached'"),
            'resolution_warning' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE {$openWhere} AND resolution_sla_status = 'warning'"),
            'resolution_breached' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE {$openWhere} AND resolution_sla_status = 'breached'"),
            'escalated' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE {$openWhere} AND escalation_level > 0"),
            'due_soon' => (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM tickets
                 WHERE {$openWhere}
                 AND sla_status <> 'breached'
                 AND COALESCE(resolution_due_at, sla_due_at) <= DATE_ADD(NOW(), INTERVAL 2 HOUR)"
            ),
            'resolved_30d' => $resolved,
            'resolution_compliance' => $resolved > 0 ? round(($met / $resolved) * 100, 1) : 100.0,
        ];
    }

    public function atRiskTickets(int $limit = 10): array
    {
        return $this->db->fetchAll(
            $this->ticketSelect() . "
             WHERE t.deleted_at IS NULL
             AND t.status NOT IN ('resolved','closed')
             AND (
                t.sla_status IN ('warning','breached')
                OR t.response_due_at <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                OR COALESCE(t.resolution_due_at, t.sla_due_at) <= DATE_ADD(NOW(), INTERVAL 2 HOUR)
             )
             ORDER BY FIELD(t.sla_status, 'breached','warning','on_track'),
                      COALESCE(t.response_due_at, t.resolution_due_at, t.sla_due_at) ASC
             LIMIT ?",
            [$limit]
        );
    }

    public function usersForNotification(?int $assignedTo, string $rolesCsv = '', ?string $escalationRole = null): array
    {
        $userIds = [];
        if ($assignedTo) {
            $userIds[] = $assignedTo;
        }

        $roles = array_filter(array_map('trim', explode(',', $rolesCsv)));
        if ($escalationRole) {
            $roles[] = trim($escalationRole);
        }
        $roles = array_values(array_unique(array_filter($roles)));

        if (!empty($roles)) {
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT u.id
                 FROM users u
                 JOIN user_roles ur ON u.id = ur.user_id
                 JOIN roles r ON ur.role_id = r.id
                 WHERE r.slug IN ({$placeholders})
                 AND u.status = 'active'
                 AND u.deleted_at IS NULL",
                $roles
            );
            foreach ($rows as $row) {
                $userIds[] = (int)$row->id;
            }
        }

        return array_values(array_unique(array_filter($userIds)));
    }

    public function notify(int $userId, string $type, string $title, string $message, string $link): void
    {
        (new \App\Services\NotificationService())->send($userId, $type, $title, $message, $link);
    }

    private function ticketSelect(): string
    {
        return "SELECT t.*,
                       sr.id as rule_id,
                       sr.name as rule_name,
                       sr.response_time as rule_response_time,
                       sr.resolution_time as rule_resolution_time,
                       sr.escalation_time as rule_escalation_time,
                       sr.warning_threshold as rule_warning_threshold,
                       sr.notify_roles as rule_notify_roles,
                       sr.escalation_role as rule_escalation_role,
                       CONCAT(req.first_name, ' ', req.last_name) as requester_name,
                       CONCAT(asgn.first_name, ' ', asgn.last_name) as assignee_name,
                       tc.name as category_name,
                       d.name as dept_name
                FROM tickets t
                LEFT JOIN sla_rules sr ON t.sla_rule_id = sr.id AND sr.deleted_at IS NULL
                LEFT JOIN users req ON t.requester_id = req.id
                LEFT JOIN users asgn ON t.assigned_to = asgn.id
                LEFT JOIN ticket_categories tc ON t.category_id = tc.id
                LEFT JOIN departments d ON t.department_id = d.id";
    }
}
