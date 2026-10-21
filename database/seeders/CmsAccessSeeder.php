<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Services\CmsAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class CmsAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::transaction(function (): void {
            // Preserve edited permissions and intentionally deleted roles on subsequent seeds.
            if (SiteSetting::where('group', '_access_seed')->exists()) {
                return;
            }
            foreach (array_keys(CmsAccess::permissions()) as $name) {
                Permission::findOrCreate($name, 'web');
            }
            foreach (CmsAccess::roleDefaults() as $name => $permissions) {
                $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                if ($role->wasRecentlyCreated) {
                    $role->syncPermissions($permissions);
                }
            }
            SiteSetting::create(['group' => '_access_seed', 'values' => ['installed' => true]]);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
