<?php

namespace App\Support;

class LocalizedContent
{
    public static function field(array $record, string $field): string
    {
        $original = (string) ($record[$field] ?? '');
        $translation = (string) ($record[$field.'_en'] ?? '');
        return app()->getLocale() === 'en' && trim($translation) !== '' ? $translation : $original;
    }
}
