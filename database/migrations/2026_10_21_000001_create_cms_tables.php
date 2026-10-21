<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username', 100)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 20);
            $table->string('slug', 160);
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['group', 'slug']);
            $table->timestamps();
        });
        Schema::create('treatments', function (Blueprint $table): void {
            $table->id();
            $table->string('service', 20);
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('slug', 160);
            $this->translated($table, ['title', 'subtitle', 'duration']);
            $this->translated($table, ['description', 'pre', 'post', 'downtime'], true);
            foreach (['image', 'catalog_image', 'comparison_before', 'comparison_after'] as $field) {
                $table->string($field, 1024)->nullable();
            }
            foreach (['image_position', 'catalog_image_position', 'comparison_position'] as $field) {
                $table->string($field, 100)->nullable();
            }
            $table->boolean('comparison_illustrative')->default(true);
            $table->boolean('legacy')->default(false);
            $this->publication($table);
            $table->unique(['service', 'slug']);
        });
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 160)->unique();
            $this->translated($table, ['title', 'specialty']);
            $this->translated($table, ['description', 'schedule', 'education', 'expertise'], true);
            foreach (['image', 'home_image', 'home_source', 'schedule_image'] as $field) {
                $table->string($field, 1024)->nullable();
            }
            foreach (['schedule_width', 'schedule_height', 'home_image_width', 'home_image_height'] as $field) {
                $table->unsignedInteger($field)->nullable();
            }
            $table->unsignedInteger('home_sort_order')->default(0);
            $table->boolean('featured')->default(false);
            $this->publication($table);
        });
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('slug', 160)->unique();
            $this->translated($table, ['title']);
            $this->translated($table, ['description', 'body'], true);
            $table->string('image', 1024)->nullable();
            $table->string('author')->nullable();
            $table->date('date')->nullable();
            $table->boolean('featured')->default(false);
            $this->publication($table);
        });
        Schema::create('hero_slides', function (Blueprint $table): void {
            $table->id();
            $table->string('section', 20);
            $this->translated($table, ['title', 'button']);
            $this->translated($table, ['description'], true);
            $table->string('image', 1024)->nullable();
            $table->string('mobile_image', 1024)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('mobile_position', 100)->nullable();
            $table->string('url', 2048)->nullable();
            $table->boolean('team')->default(false);
            $table->string('fit', 20)->default('cover');
            $table->string('background', 30)->nullable();
            $this->publication($table);
            $table->index(['section', 'status', 'sort_order']);
        });
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $this->translated($table, ['title']);
            $this->translated($table, ['description'], true);
            $table->string('image', 1024)->nullable();
            $table->string('url', 2048)->nullable();
            $this->publication($table);
        });
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $this->translated($table, ['title']);
            $this->translated($table, ['description'], true);
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('url', 2048)->nullable();
            $this->publication($table);
        });
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 40)->unique();
            $table->json('values');
            $table->timestamps();
        });
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activities', 'site_settings', 'reviews', 'services', 'hero_slides', 'articles', 'doctors', 'treatments', 'categories', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function translated(Blueprint $table, array $fields, bool $text = false): void
    {
        foreach ($fields as $field) {
            foreach ([$field, $field.'_en'] as $name) {
                if ($text) {
                    $table->longText($name)->nullable();
                } else {
                    $table->string($name)->nullable();
                }
            }
        }
    }

    private function publication(Blueprint $table): void
    {
        $table->string('status', 20)->default('draft')->index();
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    }
};
