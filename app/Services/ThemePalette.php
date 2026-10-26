<?php

declare(strict_types=1);

namespace App\Services;

final class ThemePalette
{
    public static function schema(): array
    {
        return config('theme.groups');
    }

    public static function defaults(): array
    {
        $defaults = [];
        foreach (self::schema() as $group => $definition) {
            foreach ($definition['defaults'] as $key => $value) {
                $defaults[$group.'_'.$key] = $value;
            }
        }
        return $defaults;
    }

    public static function resolve(array $saved): array
    {
        $values = self::defaults();
        foreach ($values as $key => $default) {
            if (isset($saved[$key]) && is_string($saved[$key]) && preg_match('/^#[0-9a-fA-F]{6}$/D', $saved[$key])) {
                $values[$key] = strtolower($saved[$key]);
            }
        }
        return $values;
    }

    public static function css(array $saved): string
    {
        $values = self::resolve($saved);
        $rules = [];
        foreach (self::schema() as $group => $definition) {
            $variables = [];
            foreach ($definition['defaults'] as $key => $default) {
                $variables[] = '--'.str_replace('_', '-', $key).':'.$values[$group.'_'.$key];
            }
            $selector = match ($group) {
                'shared' => ':root',
                'home' => 'body:has(.anare-home-hero)',
                default => 'body.'.$group.'.treatment-page',
            };
            $rules[] = $selector.'{'.implode(';', $variables).'}';
            if (in_array($group, ['skin', 'shape'], true)) {
                $rules[] = 'body.'.$group.'{--accent:'.$values[$group.'_accent'].';--pale:'.$values[$group.'_pale'].';--background:'.$values[$group.'_background'].'}';
            }
        }
        return implode("\n", $rules);
    }
}
