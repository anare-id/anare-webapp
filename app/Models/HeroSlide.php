<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class HeroSlide extends Model
{
    use \App\Models\Concerns\HasPublication;

    protected $fillable = ['section', 'title', 'title_en', 'description', 'description_en', 'image', 'mobile_image', 'position', 'mobile_position', 'button', 'button_en', 'url', 'team', 'fit', 'background', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['team' => 'boolean', 'sort_order' => 'integer'];
    }
}
