<?php
namespace App\Repositories;

use App\Helpers\Database;

class MaintenanceRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function refreshOverdue(): int
    {
        return $this->db->update(
            'maintenance_tasks',
            ['status' => 'overdue'],
            "due_date < CURDATE() AND status = 'scheduled' AND deleted_at IS NULL"
        );
    }

    public function metrics(): array
    {
        $duePm = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM maintenance_tasks
             WHERE type='preventive' AND due_date <= CURDATE()
             AND status != 'cancelled' AND deleted_at IS NULL"
        );
        $completedPm = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM maintenance_tasks
             WHERE type='preventive' AND due_date <= CURDATE()
             AND status='completed' AND deleted_at IS NULL"
        );

        return [
            'open' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM maintenance_tasks WHERE status IN ('scheduled','in_progress','overdue') AND deleted_at IS NULL"),
            'due_today' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM maintenance_tasks WHERE due_date <= CURDATE() AND status NOT IN ('completed','cancelled') AND deleted_at IS NULL"),
            'overdue' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM maintenance_tasks WHERE due_date < CURDATE() AND status NOT IN ('completed','cancelled') AND deleted_at IS NULL"),
            'completed_month' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM maintenance_tasks WHERE status='completed' AND completed_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND deleted_at IS NULL"),
            'active_schedules' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM maintenance_schedules WHERE is_active=1 AND deleted_at IS NULL"),
            'pm_compliance' => $duePm > 0 ? round(($completedPm / $duePm) * 100, 1) : 100,
            'cost_month' => (float)$this->db->fetchColumn("SELECT COALESCE(SUM(cost),0) FROM maintenance_tasks WHERE completed_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND deleted_at IS NULL"),
        ];
    }

    public function statusCounts(): array
    {
        return $this->db->fetchAll(
            "SELECT status, COUNT(*) as cnt
             FROM maintenance_tasks
             WHERE deleted_at IS NULL
             GROUP BY status
             ORDER BY FIELD(status, 'scheduled','in_progress','overdue','completed','cancelled')"
        );
    }

    public function typeCounts(): array
    {
        return $this->db->fetchAll(
            "SELECT type, COUNT(*) as cnt, COALESCE(SUM(cost),0) as total_cost
             FROM maintenance_tasks
             WHERE deleted_at IS NULL
             GROUP BY type
             ORDER BY FIELD(type, 'preventive','inspection','corrective','emergency')"
        );
    }

    public function getAll(array $filters = []): array
    {
        [$sql, $params] = $this->filteredTaskQuery($filters);
        $sql .= " ORDER BY FIELD(m.status, 'overdue','in_progress','scheduled','completed','cancelled'), FIELD(m.priority, 'critical','high','medium','low'), m.due_date ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function upcoming(int $limit = 8): array
    {
        return $this->db->fetchAll(
            $this->taskSelect() . "
             WHERE m.deleted_at IS NULL
             AND m.status IN ('scheduled','in_progress','overdue')
             ORDER BY m.due_date ASC
             LIMIT {$limit}"
        );
    }

    public function atRisk(int $limit = 8): array
    {
        return $this->db->fetchAll(
            $this->taskSelect() . "
             WHERE m.deleted_at IS NULL
             AND m.status NOT IN ('completed','cancelled')
             AND (m.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) OR m.priority IN ('critical','high'))
             ORDER BY m.due_date ASC, FIELD(m.priority, 'critical','high','medium','low')
             LIMIT {$limit}"
        );
    }

    public function queue(?int $userId = null, array $filters = []): array
    {
        $sql = $this->taskSelect() . "
             WHERE m.deleted_at IS NULL
             AND m.status IN ('scheduled','in_progress','overdue')";
        $params = [];

        if ($userId && ($filters['scope'] ?? 'team') === 'mine') {
            $sql .= " AND m.assigned_to = ?";
            $params[] = $userId;
        } elseif ($userId) {
            $sql .= " AND (m.assigned_to = ? OR m.assigned_to IS NULL)";
            $params[] = $userId;
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND m.priority = ?";
            $params[] = $filters['priority'];
        }

        $sql .= " ORDER BY FIELD(m.status, 'overdue','in_progress','scheduled'), m.due_date ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function history(array $filters = []): array
    {
        $filters['history'] = true;
        [$sql, $params] = $this->filteredTaskQuery($filters);
        $sql .= " ORDER BY COALESCE(m.completed_date, DATE(m.updated_at), m.due_date) DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function findById(int $id): ?object
    {
        return $this->db->fetch(
            $this->taskSelect() . " WHERE m.id = ? AND m.deleted_at IS NULL",
            [$id]
        );
    }

    public function openTaskForSchedule(int $scheduleId): ?object
    {
        return $this->db->fetch(
            "SELECT * FROM maintenance_tasks
             WHERE schedule_id = ? AND status NOT IN ('completed','cancelled') AND deleted_at IS NULL
             ORDER BY id DESC LIMIT 1",
            [$scheduleId]
        );
    }

    public function createTask(array $data): int
    {
        return $this->db->insert('maintenance_tasks', $data);
    }

    public function updateTask(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('maintenance_tasks', $data, 'id = ?', [$id]);
    }

    public function addLog(array $data): int
    {
        return $this->db->insert('maintenance_logs', $data);
    }

    public function logs(int $taskId): array
    {
        return $this->db->fetchAll(
            "SELECT ml.*, TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as user_name
             FROM maintenance_logs ml
             JOIN users u ON ml.user_id = u.id
             WHERE ml.task_id = ?
             ORDER BY ml.created_at DESC",
            [$taskId]
        );
    }

    public function calendarEvents(?string $start = null, ?string $end = null): array
    {
        $sql = $this->taskSelect() . " WHERE m.deleted_at IS NULL";
        $params = [];
        if ($start && $end) {
            $sql .= " AND m.scheduled_date BETWEEN ? AND ?";
            $params[] = date('Y-m-d', strtotime($start));
            $params[] = date('Y-m-d', strtotime($end));
        }
        $sql .= " ORDER BY m.scheduled_date ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function schedules(array $filters = []): array
    {
        $sql = "SELECT ms.*, a.asset_tag, a.name as asset_name, d.name as department_name,
                       TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as tech_name
                FROM maintenance_schedules ms
                JOIN assets a ON ms.asset_id = a.id
                LEFT JOIN departments d ON ms.department_id = d.id
                LEFT JOIN users u ON ms.assigned_to = u.id
                WHERE ms.deleted_at IS NULL";
        $params = [];

        if (isset($filters['active']) && $filters['active'] !== '') {
            $sql .= " AND ms.is_active = ?";
            $params[] = (int)$filters['active'];
        }
        if (!empty($filters['due_within_days'])) {
            $sql .= " AND ms.next_due <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
            $params[] = (int)$filters['due_within_days'];
        }

        $sql .= " ORDER BY ms.next_due ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function findSchedule(int $id): ?object
    {
        return $this->db->fetch(
            "SELECT ms.*, a.asset_tag, a.name as asset_name
             FROM maintenance_schedules ms
             JOIN assets a ON ms.asset_id = a.id
             WHERE ms.id = ? AND ms.deleted_at IS NULL",
            [$id]
        );
    }

    public function createSchedule(array $data): int
    {
        return $this->db->insert('maintenance_schedules', $data);
    }

    public function updateSchedule(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('maintenance_schedules', $data, 'id = ?', [$id]);
    }

    public function generateWorkOrderNumber(): string
    {
        $next = ((int)$this->db->fetchColumn("SELECT COALESCE(MAX(id), 0) + 1 FROM maintenance_tasks")) + 9400;
        return 'WO-' . date('ym') . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public function assets(): array
    {
        return $this->db->fetchAll("SELECT id, asset_tag, name, department_id FROM assets WHERE deleted_at IS NULL ORDER BY asset_tag");
    }

    public function technicians(): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email
             FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ('technician', 'biomedical_engineer', 'administrator', 'super_administrator')
             AND u.status='active' AND u.deleted_at IS NULL
             ORDER BY u.first_name, u.last_name"
        );
    }

    public function departments(): array
    {
        return $this->db->fetchAll("SELECT id, name FROM departments WHERE deleted_at IS NULL ORDER BY name");
    }

    public function notifyUser(int $userId, string $title, string $message, string $link): void
    {
        (new \App\Services\NotificationService())->send($userId, NOTIFY_MAINTENANCE_DUE, $title, $message, $link);
    }

    public function addAssetHistory(int $assetId, int $userId, string $action, string $description): void
    {
        $this->db->insert('asset_history', [
            'asset_id' => $assetId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }

    public function updateAssetMaintenanceState(int $assetId, array $data): void
    {
        $this->db->update('assets', $data, 'id = ?', [$assetId]);
    }

    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void { $this->db->commit(); }
    public function rollback(): void { $this->db->rollback(); }

    private function filteredTaskQuery(array $filters): array
    {
        $sql = $this->taskSelect() . " WHERE m.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['history'])) {
            $sql .= " AND m.status IN ('completed','cancelled')";
        }
        if (!empty($filters['status'])) {
            $sql .= " AND m.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND m.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND m.priority = ?";
            $params[] = $filters['priority'];
        }
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND m.assigned_to = ?";
            $params[] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['asset_id'])) {
            $sql .= " AND m.asset_id = ?";
            $params[] = (int)$filters['asset_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND m.scheduled_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND m.scheduled_date <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $needle = '%' . $filters['search'] . '%';
            $sql .= " AND (m.title LIKE ? OR m.work_order_number LIKE ? OR a.asset_tag LIKE ? OR a.name LIKE ?)";
            array_push($params, $needle, $needle, $needle, $needle);
        }

        return [$sql, $params];
    }

    private function taskSelect(): string
    {
        return "SELECT m.*,
                       COALESCE(m.work_order_number, CONCAT('WO-', LPAD(m.id, 4, '0'))) as wo_number,
                       a.asset_tag, a.name as asset_name,
                       d.name as dept_name,
                       TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as tech_name,
                       u.email as tech_email,
                       TRIM(CONCAT(COALESCE(req.first_name,''), ' ', COALESCE(req.last_name,''))) as requested_by_name,
                       TRIM(CONCAT(COALESCE(done.first_name,''), ' ', COALESCE(done.last_name,''))) as completed_by_name,
                       ms.frequency as schedule_frequency,
                       ms.next_due as schedule_next_due
                FROM maintenance_tasks m
                LEFT JOIN assets a ON m.asset_id = a.id
                LEFT JOIN departments d ON m.department_id = d.id
                LEFT JOIN users u ON m.assigned_to = u.id
                LEFT JOIN users req ON m.requested_by = req.id
                LEFT JOIN users done ON m.completed_by = done.id
                LEFT JOIN maintenance_schedules ms ON m.schedule_id = ms.id";
    }
}
