<?php
namespace App\Repositories;

use App\Helpers\Database;

class InventoryRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function metrics(): array
    {
        return [
            'total_items' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM inventory_items WHERE deleted_at IS NULL"),
            'low_stock' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM inventory_items WHERE quantity > 0 AND quantity <= reorder_level AND deleted_at IS NULL"),
            'out_of_stock' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM inventory_items WHERE quantity <= 0 AND deleted_at IS NULL"),
            'inventory_value' => (float)$this->db->fetchColumn("SELECT COALESCE(SUM(quantity * COALESCE(unit_cost,0)),0) FROM inventory_items WHERE deleted_at IS NULL"),
            'open_requests' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM inventory_purchase_requests WHERE status IN ('submitted','approved','ordered') AND deleted_at IS NULL"),
            'suppliers' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM inventory_suppliers WHERE is_active=1 AND deleted_at IS NULL"),
        ];
    }

    public function items(array $filters = []): array
    {
        $sql = $this->itemSelect() . " WHERE i.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND i.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['supplier_id'])) {
            $sql .= " AND i.supplier_id = ?";
            $params[] = (int)$filters['supplier_id'];
        }
        if (!empty($filters['stock'])) {
            if ($filters['stock'] === 'low') {
                $sql .= " AND i.quantity > 0 AND i.quantity <= i.reorder_level";
            } elseif ($filters['stock'] === 'out') {
                $sql .= " AND i.quantity <= 0";
            } elseif ($filters['stock'] === 'ok') {
                $sql .= " AND i.quantity > i.reorder_level";
            }
        }
        if (!empty($filters['search'])) {
            $needle = '%' . $filters['search'] . '%';
            $sql .= " AND (i.name LIKE ? OR i.sku LIKE ? OR i.location LIKE ? OR i.supplier LIKE ? OR s.name LIKE ?)";
            array_push($params, $needle, $needle, $needle, $needle, $needle);
        }

        $sql .= " ORDER BY i.name ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function reorderAlerts(): array
    {
        return $this->db->fetchAll(
            $this->itemSelect() . "
             WHERE i.deleted_at IS NULL AND i.quantity <= i.reorder_level
             ORDER BY (i.quantity <= 0) DESC, (i.reorder_level - i.quantity) DESC, i.name ASC"
        );
    }

    public function findItem(int $id): ?object
    {
        return $this->db->fetch($this->itemSelect() . " WHERE i.id = ? AND i.deleted_at IS NULL", [$id]);
    }

    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM inventory_items WHERE sku = ? AND deleted_at IS NULL";
        $params = [$sku];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        return (bool)$this->db->fetch($sql, $params);
    }

    public function createItem(array $data): int
    {
        return $this->db->insert('inventory_items', $data);
    }

    public function updateItem(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('inventory_items', $data, 'id = ?', [$id]);
    }

    public function transactions(?int $itemId = null, int $limit = 100): array
    {
        $sql = "SELECT it.*, i.name as item_name, i.sku, i.unit,
                       TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as user_name
                FROM inventory_transactions it
                JOIN inventory_items i ON it.item_id = i.id
                JOIN users u ON it.user_id = u.id";
        $params = [];

        if ($itemId) {
            $sql .= " WHERE it.item_id = ?";
            $params[] = $itemId;
        }

        $sql .= " ORDER BY it.created_at DESC LIMIT {$limit}";
        return $this->db->fetchAll($sql, $params);
    }

    public function addTransaction(array $data): int
    {
        return $this->db->insert('inventory_transactions', $data);
    }

    public function categories(): array
    {
        return $this->db->fetchAll("SELECT * FROM inventory_categories WHERE deleted_at IS NULL ORDER BY name");
    }

    public function suppliers(bool $activeOnly = false): array
    {
        $sql = "SELECT s.*,
                       COUNT(i.id) as item_count,
                       COALESCE(SUM(i.quantity * COALESCE(i.unit_cost,0)),0) as inventory_value
                FROM inventory_suppliers s
                LEFT JOIN inventory_items i ON i.supplier_id = s.id AND i.deleted_at IS NULL
                WHERE s.deleted_at IS NULL";
        if ($activeOnly) {
            $sql .= " AND s.is_active = 1";
        }
        $sql .= " GROUP BY s.id ORDER BY s.name ASC";
        return $this->db->fetchAll($sql);
    }

    public function createSupplier(array $data): int
    {
        return $this->db->insert('inventory_suppliers', $data);
    }

    public function purchaseRequests(array $filters = []): array
    {
        $sql = "SELECT pr.*, i.name as item_name, i.sku, i.unit, s.name as supplier_name,
                       TRIM(CONCAT(COALESCE(req.first_name,''), ' ', COALESCE(req.last_name,''))) as requested_by_name,
                       TRIM(CONCAT(COALESCE(app.first_name,''), ' ', COALESCE(app.last_name,''))) as approved_by_name
                FROM inventory_purchase_requests pr
                JOIN inventory_items i ON pr.item_id = i.id
                LEFT JOIN inventory_suppliers s ON pr.supplier_id = s.id
                JOIN users req ON pr.requested_by = req.id
                LEFT JOIN users app ON pr.approved_by = app.id
                WHERE pr.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND pr.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['item_id'])) {
            $sql .= " AND pr.item_id = ?";
            $params[] = (int)$filters['item_id'];
        }

        $sql .= " ORDER BY pr.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function findPurchaseRequest(int $id): ?object
    {
        return $this->db->fetch("SELECT * FROM inventory_purchase_requests WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function createPurchaseRequest(array $data): int
    {
        return $this->db->insert('inventory_purchase_requests', $data);
    }

    public function updatePurchaseRequest(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('inventory_purchase_requests', $data, 'id = ?', [$id]);
    }

    public function generateRequestNumber(): string
    {
        $next = ((int)$this->db->fetchColumn("SELECT COALESCE(MAX(id), 0) + 1 FROM inventory_purchase_requests")) + 7000;
        return 'PR-' . date('ym') . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public function managers(): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT u.id FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ('administrator','super_administrator','manager')
             AND u.status='active' AND u.deleted_at IS NULL"
        );
    }

    public function notify(int $userId, string $type, string $title, string $message, string $link): void
    {
        (new \App\Services\NotificationService())->send($userId, $type, $title, $message, $link);
    }

    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void { $this->db->commit(); }
    public function rollback(): void { $this->db->rollback(); }

    private function itemSelect(): string
    {
        return "SELECT i.*, ic.name as category_name,
                       s.name as supplier_name, s.email as supplier_email, s.phone as supplier_phone,
                       COALESCE(s.name, i.supplier) as display_supplier,
                       CASE
                           WHEN i.quantity <= 0 THEN 'out'
                           WHEN i.quantity <= i.reorder_level THEN 'low'
                           ELSE 'ok'
                       END as stock_state
                FROM inventory_items i
                LEFT JOIN inventory_categories ic ON i.category_id = ic.id
                LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id";
    }
}
