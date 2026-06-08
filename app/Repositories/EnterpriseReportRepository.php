<?php
namespace App\Repositories;

use App\Helpers\Database;

class EnterpriseReportRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function db(): Database
    {
        return $this->db;
    }

    public function departments(): array
    {
        return $this->db->fetchAll("SELECT id, name FROM departments WHERE deleted_at IS NULL ORDER BY name");
    }

    public function users(): array
    {
        return $this->db->fetchAll(
            "SELECT id, first_name, last_name, email
             FROM users
             WHERE deleted_at IS NULL
             ORDER BY first_name, last_name"
        );
    }

    public function assetCategories(): array
    {
        return $this->db->fetchAll("SELECT id, name FROM asset_categories WHERE deleted_at IS NULL ORDER BY name");
    }

    public function ticketCategories(): array
    {
        return $this->db->fetchAll("SELECT id, name FROM ticket_categories WHERE deleted_at IS NULL ORDER BY name");
    }

    public function inventoryCategories(): array
    {
        return $this->db->fetchAll("SELECT id, name FROM inventory_categories WHERE deleted_at IS NULL ORDER BY name");
    }

    public function schedules(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM report_schedules
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY is_active DESC, next_run_at ASC, created_at DESC",
            [$userId]
        );
    }

    public function createSchedule(array $data): int
    {
        return $this->db->insert('report_schedules', $data);
    }

    public function findSchedule(int $id): ?object
    {
        return $this->db->fetch("SELECT * FROM report_schedules WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function toggleSchedule(int $id, int $userId): void
    {
        $this->db->query(
            "UPDATE report_schedules
             SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW()
             WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );
    }

    public function dueSchedules(int $limit = 25): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM report_schedules
             WHERE is_active = 1
             AND deleted_at IS NULL
             AND (next_run_at IS NULL OR next_run_at <= NOW())
             ORDER BY COALESCE(next_run_at, created_at) ASC
             LIMIT ?",
            [$limit]
        );
    }

    public function updateScheduleRun(int $id, string $nextRunAt): void
    {
        $this->db->update(
            'report_schedules',
            [
                'last_run_at' => date('Y-m-d H:i:s'),
                'next_run_at' => $nextRunAt,
            ],
            'id = ?',
            [$id]
        );
    }

    public function createExport(array $data): int
    {
        return $this->db->insert('report_exports', $data);
    }

    public function recentExports(int $userId, int $limit = 8): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM report_exports
             WHERE user_id = ?
             ORDER BY generated_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }
}
