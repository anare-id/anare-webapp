<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Category extends Model
{
    protected $fillable = ['group', 'slug', 'title', 'title_en', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
