<?php

declare(strict_types=1);

namespace App\Support;

final class OptimizedImages
{
    private static ?array $manifest = null;

    public static function path(?string $path): ?string
    {
        self::$manifest ??= is_file(public_path('images/optimized/manifest.json'))
            ? json_decode(file_get_contents(public_path('images/optimized/manifest.json')), true, 512, JSON_THROW_ON_ERROR) : [];
        return self::$manifest[$path] ?? $path;
    }

    public static function record(array $record): array
    {
        foreach (['image', 'catalog_image', 'home_image', 'home_source', 'schedule_image', 'mobile_image', 'comparison_before', 'comparison_after'] as $field) {
            if (isset($record[$field])) {
                $record[$field] = self::path($record[$field]);
            }
        }
        return $record;
    }
}
