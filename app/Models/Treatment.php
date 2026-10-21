<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Treatment extends Model
{
    use \App\Models\Concerns\HasPublication;

    protected $fillable = ['service', 'category_id', 'slug', 'title', 'title_en', 'description', 'description_en', 'image', 'catalog_image', 'catalog_image_position', 'image_position', 'subtitle', 'subtitle_en', 'duration', 'duration_en', 'pre', 'pre_en', 'post', 'post_en', 'downtime', 'downtime_en', 'status', 'sort_order', 'comparison_before', 'comparison_after', 'comparison_position', 'comparison_illustrative', 'legacy'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'comparison_illustrative' => 'boolean', 'legacy' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
