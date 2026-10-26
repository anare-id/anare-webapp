<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Article;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CmsRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $record = $this->resource;
        $data = \App\Support\OptimizedImages::record($record->only($record->getFillable()));
        $data['id'] = (string) $record->getKey();
        $data['created_at'] = $record->created_at?->toIso8601String();
        $data['updated_at'] = $record->updated_at?->toIso8601String();
        if ($record instanceof Treatment || $record instanceof Article) {
            $data['category'] = $record->category?->slug;
        }
        if ($record instanceof Article) {
            $data['date'] = $record->date?->format('Y-m-d');
        }
        return $data;
    }
}
