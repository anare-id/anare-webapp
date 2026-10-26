<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\WebLink;
use App\Rules\LocalImage;
use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CmsAccess::settings($this->user(), (string) $this->route('group'));
    }

    public function rules(): array
    {
        $group = (string) $this->route('group');
        $text = ['sometimes', 'nullable', 'string', 'max:2000'];
        $rules = match ($group) {
            'home' => array_fill_keys(['services_title', 'services_title_en', 'doctors_title', 'doctors_title_en', 'doctors_description', 'doctors_description_en', 'doctors_invitation', 'doctors_invitation_en', 'instagram_title', 'instagram_title_en', 'reviews_title', 'reviews_title_en', 'location_title', 'location_title_en'], $text) + [
                'slide_interval' => ['sometimes', 'required', 'integer', 'between:2,60'],
                'controls_style' => ['sometimes', Rule::in(['plain', 'glass'])],
            ],
            'contact' => array_fill_keys(['name', 'name_en', 'address', 'address_en', 'days', 'days_en', 'hours', 'hours_en'], $text) + [
                'email' => ['sometimes', 'nullable', 'email', 'max:255'],
                'whatsapp' => ['sometimes', 'required', 'regex:/^[1-9][0-9]{7,14}$/D'],
                'maps' => ['sometimes', 'nullable', 'string', 'max:2048', new WebLink()],
                'map_embed' => ['sometimes', 'nullable', 'url:https', 'max:2048', 'regex:~^https://(?:www\.)?(?:google\.com/maps/embed(?:\?|/)|maps\.google\.com/maps\?)~'],
                'location_image' => ['sometimes', 'nullable', 'string', 'max:1024', new LocalImage()],
            ],
            'social' => array_fill_keys(['clinic', 'shape'], ['sometimes', 'nullable', 'url:https', 'max:2048', new WebLink()]) + ['skin' => ['prohibited']],
            'skin', 'shape' => array_fill_keys(['name', 'name_en', 'title', 'title_en', 'description', 'description_en'], $text) + [
                'per_page' => ['sometimes', 'required', 'integer', 'between:1,100'],
                'pagination_slots' => ['sometimes', 'required', 'integer', 'between:1,20'],
                'show_pagination' => ['sometimes', 'boolean'],
                'default_category' => ['sometimes', 'required', 'string', Rule::exists('categories', 'slug')->where('group', $group)],
            ],
            'doctors' => array_fill_keys(['title', 'title_en', 'description', 'description_en'], $text),
            default => abort(404),
        };
        return $rules;
    }
}
