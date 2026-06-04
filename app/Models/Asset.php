<?php
namespace App\Models;

class Asset
{
    public static function statusLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }

    public static function warrantyState(?string $expiry): array
    {
        if (!$expiry) {
            return ['label' => 'Not recorded', 'class' => 'secondary', 'days' => null];
        }

        $days = (int)ceil((strtotime($expiry) - time()) / 86400);
        if ($days < 0) {
            return ['label' => 'Expired', 'class' => 'danger', 'days' => $days];
        }
        if ($days <= 30) {
            return ['label' => 'Expiring soon', 'class' => 'warning text-dark', 'days' => $days];
        }
        if ($days <= 90) {
            return ['label' => 'Review soon', 'class' => 'info', 'days' => $days];
        }

        return ['label' => 'Covered', 'class' => 'success', 'days' => $days];
    }
}
