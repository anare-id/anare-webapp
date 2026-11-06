<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            foreach (['comparison_before_2', 'comparison_after_2'] as $field) {
                $table->string($field, 1024)->nullable();
                $table->string($field.'_alt', 500)->nullable();
                $table->string($field.'_alt_en', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn(['comparison_before_2', 'comparison_before_2_alt', 'comparison_before_2_alt_en', 'comparison_after_2', 'comparison_after_2_alt', 'comparison_after_2_alt_en']);
        });
    }
};
