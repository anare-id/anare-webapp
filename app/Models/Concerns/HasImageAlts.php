<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasImageAlts
{
    public function initializeHasImageAlts(): void
    {
        foreach (['image', 'catalog_image', 'comparison_before', 'comparison_after', 'home_image', 'schedule_image', 'mobile_image'] as $field) {
            if (in_array($field, $this->fillable, true)) $this->mergeFillable([$field.'_alt', $field.'_alt_en']);
        }
    }
}
