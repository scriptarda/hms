<?php
namespace App\Services;

use App\Repositories\NotificationRepository;

class NotificationService
{
    private NotificationRepository $repo;
    private array $mailConfig;

    public function __construct()
    {
        $this->repo = new NotificationRepository();
        $this->mailConfig = file_exists(CONFIG_PATH . '/mail.php') ? require CONFIG_PATH . '/mail.php' : [];
    }

    public static function types(): array
    {
        return [
            NOTIFY_TICKET_ASSIGNED => 'Ticket assigned',
            NOTIFY_TICKET_UPDATED => 'Ticket updated',
            NOTIFY_TICKET_RESOLVED => 'Ticket resolved',
            NOTIFY_TICKET_ESCALATED => 'Ticket escalated',
            NOTIFY_SLA_WARNING => 'SLA warning',
            NOTIFY_SLA_BREACHED => 'SLA breached',
            NOTIFY_APPROVAL_REQUIRED => 'Approval required',
            NOTIFY_MAINTENANCE_DUE => 'Maintenance due',
            NOTIFY_LOW_STOCK => 'Low stock',
            NOTIFY_REPORT_READY => 'Report ready',
            NOTIFY_REPORT_FAILED => 'Report failed',
            NOTIFY_SYSTEM => 'System',
        ];
    }

    public function center(int $userId, array $filters = []): array
    {
        return [
            'notifications' => $this->repo->listForUser($userId, $filters),
            'stats' => $this->repo->stats($userId),
            'filters' => $filters,
            'types' => self::types(),
        ];
    }

    public function unread(int $userId): array
    {
        return [
            'count' => $this->repo->unreadCount($userId),
            'list' => $this->repo->unread($userId),
        ];
    }

    public function preferences(int $userId): array
    {
        return $this->repo->preferences($userId, self::types());
    }

    public function savePreferences(int $userId, array $input): void
    {
        $types = self::types();
        foreach ($types as $type => $label) {
            $row = $input['preferences'][$type] ?? [];
            $this->repo->savePreference($userId, $type, [
                'in_app' => isset($row['in_app']) ? 1 : 0,
                'email' => isset($row['email']) ? 1 : 0,
                'sms' => isset($row['sms']) ? 1 : 0,
                'push' => isset($row['push']) ? 1 : 0,
                'quiet_hours_start' => trim($row['quiet_hours_start'] ?? ''),
                'quiet_hours_end' => trim($row['quiet_hours_end'] ?? ''),
            ]);
        }
    }

    public function send(int $userId, string $type, string $title, string $message, string $link = '', array $options = []): int
    {
        $preference = $this->repo->preference($userId, $type);
        $severity = $options['severity'] ?? $this->severityForType($type);
        $notificationId = 0;
        $payload = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'severity' => $severity,
            'data' => $options['data'] ?? [],
        ];

        if ((int)$preference->in_app === 1) {
            $notificationId = $this->repo->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'channel' => 'in_app',
                'severity' => $severity,
                'data_json' => json_encode($options['data'] ?? []),
                'delivered_at' => date('Y-m-d H:i:s'),
            ]);

            $payload['id'] = $notificationId;
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->repo->addDeliveryLog([
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'channel' => 'in_app',
                'status' => 'sent',
                'provider' => 'hems',
                'payload_json' => json_encode($payload),
                'sent_at' => date('Y-m-d H:i:s'),
            ]);
            $this->repo->queueRealtimeEvent($userId, 'notification.created', $payload);
        }

        $quiet = $this->isQuietHours($preference);
        $this->dispatchExternalChannels($userId, $notificationId ?: null, $preference, $payload, $quiet);

        return $notificationId;
    }

    public function sendMany(array $userIds, string $type, string $title, string $message, string $link = '', array $options = []): void
    {
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            if ($userId > 0) {
                $this->send($userId, $type, $title, $message, $link, $options);
            }
        }
    }

    public function markRead(int $id, int $userId, bool $read = true): void
    {
        $this->repo->markRead($id, $userId, $read);
        $this->repo->queueRealtimeEvent($userId, 'notification.read_state_changed', [
            'id' => $id,
            'is_read' => $read,
            'count' => $this->repo->unreadCount($userId),
        ]);
    }

    public function markAllRead(int $userId): void
    {
        $this->repo->markAllRead($userId);
        $this->repo->queueRealtimeEvent($userId, 'notification.read_all', [
            'count' => 0,
        ]);
    }

    public function registerPushSubscription(int $userId, array $input): int
    {
        return $this->repo->registerPushSubscription($userId, $input);
    }

    private function dispatchExternalChannels(int $userId, ?int $notificationId, object $preference, array $payload, bool $quiet): void
    {
        $user = $this->repo->user($userId);
        if (!$user) {
            return;
        }

        if ((int)$preference->email === 1) {
            if ($quiet) {
                $this->deliverySkipped($notificationId, $userId, 'email', $payload, 'quiet_hours');
            } else {
                $this->logEmail($user->email, $payload);
                $this->repo->addDeliveryLog([
                    'notification_id' => $notificationId,
                    'user_id' => $userId,
                    'channel' => 'email',
                    'status' => 'sent',
                    'provider' => $this->mailConfig['driver'] ?? 'log',
                    'recipient' => $user->email,
                    'payload_json' => json_encode($payload),
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ((int)$preference->sms === 1) {
            $this->repo->addDeliveryLog([
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'channel' => 'sms',
                'status' => $quiet ? 'skipped' : 'queued',
                'provider' => 'sms_ready',
                'recipient' => $user->phone ?? '',
                'payload_json' => json_encode($payload),
                'error_message' => $quiet ? 'quiet_hours' : null,
            ]);
        }

        if ((int)$preference->push === 1) {
            $this->repo->addDeliveryLog([
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'channel' => 'push',
                'status' => $quiet ? 'skipped' : 'queued',
                'provider' => 'web_push_ready',
                'payload_json' => json_encode($payload),
                'error_message' => $quiet ? 'quiet_hours' : null,
            ]);
        }
    }

    private function deliverySkipped(?int $notificationId, int $userId, string $channel, array $payload, string $reason): void
    {
        $this->repo->addDeliveryLog([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'channel' => $channel,
            'status' => 'skipped',
            'provider' => $channel . '_ready',
            'payload_json' => json_encode($payload),
            'error_message' => $reason,
        ]);
    }

    private function logEmail(string $recipient, array $payload): void
    {
        $logDir = STORAGE_PATH . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] To: ' . $recipient . ' | ' . $payload['title'] . ' | ' . $payload['message'] . PHP_EOL;
        file_put_contents($logDir . '/notification_email.log', $line, FILE_APPEND);
    }

    private function isQuietHours(object $preference): bool
    {
        if (empty($preference->quiet_hours_start) || empty($preference->quiet_hours_end)) {
            return false;
        }

        $now = date('H:i:s');
        $start = $preference->quiet_hours_start;
        $end = $preference->quiet_hours_end;

        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        }

        return $now >= $start || $now <= $end;
    }

    private function severityForType(string $type): string
    {
        return match ($type) {
            NOTIFY_SLA_BREACHED, NOTIFY_TICKET_ESCALATED, NOTIFY_REPORT_FAILED => 'danger',
            NOTIFY_SLA_WARNING, NOTIFY_APPROVAL_REQUIRED, NOTIFY_MAINTENANCE_DUE, NOTIFY_LOW_STOCK => 'warning',
            NOTIFY_TICKET_RESOLVED, NOTIFY_REPORT_READY => 'success',
            default => 'info',
        };
    }
}
