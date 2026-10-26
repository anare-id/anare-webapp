<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;

final class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CmsAccess::can($this->user(), 'media.upload');
    }

    public function rules(): array
    {
        return ['image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'mimetypes:image/jpeg,image/png,image/webp,image/gif', 'max:2048', static function (string $attribute, mixed $value, \Closure $fail): void {
            if (!$value instanceof \Illuminate\Http\UploadedFile || !$value->isValid()) {
                return;
            }
            $size = @getimagesize($value->getPathname());
            if ($size && $size[0] * $size[1] > (int) config('images.upload_max_pixels')) {
                $fail('Gambar maksimal 12 megapiksel. Kecilkan dimensinya sebelum upload.');
                return;
            }
            $memory = ini_get('memory_limit');
            if ($size && $memory !== '-1') {
                $unit = strtolower(substr($memory, -1));
                $limit = (int) $memory * match ($unit) {
                    'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1
                };
                if ($size[0] * $size[1] * 12 + 33554432 > $limit - memory_get_usage(true)) {
                    $fail('Dimensi gambar terlalu besar untuk diproses server. Kecilkan gambar lalu upload ulang.');
                }
            }
        }]];
    }
}
