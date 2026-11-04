<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasSeo
{
    public function initializeHasSeo(): void
    {
        $this->mergeFillable(['seo_title', 'seo_title_en', 'meta_description', 'meta_description_en', 'is_indexable']);
        $this->mergeCasts(['is_indexable' => 'boolean']);
    }
}
