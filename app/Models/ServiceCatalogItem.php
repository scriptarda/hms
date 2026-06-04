<?php
namespace App\Models;

class ServiceCatalogItem
{
    public static function typeLabel(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }

    public static function decodeSchema(?string $schema): array
    {
        if (!$schema) {
            return [];
        }

        $decoded = json_decode($schema, true);
        return is_array($decoded) ? $decoded : [];
    }
}
