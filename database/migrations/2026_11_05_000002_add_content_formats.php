<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['articles', 'treatments', 'doctors', 'hero_slides', 'services', 'reviews'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->json('content_formats')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['articles', 'treatments', 'doctors', 'hero_slides', 'services', 'reviews'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('content_formats'));
        }
    }
};
