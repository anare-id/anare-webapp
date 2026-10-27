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

    public static function presets(): array
    {
        $presets = ['default' => ['label' => 'Default', 'values' => self::defaults(), 'swatches' => ['#75a5c1', '#a9977f', '#898989', '#f4f8fa']]];
        foreach (config('theme.presets', []) as $id => $palette) {
            $values = [];
            foreach (self::schema() as $group => $definition) {
                foreach ($definition['defaults'] as $key => $default) {
                    $values[$group.'_'.$key] = match ($key) {
                        'accent', 'treatment_comparison_label' => $palette['accent'],
                        'ink', 'treatment_label', 'header_text' => $palette['ink'],
                        'pale', 'treatment_card', 'treatment_category', 'treatment_soft', 'treatment_panel', 'header_background' => $palette['pale'],
                        'canvas', 'background', 'catalog_background', 'treatment_comparison' => $palette['background'],
                        'footer', 'treatment_footer' => $palette['footer'],
                        'button', 'neutral_button' => $palette['button'],
                        'button_hover', 'neutral_button_hover' => $palette['button_hover'],
                        'treatment_secondary', 'treatment_muted' => $palette['secondary'],
                        'treatment_border' => $palette['border'],
                        'treatment_disabled' => $palette['disabled'],
                        default => $default,
                    };
                }
            }
            $presets[$id] = ['label' => $palette['label'], 'values' => $values, 'swatches' => [$palette['accent'], $palette['ink'], $palette['pale'], $palette['footer']]];
        }
        return $presets;
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
                'home' => 'body:not(.treatment-page)',
                default => 'body.'.$group.'.treatment-page',
            };
            $rules[] = $selector.'{'.implode(';', $variables).'}';
        }
        return implode("\n", $rules);
    }
}
