<?php
namespace App\Repositories;

use App\Helpers\Database;

class TicketRepository
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findById(int $id): ?object
    {
        return $this->db->fetch(
            "SELECT t.*, tc.name as category_name, ts.name as subcategory_name,
                    d.name as dept_name, b.name as building_name,
                    CONCAT(req.first_name,' ',req.last_name) as requester_name, req.email as requester_email,
                    CONCAT(asgn.first_name,' ',asgn.last_name) as assignee_name,
                    a.name as asset_name, a.asset_tag
             FROM tickets t
             LEFT JOIN ticket_categories tc ON t.category_id=tc.id
             LEFT JOIN ticket_subcategories ts ON t.subcategory_id=ts.id
             LEFT JOIN departments d ON t.department_id=d.id
             LEFT JOIN buildings b ON t.building_id=b.id
             LEFT JOIN users req ON t.requester_id=req.id
             LEFT JOIN users asgn ON t.assigned_to=asgn.id
             LEFT JOIN assets a ON t.asset_id=a.id
             WHERE t.id=? AND t.deleted_at IS NULL", [$id]);
    }

    public function getAll(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $sql = "SELECT t.*, tc.name as category_name,
                CONCAT(req.first_name,' ',req.last_name) as requester_name,
                CONCAT(asgn.first_name,' ',asgn.last_name) as assignee_name,
                d.name as dept_name
                FROM tickets t
                LEFT JOIN ticket_categories tc ON t.category_id=tc.id
                LEFT JOIN users req ON t.requester_id=req.id
                LEFT JOIN users asgn ON t.assigned_to=asgn.id
                LEFT JOIN departments d ON t.department_id=d.id
                WHERE t.deleted_at IS NULL";
        $params = [];
        if (!empty($filters['status'])) { $sql .= " AND t.status=?"; $params[] = $filters['status']; }
        if (!empty($filters['priority'])) { $sql .= " AND t.priority=?"; $params[] = $filters['priority']; }
        if (!empty($filters['department_id'])) { $sql .= " AND t.department_id=?"; $params[] = $filters['department_id']; }
        if (!empty($filters['assigned_to'])) { $sql .= " AND t.assigned_to=?"; $params[] = $filters['assigned_to']; }
        if (!empty($filters['requester_id'])) { $sql .= " AND t.requester_id=?"; $params[] = $filters['requester_id']; }
        if (!empty($filters['search'])) { $sql .= " AND (t.title LIKE ? OR t.ticket_number LIKE ?)"; $params[] = "%{$filters['search']}%"; $params[] = "%{$filters['search']}%"; }
        $sql .= " ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM tickets t WHERE t.deleted_at IS NULL";
        $params = [];
        if (!empty($filters['status'])) { $sql .= " AND t.status=?"; $params[] = $filters['status']; }
        if (!empty($filters['priority'])) { $sql .= " AND t.priority=?"; $params[] = $filters['priority']; }
        if (!empty($filters['search'])) { $sql .= " AND (t.title LIKE ? OR t.ticket_number LIKE ?)"; $params[] = "%{$filters['search']}%"; $params[] = "%{$filters['search']}%"; }
        return (int)$this->db->fetchColumn($sql, $params);
    }

    public function create(array $data): int { return $this->db->insert('tickets', $data); }
    public function update(int $id, array $data): int { $data['updated_at'] = date('Y-m-d H:i:s'); return $this->db->update('tickets', $data, 'id=?', [$id]); }
    public function getComments(int $ticketId): array { return $this->db->fetchAll("SELECT tc.*, CONCAT(u.first_name,' ',u.last_name) as user_name, u.avatar FROM ticket_comments tc JOIN users u ON tc.user_id=u.id WHERE tc.ticket_id=? AND tc.deleted_at IS NULL ORDER BY tc.created_at ASC", [$ticketId]); }
    public function addComment(array $data): int { return $this->db->insert('ticket_comments', $data); }
    public function getAttachments(int $ticketId): array { return $this->db->fetchAll("SELECT * FROM ticket_attachments WHERE ticket_id=? ORDER BY created_at DESC", [$ticketId]); }
    public function addAttachment(array $data): int { return $this->db->insert('ticket_attachments', $data); }
    public function addHistory(array $data): int { return $this->db->insert('ticket_history', $data); }
    public function getHistory(int $ticketId): array { return $this->db->fetchAll("SELECT th.*, CONCAT(u.first_name,' ',u.last_name) as user_name FROM ticket_history th JOIN users u ON th.user_id=u.id WHERE th.ticket_id=? ORDER BY th.created_at DESC", [$ticketId]); }
    public function getCategories(): array { return $this->db->fetchAll("SELECT * FROM ticket_categories WHERE is_active=1 AND deleted_at IS NULL ORDER BY name"); }
    public function getSubcategories(int $catId): array { return $this->db->fetchAll("SELECT * FROM ticket_subcategories WHERE category_id=? AND is_active=1 AND deleted_at IS NULL", [$catId]); }
    public function generateTicketNumber(): string { $last = $this->db->fetchColumn("SELECT MAX(id) FROM tickets"); return 'HEMS-' . str_pad(($last ?? 0) + 9400, 4, '0', STR_PAD_LEFT); }
}
