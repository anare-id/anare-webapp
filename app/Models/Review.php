<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Review extends Model
{
    use \App\Models\Concerns\HasPublication;

    protected $fillable = ['title', 'title_en', 'description', 'description_en', 'rating', 'url', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'sort_order' => 'integer'];
    }
}
