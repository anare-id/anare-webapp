<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $images = [
        'articles' => ['image'], 'treatments' => ['image', 'catalog_image', 'comparison_before', 'comparison_after'],
        'doctors' => ['image', 'home_image', 'schedule_image'], 'hero_slides' => ['image', 'mobile_image'], 'services' => ['image'],
    ];

    public function up(): void
    {
        foreach ($this->images as $table => $images) {
            Schema::table($table, function (Blueprint $blueprint) use ($images, $table): void {
                foreach ($images as $image) {
                    $blueprint->string($image.'_alt', 500)->nullable();
                    $blueprint->string($image.'_alt_en', 500)->nullable();
                }
                if (in_array($table, ['articles', 'treatments', 'doctors'], true)) {
                    $blueprint->string('seo_title')->nullable();
                    $blueprint->string('seo_title_en')->nullable();
                    $blueprint->text('meta_description')->nullable();
                    $blueprint->text('meta_description_en')->nullable();
                    $blueprint->boolean('is_indexable')->default(true);
                }
                if ($table === 'articles') $blueprint->string('body_format', 10)->default('text');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->images as $table => $images) {
            $columns = [];
            foreach ($images as $image) array_push($columns, $image.'_alt', $image.'_alt_en');
            if (in_array($table, ['articles', 'treatments', 'doctors'], true)) array_push($columns, 'seo_title', 'seo_title_en', 'meta_description', 'meta_description_en', 'is_indexable');
            if ($table === 'articles') $columns[] = 'body_format';
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($columns));
        }
    }
};
