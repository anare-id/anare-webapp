<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Rules\LocalImage;
use App\Rules\WebLink;
use App\Services\CmsCollections;
use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $collection = (string) $this->route('collection');
        $id = $this->route('id');
        $record = $id === null ? null : app(CmsCollections::class)->find($collection, (string) $id);
        return CmsAccess::content($this->user(), $collection, $id === null ? 'create' : 'update', $record, $this->input('status'));
    }

    protected function prepareForValidation(): void
    {
        $group = (string) $this->route('collection');
        if (in_array($group, ['skin', 'shape', 'articles'], true) && $this->has('category') && !$this->has('category_id')) {
            $this->merge(['category_id' => Category::where('group', $group)->where('slug', $this->input('category'))->value('id')]);
        }
    }

    public function rules(): array
    {
        $collection = (string) $this->route('collection');
        $model = app(CmsCollections::class)->query($collection)->getModel();
        $required = $this->isMethod('post') ? 'required' : 'sometimes';
        $rules = [
            'title' => [$required, 'required', 'string', 'max:255'],
            'title_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:50000'],
            'description_en' => ['sometimes', 'nullable', 'string', 'max:50000'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published'])],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ];
        if ($collection !== 'reviews') {
            $rules['image'] = ['sometimes', 'nullable', 'string', 'max:1024', new LocalImage()];
        }
        if (in_array($collection, ['skin', 'shape', 'doctors', 'articles'], true)) {
            $unique = Rule::unique($model->getTable(), 'slug')->ignore((int) $this->route('id'));
            if (in_array($collection, ['skin', 'shape'], true)) {
                $unique->where('service', $collection);
            }
            $rules['slug'] = [$required, 'required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $unique];
        }
        if (in_array($collection, ['skin', 'shape', 'articles'], true)) {
            $rules['category_id'] = [$required, 'required', 'integer', Rule::exists('categories', 'id')->where('group', $collection)];
        }
        if (in_array($collection, ['skin', 'shape'], true)) {
            foreach (['subtitle', 'duration'] as $field) {
                $this->translated($rules, $field, 255);
            }
            foreach (['pre', 'post', 'downtime'] as $field) {
                $this->translated($rules, $field, 50000);
            }
            foreach (['catalog_image', 'comparison_before', 'comparison_after'] as $field) {
                $rules[$field] = ['sometimes', 'nullable', 'string', 'max:1024', new LocalImage()];
            }
            foreach (['image_position', 'catalog_image_position', 'comparison_position'] as $field) {
                $rules[$field] = $this->position();
            }
            $rules['comparison_illustrative'] = ['sometimes', 'boolean'];
            $rules['legacy'] = ['sometimes', 'boolean'];
        }
        if ($collection === 'doctors') {
            foreach (['specialty', 'schedule', 'education', 'expertise'] as $field) {
                $this->translated($rules, $field, $field === 'specialty' ? 255 : 50000);
            }
            foreach (['home_image', 'home_source', 'schedule_image'] as $field) {
                $rules[$field] = ['sometimes', 'nullable', 'string', 'max:1024', new LocalImage()];
            }
            foreach (['schedule_width', 'schedule_height', 'home_image_width', 'home_image_height'] as $field) {
                $rules[$field] = ['sometimes', 'nullable', 'integer', 'min:1', 'max:20000'];
            }
            $rules['home_sort_order'] = ['sometimes', 'integer', 'min:0', 'max:1000000'];
        }
        if (in_array($collection, ['doctors', 'articles'], true)) {
            $rules['featured'] = ['sometimes', 'boolean'];
        }
        if ($collection === 'articles') {
            $this->translated($rules, 'body', 200000);
            $rules['author'] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules['date'] = ['sometimes', 'nullable', 'date_format:Y-m-d'];
        }
        if (str_starts_with($collection, 'hero-')) {
            $this->translated($rules, 'button', 255);
            $rules['mobile_image'] = ['sometimes', 'nullable', 'string', 'max:1024', new LocalImage()];
            $rules['position'] = $this->position();
            $rules['mobile_position'] = $this->position();
            $rules['team'] = ['sometimes', 'boolean'];
            $rules['fit'] = ['sometimes', Rule::in(['cover', 'contain'])];
            $rules['background'] = ['sometimes', 'nullable', 'regex:/^#[a-fA-F0-9]{3}(?:[a-fA-F0-9]{3})?$/D'];
        }
        if ($collection === 'reviews') {
            $rules['rating'] = ['sometimes', 'required', 'integer', 'between:1,5'];
        }
        if (in_array($collection, ['services', 'reviews'], true) || str_starts_with($collection, 'hero-')) {
            $rules['url'] = ['sometimes', 'nullable', 'string', 'max:2048', new WebLink()];
        }
        return $rules;
    }

    private function translated(array &$rules, string $field, int $max): void
    {
        foreach ([$field, $field.'_en'] as $name) {
            $rules[$name] = ['sometimes', 'nullable', 'string', 'max:'.$max];
        }
    }

    private function position(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^(?:center|left|right|top|bottom|\d{1,3}(?:\.\d+)?%)(?: (?:center|left|right|top|bottom|\d{1,3}(?:\.\d+)?%))?$/D'];
    }
}
