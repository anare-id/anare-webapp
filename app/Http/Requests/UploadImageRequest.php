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
        return ['image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'mimetypes:image/jpeg,image/png,image/webp,image/gif', 'max:2048']];
    }
}
