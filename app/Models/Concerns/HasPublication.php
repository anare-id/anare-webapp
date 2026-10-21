<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasPublication
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
