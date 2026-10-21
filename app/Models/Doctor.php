<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model
{
    use \App\Models\Concerns\HasPublication;

    protected $fillable = ['slug', 'title', 'title_en', 'description', 'description_en', 'image', 'home_image', 'home_source', 'schedule_image', 'specialty', 'specialty_en', 'schedule', 'schedule_en', 'education', 'education_en', 'expertise', 'expertise_en', 'featured', 'status', 'sort_order', 'home_sort_order', 'schedule_width', 'schedule_height', 'home_image_width', 'home_image_height'];

    protected function casts(): array
    {
        return ['featured' => 'boolean', 'sort_order' => 'integer', 'home_sort_order' => 'integer', 'schedule_width' => 'integer', 'schedule_height' => 'integer', 'home_image_width' => 'integer', 'home_image_height' => 'integer'];
    }
}
