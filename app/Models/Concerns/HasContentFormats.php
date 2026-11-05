<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasContentFormats
{
    public function initializeHasContentFormats(): void
    {
        $this->mergeFillable(['content_formats']);
        $this->mergeCasts(['content_formats' => 'array']);
    }
}
