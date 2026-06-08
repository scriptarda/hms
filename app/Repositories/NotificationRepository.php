<?php
namespace App\Repositories;

use App\Helpers\Database;

class NotificationRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listForUser(int $userId, array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND deleted_at IS NULL";
        $params = [$userId];

        if (($filters['read'] ?? '') === 'unread') {
            $sql .= " AND is_read = 0";
        } elseif (($filters['read'] ?? '') === 'read') {
            $sql .= " AND is_read = 1";
        }
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['severity'])) {
            $sql .= " AND severity = ?";
            $params[] = $filters['severity'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR message LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY is_read ASC, created_at DESC LIMIT ?";
        $params[] = $limit;
        return $this->db->fetchAll($sql, $params);
    }

    public function stats(int $userId): array
    {
        return [
            'total' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND deleted_at IS NULL", [$userId]),
            'unread' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0 AND deleted_at IS NULL", [$userId]),
            'today' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND DATE(created_at) = CURDATE() AND deleted_at IS NULL", [$userId]),
            'critical' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND severity IN ('warning','danger') AND is_read = 0 AND deleted_at IS NULL", [$userId]),
        ];
    }

    public function unread(int $userId, int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notifications
             WHERE user_id = ? AND is_read = 0 AND deleted_at IS NULL
             ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function unreadCount(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0 AND deleted_at IS NULL",
            [$userId]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('notifications', $data);
    }

    public function markRead(int $id, int $userId, bool $read = true): int
    {
        return $this->db->update(
            'notifications',
            [
                'is_read' => $read ? 1 : 0,
                'read_at' => $read ? date('Y-m-d H:i:s') : null,
            ],
            'id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function markAllRead(int $userId): int
    {
        return $this->db->update(
            'notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'user_id = ? AND deleted_at IS NULL',
            [$userId]
        );
    }

    public function preferences(int $userId, array $types): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM notification_preferences WHERE user_id = ?",
            [$userId]
        );
        $byType = [];
        foreach ($rows as $row) {
            $byType[$row->type] = $row;
        }

        $prefs = [];
        foreach ($types as $type => $label) {
            $prefs[$type] = $byType[$type] ?? (object)[
                'type' => $type,
                'label' => $label,
                'in_app' => 1,
                'email' => 0,
                'sms' => 0,
                'push' => 0,
                'quiet_hours_start' => null,
                'quiet_hours_end' => null,
            ];
        }

        return $prefs;
    }

    public function preference(int $userId, string $type): object
    {
        $row = $this->db->fetch(
            "SELECT * FROM notification_preferences WHERE user_id = ? AND type = ?",
            [$userId, $type]
        );

        return $row ?: (object)[
            'type' => $type,
            'in_app' => 1,
            'email' => 0,
            'sms' => 0,
            'push' => 0,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
        ];
    }

    public function savePreference(int $userId, string $type, array $data): void
    {
        $this->db->query(
            "INSERT INTO notification_preferences
                (user_id, type, in_app, email, sms, push, quiet_hours_start, quiet_hours_end)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                in_app = VALUES(in_app),
                email = VALUES(email),
                sms = VALUES(sms),
                push = VALUES(push),
                quiet_hours_start = VALUES(quiet_hours_start),
                quiet_hours_end = VALUES(quiet_hours_end),
                updated_at = NOW()",
            [
                $userId,
                $type,
                (int)($data['in_app'] ?? 0),
                (int)($data['email'] ?? 0),
                (int)($data['sms'] ?? 0),
                (int)($data['push'] ?? 0),
                $data['quiet_hours_start'] ?: null,
                $data['quiet_hours_end'] ?: null,
            ]
        );
    }

    public function addDeliveryLog(array $data): int
    {
        return $this->db->insert('notification_delivery_logs', $data);
    }

    public function queueRealtimeEvent(int $userId, string $eventName, array $payload): int
    {
        return $this->db->insert('notification_realtime_events', [
            'user_id' => $userId,
            'event_name' => $eventName,
            'payload_json' => json_encode($payload),
        ]);
    }

    public function registerPushSubscription(int $userId, array $data): int
    {
        $this->db->query(
            "INSERT INTO notification_push_subscriptions
                (user_id, endpoint, p256dh_key, auth_token, user_agent, is_active, last_seen_at)
             VALUES (?, ?, ?, ?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                p256dh_key = VALUES(p256dh_key),
                auth_token = VALUES(auth_token),
                user_agent = VALUES(user_agent),
                is_active = 1,
                last_seen_at = NOW(),
                updated_at = NOW()",
            [
                $userId,
                $data['endpoint'] ?? '',
                $data['p256dh_key'] ?? '',
                $data['auth_token'] ?? '',
                $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    public function user(int $userId): ?object
    {
        return $this->db->fetch("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL", [$userId]);
    }
}
