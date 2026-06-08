<?php
namespace App\Models;

class MaintenanceTask
{
    public static function workOrderLabel(object $task): string
    {
        return $task->wo_number ?? $task->work_order_number ?? ('WO-' . str_pad((string)$task->id, 4, '0', STR_PAD_LEFT));
    }

    public static function frequencyLabel(string $frequency): string
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'biweekly' => 'Biweekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi_annual' => 'Semi-Annual',
            'annual' => 'Annual',
        ][$frequency] ?? ucwords(str_replace('_', ' ', $frequency));
    }

    public static function nextDueDate(string $frequency, ?string $fromDate = null): string
    {
        $date = $fromDate ? strtotime($fromDate) : time();
        $modifier = [
            'daily' => '+1 day',
            'weekly' => '+1 week',
            'biweekly' => '+2 weeks',
            'monthly' => '+1 month',
            'quarterly' => '+3 months',
            'semi_annual' => '+6 months',
            'annual' => '+1 year',
        ][$frequency] ?? '+1 month';

        return date('Y-m-d', strtotime($modifier, $date));
    }

    public static function checklistFromText(string $text): ?string
    {
        $items = array_values(array_filter(array_map('trim', preg_split('/\R/', $text))));
        if (empty($items)) {
            return null;
        }

        return json_encode(array_map(fn(string $item) => [
            'label' => $item,
            'done' => false,
        ], $items));
    }

    public static function decodeChecklist(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
