<?php

declare(strict_types=1);

namespace App\Services;

final class ContentFormatting
{
    public static function fields(string $collection): array
    {
        return array_merge(['description'], match ($collection) {
            'articles' => ['body'],
            'doctors' => ['education', 'expertise', 'schedule'],
            'skin', 'shape' => ['pre', 'post', 'downtime'],
            default => [],
        });
    }

    public static function settingsFields(string $group): array
    {
        return match ($group) {
            'home' => ['doctors_description', 'doctors_invitation'],
            'skin', 'shape', 'doctors' => ['description'],
            'contact' => ['address'],
            default => [],
        };
    }

    public static function rules(array $fields): array
    {
        $keys = array_merge($fields, array_map(fn (string $field): string => $field.'_en', $fields));
        return [
            'content_formats' => ['sometimes', 'array'.($keys === [] ? '' : ':'.implode(',', $keys)), function ($attribute, $value, $fail) use ($keys): void {
                if (is_array($value) && array_diff(array_keys($value), $keys) !== []) $fail('Unsupported rich content field.');
            }],
            'content_formats.*' => ['required', \Illuminate\Validation\Rule::in(['text', 'html'])],
        ];
    }

    public static function format(array $record, string $field): string
    {
        return $record['content_formats'][$field] ?? (in_array($field, ['body', 'body_en'], true) ? ($record['body_format'] ?? 'text') : 'text');
    }

    public static function localizedKey(array $record, string $field): string
    {
        return app()->getLocale() === 'en' && trim((string) ($record[$field.'_en'] ?? '')) !== '' ? $field.'_en' : $field;
    }

    public static function html(array $record, string $field): string
    {
        $key = self::localizedKey($record, $field);
        return app(ArticleHtml::class)->render((string) ($record[$key] ?? ''), self::format($record, $key));
    }

    public static function plain(string $value): string
    {
        return trim(html_entity_decode(strip_tags(preg_replace('~<(?:br\s*/?|/p|/div|/li|/h[1-6])>~i', "\n", $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function prepare(array $old, array $updates, array $fields): array
    {
        $formats = array_replace($old['content_formats'] ?? [], $updates['content_formats'] ?? []);
        if (array_key_exists('body_format', $updates) && in_array('body', $fields, true)) {
            foreach (['body', 'body_en'] as $key) {
                if (!array_key_exists($key, $updates['content_formats'] ?? [])) $formats[$key] = $updates['body_format'];
            }
        }
        foreach ($fields as $field) {
            foreach ([$field, $field.'_en'] as $key) {
                $before = self::format($old, $key);
                $after = $formats[$key] ?? $before;
                if (!array_key_exists($key, $updates) && $after !== $before && isset($old[$key])) {
                    $updates[$key] = $after === 'html' ? nl2br(e($old[$key])) : self::plain($old[$key]);
                }
                if (isset($updates[$key]) && $after === 'html') $updates[$key] = app(ArticleHtml::class)->sanitize($updates[$key]);
            }
        }
        if (isset($updates['content_formats']) || isset($updates['body_format'])) $updates['content_formats'] = $formats;
        return $updates;
    }
}
