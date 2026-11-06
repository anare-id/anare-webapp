<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasContentFormats;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Treatment extends Model
{
    use \App\Models\Concerns\HasPublication;
    use \App\Models\Concerns\HasSeo;
    use \App\Models\Concerns\HasImageAlts;
    use HasContentFormats;

    protected $fillable = ['service', 'category_id', 'slug', 'title', 'title_en', 'description', 'description_en', 'image', 'catalog_image', 'catalog_image_position', 'image_position', 'subtitle', 'subtitle_en', 'duration', 'duration_en', 'pre', 'pre_en', 'post', 'post_en', 'downtime', 'downtime_en', 'status', 'sort_order', 'show_comparison', 'comparison_before', 'comparison_after', 'comparison_before_2', 'comparison_after_2', 'comparison_position', 'comparison_illustrative', 'legacy'];

    protected $attributes = ['show_comparison' => false];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'show_comparison' => 'boolean', 'comparison_illustrative' => 'boolean', 'legacy' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
