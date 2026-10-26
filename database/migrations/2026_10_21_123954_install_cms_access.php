<?php

declare(strict_types=1);

use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\CmsAccessSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // MySQL commits DDL before the seeder runs; allow retry after a partial failure.
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true);
            });
        }
        app(CmsAccessSeeder::class)->run();
        // This one-time upgrade preserves access for accounts that existed before RBAC.
        // Accounts created after migration must be assigned a role explicitly.
        DB::transaction(function (): void {
            User::query()->orderBy('id')->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $user->assignRole('super-admin');
                }
            });
        });
    }

    public function down(): void
    {
        SiteSetting::where('group', '_access_seed')->delete();
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
