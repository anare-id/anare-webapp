<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Service extends Model
{
    use \App\Models\Concerns\HasPublication;
    use \App\Models\Concerns\HasContentFormats;
    use \App\Models\Concerns\HasImageAlts;

    protected $fillable = ['title', 'title_en', 'description', 'description_en', 'image', 'url', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
