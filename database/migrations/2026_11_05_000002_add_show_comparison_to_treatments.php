<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table): void {
            $table->boolean('show_comparison')->default(false);
        });

        DB::table('treatments')
            ->whereNotNull('comparison_before')
            ->whereNotNull('comparison_after')
            ->whereRaw("TRIM(comparison_before) <> ''")
            ->whereRaw("TRIM(comparison_after) <> ''")
            ->update(['show_comparison' => true]);
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table): void {
            $table->dropColumn('show_comparison');
        });
    }
};
