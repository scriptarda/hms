<?php
namespace App\Repositories;

use App\Helpers\Database;

class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByEmail(string $email): ?object
    {
        return $this->db->fetch(
            "SELECT u.*, d.name as department_name,
                    r.name as role_name, r.slug as role_slug
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             WHERE u.email = ? AND u.deleted_at IS NULL",
            [$email]
        );
    }

    public function findById(int $id): ?object
    {
        return $this->db->fetch(
            "SELECT u.*, d.name as department_name,
                    r.name as role_name, r.slug as role_slug
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             WHERE u.id = ? AND u.deleted_at IS NULL",
            [$id]
        );
    }

    public function getAll(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $sql = "SELECT u.*, d.name as department_name, r.name as role_name, r.slug as role_slug
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.deleted_at IS NULL";
        $params = [];
        if ($search) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }
        $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function count(?string $search = null): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL";
        $params = [];
        if ($search) {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }
        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert('users', $data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('users', $data, 'id = ?', [$id]);
    }

    public function updateLoginAttempts(int $id, int $attempts, ?string $lockedUntil = null): void
    {
        $data = ['login_attempts' => $attempts, 'locked_until' => $lockedUntil];
        $this->db->update('users', $data, 'id = ?', [$id]);
    }

    public function recordLogin(int $id): void
    {
        $this->db->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'login_attempts' => 0,
            'locked_until' => null
        ], 'id = ?', [$id]);
    }

    public function getPermissions(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT p.slug FROM permissions p
             JOIN role_permissions rp ON p.id = rp.permission_id
             JOIN user_roles ur ON rp.role_id = ur.role_id
             WHERE ur.user_id = ?",
            [$userId]
        );
        return array_map(fn($r) => $r->slug, $rows);
    }

    public function setPasswordResetToken(int $id, string $token, string $expires): void
    {
        $this->db->update('users', [
            'password_reset_token' => $token,
            'password_reset_expires' => $expires
        ], 'id = ?', [$id]);
    }

    public function findByResetToken(string $token): ?object
    {
        return $this->db->fetch(
            "SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW() AND deleted_at IS NULL",
            [$token]
        );
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $this->db->query("DELETE FROM user_roles WHERE user_id = ?", [$userId]);
        $this->db->insert('user_roles', ['user_id' => $userId, 'role_id' => $roleId]);
    }

    public function getTechnicians(): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE r.slug IN ('technician','biomedical_engineer','administrator','super_administrator')
             AND u.status = 'active' AND u.deleted_at IS NULL
             ORDER BY u.first_name"
        );
    }
}
