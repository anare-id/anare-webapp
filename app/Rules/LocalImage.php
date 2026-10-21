<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class LocalImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || preg_match('~^/(images|storage)/[^?#\x00-\x1f]+\.(?:jpe?g|png|webp|gif|avif)$~iD', $value) !== 1 || str_contains($value, '..') || str_contains($value, '\\') || str_contains($value, '%')) {
            $fail('The :attribute must be a local image uploaded to this website.');
            return;
        }
        $path = str_starts_with($value, '/storage/') ? storage_path('app/public/'.substr($value, 9)) : public_path(ltrim($value, '/'));
        if (!is_file($path)) {
            $fail('The :attribute image does not exist.');
        }
    }
}
