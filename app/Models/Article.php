<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Article extends Model
{
    use \App\Models\Concerns\HasPublication;

    protected $fillable = ['category_id', 'slug', 'title', 'title_en', 'description', 'description_en', 'body', 'body_en', 'image', 'author', 'date', 'featured', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['featured' => 'boolean', 'date' => 'date:Y-m-d', 'sort_order' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
