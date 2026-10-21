<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = (string) $this->route('group');
        $id = $this->route('id');
        $category = $id === null ? null : Category::where('group', $group)->findOrFail($id);
        return CmsAccess::category($this->user(), $group, 'update', $category);
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';
        return [
            'slug' => [$required, 'required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('categories', 'slug')->where('group', $this->route('group'))->ignore((int) $this->route('id'))],
            'title' => [$required, 'required', 'string', 'max:255'],
            'title_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
