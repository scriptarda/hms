<?php
namespace App\Repositories;

use App\Helpers\Database;

class AssetRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT a.*, ac.name as category_name, d.name as department_name,
                       b.name as building_name, f.name as floor_name, r.name as room_name, r.room_number,
                       aa.user_id as assigned_user_id,
                       CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
                       u.email as assigned_user_email
                FROM assets a
                LEFT JOIN asset_categories ac ON a.category_id = ac.id
                LEFT JOIN departments d ON a.department_id = d.id
                LEFT JOIN buildings b ON a.building_id = b.id
                LEFT JOIN floors f ON a.floor_id = f.id
                LEFT JOIN rooms r ON a.room_id = r.id
                LEFT JOIN asset_assignments aa ON aa.asset_id = a.id AND aa.returned_at IS NULL
                LEFT JOIN users u ON aa.user_id = u.id
                WHERE a.deleted_at IS NULL";

        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND a.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['department_id'])) {
            $sql .= " AND a.department_id = ?";
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['assigned_user_id'])) {
            $sql .= " AND aa.user_id = ?";
            $params[] = $filters['assigned_user_id'];
        }
        if (!empty($filters['warranty'])) {
            if ($filters['warranty'] === 'expired') {
                $sql .= " AND a.warranty_expiry IS NOT NULL AND a.warranty_expiry < CURDATE()";
            } elseif ($filters['warranty'] === 'expiring_30') {
                $sql .= " AND a.warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            } elseif ($filters['warranty'] === 'expiring_90') {
                $sql .= " AND a.warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
            } elseif ($filters['warranty'] === 'missing') {
                $sql .= " AND a.warranty_expiry IS NULL";
            }
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (a.name LIKE ? OR a.asset_tag LIKE ? OR a.serial_number LIKE ? OR a.manufacturer LIKE ? OR a.model LIKE ?)";
            $needle = '%' . $filters['search'] . '%';
            array_push($params, $needle, $needle, $needle, $needle, $needle);
        }

        $sql .= " ORDER BY a.asset_tag ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function findById(int $id): ?object
    {
        return $this->db->fetch(
            "SELECT a.*, ac.name as category_name, d.name as department_name, b.name as building_name,
                    f.name as floor_name, r.name as room_name, r.room_number,
                    aa.user_id as assigned_user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
                    u.email as assigned_user_email,
                    aa.assigned_at as assigned_at
             FROM assets a
             LEFT JOIN asset_categories ac ON a.category_id = ac.id
             LEFT JOIN departments d ON a.department_id = d.id
             LEFT JOIN buildings b ON a.building_id = b.id
             LEFT JOIN floors f ON a.floor_id = f.id
             LEFT JOIN rooms r ON a.room_id = r.id
             LEFT JOIN asset_assignments aa ON aa.asset_id = a.id AND aa.returned_at IS NULL
             LEFT JOIN users u ON aa.user_id = u.id
             WHERE a.id = ? AND a.deleted_at IS NULL",
            [$id]
        );
    }

    public function findRaw(int $id): ?object
    {
        return $this->db->fetch("SELECT * FROM assets WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function assetTagExists(string $tag, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM assets WHERE asset_tag = ? AND deleted_at IS NULL";
        $params = [$tag];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        return (bool)$this->db->fetch($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert('assets', $data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('assets', $data, 'id = ?', [$id]);
    }

    public function softDelete(int $id): int
    {
        return $this->db->softDelete('assets', 'id = ?', [$id]);
    }

    public function addHistory(int $assetId, int $userId, string $action, string $description): int
    {
        return $this->db->insert('asset_history', [
            'asset_id' => $assetId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }

    public function getHistory(int $assetId): array
    {
        return $this->db->fetchAll(
            "SELECT ah.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
             FROM asset_history ah
             JOIN users u ON ah.user_id = u.id
             WHERE ah.asset_id = ? ORDER BY ah.created_at DESC",
            [$assetId]
        );
    }

    public function getAssignments(int $assetId): array
    {
        return $this->db->fetchAll(
            "SELECT aa.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.email as user_email,
                    CONCAT(assigner.first_name, ' ', assigner.last_name) as assigned_by_name
             FROM asset_assignments aa
             JOIN users u ON aa.user_id = u.id
             JOIN users assigner ON aa.assigned_by = assigner.id
             WHERE aa.asset_id = ? ORDER BY aa.assigned_at DESC",
            [$assetId]
        );
    }

    public function getActiveAssignment(int $assetId): ?object
    {
        return $this->db->fetch(
            "SELECT aa.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.email as user_email
             FROM asset_assignments aa
             JOIN users u ON aa.user_id = u.id
             WHERE aa.asset_id = ? AND aa.returned_at IS NULL",
            [$assetId]
        );
    }

    public function closeActiveAssignments(int $assetId): int
    {
        return $this->db->update('asset_assignments', [
            'returned_at' => date('Y-m-d H:i:s')
        ], 'asset_id = ? AND returned_at IS NULL', [$assetId]);
    }

    public function createAssignment(int $assetId, int $userId, int $assignedBy, string $notes): int
    {
        return $this->db->insert('asset_assignments', [
            'asset_id' => $assetId,
            'user_id' => $userId,
            'assigned_by' => $assignedBy,
            'notes' => $notes,
        ]);
    }

    public function getTickets(int $assetId): array
    {
        return $this->db->fetchAll(
            "SELECT t.id, t.ticket_number, t.title, t.status, t.priority, t.created_at
             FROM tickets t
             WHERE t.asset_id = ? AND t.deleted_at IS NULL ORDER BY t.created_at DESC",
            [$assetId]
        );
    }

    public function getMaintenance(int $assetId): array
    {
        return $this->db->fetchAll(
            "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) as tech_name
             FROM maintenance_tasks m
             LEFT JOIN users u ON m.assigned_to = u.id
             WHERE m.asset_id = ? AND m.deleted_at IS NULL ORDER BY m.scheduled_date DESC",
            [$assetId]
        );
    }

    public function getCategories(): array
    {
        return $this->db->fetchAll("SELECT * FROM asset_categories WHERE deleted_at IS NULL ORDER BY name");
    }

    public function getDepartments(): array
    {
        return $this->db->fetchAll("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY name");
    }

    public function getBuildings(): array
    {
        return $this->db->fetchAll("SELECT * FROM buildings WHERE deleted_at IS NULL ORDER BY name");
    }

    public function getUsers(): array
    {
        return $this->db->fetchAll("SELECT id, first_name, last_name, email FROM users WHERE status='active' AND deleted_at IS NULL ORDER BY first_name, last_name");
    }

    public function getMetrics(): array
    {
        return [
            'total' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM assets WHERE deleted_at IS NULL"),
            'active' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM assets WHERE status='active' AND deleted_at IS NULL"),
            'assigned' => (int)$this->db->fetchColumn("SELECT COUNT(DISTINCT asset_id) FROM asset_assignments WHERE returned_at IS NULL"),
            'warranty_alerts' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM assets WHERE warranty_expiry IS NOT NULL AND warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND deleted_at IS NULL"),
        ];
    }

    public function getWarrantyAlerts(int $days = 90): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, ac.name as category_name, d.name as department_name
             FROM assets a
             LEFT JOIN asset_categories ac ON a.category_id = ac.id
             LEFT JOIN departments d ON a.department_id = d.id
             WHERE a.warranty_expiry IS NOT NULL
             AND a.warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND a.deleted_at IS NULL
             ORDER BY a.warranty_expiry ASC",
            [$days]
        );
    }

    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void { $this->db->commit(); }
    public function rollback(): void { $this->db->rollback(); }
}
