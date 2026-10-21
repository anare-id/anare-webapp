<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Models\Doctor;
use App\Models\HeroSlide;
use App\Models\Review;
use App\Models\Service;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CmsCollections
{
    public const NAMES = 'skin|shape|doctors|articles|services|reviews|hero-home|hero-skin|hero-shape|hero-doctors';

    public function query(string $collection): Builder
    {
        return match ($collection) {
            'skin', 'shape' => Treatment::query()->where('service', $collection),
            'doctors' => Doctor::query(),
            'articles' => Article::query(),
            'services' => Service::query(),
            'reviews' => Review::query(),
            'hero-home', 'hero-skin', 'hero-shape', 'hero-doctors' => HeroSlide::query()->where('section', substr($collection, 5)),
            default => abort(404),
        };
    }

    public function find(string $collection, string $id): Model
    {
        abort_unless(ctype_digit($id), 404);
        return $this->query($collection)->findOrFail($id);
    }

    public function attributes(string $collection): array
    {
        return match ($collection) {
            'skin', 'shape' => ['service' => $collection],
            'hero-home', 'hero-skin', 'hero-shape', 'hero-doctors' => ['section' => substr($collection, 5)],
            default => [],
        };
    }
}
