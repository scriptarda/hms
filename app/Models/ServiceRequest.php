<?php
namespace App\Models;

class ServiceRequest
{
    public static function statusLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'secondary',
            'pending_approval' => 'warning text-dark',
            'approved' => 'success',
            'rejected' => 'danger',
            'fulfilling' => 'primary',
            'completed' => 'dark',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public static function workflowSteps(string $status): array
    {
        $steps = [
            'submitted' => ['label' => 'Submitted', 'icon' => 'bi-send-check'],
            'pending_approval' => ['label' => 'Approval', 'icon' => 'bi-shield-check'],
            'approved' => ['label' => 'Approved', 'icon' => 'bi-patch-check'],
            'fulfilling' => ['label' => 'Fulfillment', 'icon' => 'bi-tools'],
            'completed' => ['label' => 'Completed', 'icon' => 'bi-check2-circle'],
        ];

        $order = array_keys($steps);
        $current = match ($status) {
            'draft' => -1,
            'pending_approval' => 1,
            'approved' => 2,
            'fulfilling' => 3,
            'completed' => 4,
            'rejected', 'cancelled' => 1,
            default => 0,
        };

        foreach ($order as $index => $key) {
            $steps[$key]['state'] = $index < $current ? 'done' : ($index === $current ? 'current' : 'pending');
        }

        if (in_array($status, ['rejected', 'cancelled'], true)) {
            $steps[$status] = [
                'label' => self::statusLabel($status),
                'icon' => $status === 'rejected' ? 'bi-x-octagon' : 'bi-slash-circle',
                'state' => 'current',
            ];
        }

        return $steps;
    }
}
