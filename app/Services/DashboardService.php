<?php
namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Session;

class DashboardService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getStaffStats(int $userId): array
    {
        return [
            'open_tickets' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE requester_id=? AND status NOT IN ('resolved','closed') AND deleted_at IS NULL", [$userId]),
            'pending' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE requester_id=? AND status IN ('waiting_user','waiting_vendor') AND deleted_at IS NULL", [$userId]),
            'resolved_today' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE requester_id=? AND status='resolved' AND DATE(resolved_at)=CURDATE() AND deleted_at IS NULL", [$userId]),
            'total' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL"),
        ];
    }

    public function getTechStats(int $userId): array
    {
        return [
            'assigned' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE assigned_to=? AND status NOT IN ('resolved','closed') AND deleted_at IS NULL", [$userId]),
            'due_today' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE assigned_to=? AND DATE(sla_due_at)=CURDATE() AND status NOT IN ('resolved','closed') AND deleted_at IS NULL", [$userId]),
            'sla_warnings' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE assigned_to=? AND sla_status='warning' AND status NOT IN ('resolved','closed') AND deleted_at IS NULL", [$userId]),
            'escalations' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE assigned_to=? AND sla_status='breached' AND status NOT IN ('resolved','closed') AND deleted_at IS NULL", [$userId]),
        ];
    }

    public function getManagementStats(): array
    {
        $total = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $slaMet = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE sla_status='on_track' AND deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return [
            'sla_compliance' => $total > 0 ? round(($slaMet / $total) * 100, 1) : 100,
            'avg_resolution' => $this->db->fetchColumn("SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)),0) FROM tickets WHERE resolved_at IS NOT NULL AND deleted_at IS NULL AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?? 0,
            'total_assets' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM assets WHERE deleted_at IS NULL"),
            'active_incidents' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('resolved','closed') AND deleted_at IS NULL"),
        ];
    }

    public function getStatusCounts(): array
    {
        $rows = $this->db->fetchAll("SELECT status, COUNT(*) as cnt FROM tickets WHERE deleted_at IS NULL GROUP BY status");
        $result = [];
        foreach ($rows as $r) $result[$r->status] = (int)$r->cnt;
        return $result;
    }

    public function getRecentActivity(int $limit = 5, ?int $userId = null): array
    {
        $sql = "SELECT th.*, u.first_name, u.last_name, t.ticket_number, t.title as ticket_title
                FROM ticket_history th
                JOIN users u ON th.user_id = u.id
                JOIN tickets t ON th.ticket_id = t.id
                ORDER BY th.created_at DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function getMyTickets(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT t.*, tc.name as category_name FROM tickets t
             LEFT JOIN ticket_categories tc ON t.category_id = tc.id
             WHERE t.assigned_to = ? AND t.status NOT IN ('resolved','closed') AND t.deleted_at IS NULL
             ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.created_at ASC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function getMonthlyTrends(int $months = 7): array
    {
        return $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total,
                    SUM(CASE WHEN priority IN ('critical','high') THEN 1 ELSE 0 END) as critical_high
             FROM tickets WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month", [$months]
        );
    }
}
