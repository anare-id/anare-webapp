<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class CmsAccess
{
    public static function permissions(): array
    {
        return config('cms-access.permissions');
    }

    public static function roleDefaults(): array
    {
        return config('cms-access.roles');
    }

    public static function module(string $collection): string
    {
        return match ($collection) {
            'services', 'reviews', 'hero-home' => 'home',
            'hero-skin' => 'skin',
            'hero-shape' => 'shape',
            'hero-doctors' => 'doctors',
            'home', 'skin', 'shape', 'doctors', 'articles' => $collection,
            default => abort(404),
        };
    }

    public static function group(string $group): string
    {
        return match ($group) {
            'contact', 'social' => 'settings',
            'home', 'skin', 'shape', 'doctors', 'articles' => $group,
            default => abort(404),
        };
    }

    public static function section(string $section): string
    {
        return match ($section) {
            'dashboard' => 'dashboard.view',
            'beranda' => 'home.view',
            'skin' => 'skin.view',
            'shape' => 'shape.view',
            'dokter' => 'doctors.view',
            'artikel' => 'articles.view',
            'pengaturan' => 'settings.view',
            default => abort(404),
        };
    }

    public static function landing(User $user): string
    {
        if (self::can($user, 'dashboard.view')) {
            return route('admin.dashboard');
        }
        foreach (['beranda', 'skin', 'shape', 'dokter', 'artikel', 'pengaturan'] as $section) {
            if (self::can($user, self::section($section))) {
                return route('admin.section', $section);
            }
        }
        if ($user->hasRole('super-admin', 'web')) {
            foreach (['users', 'roles'] as $module) {
                if (self::can($user, $module.'.manage')) {
                    return route('admin.'.$module.'.index');
                }
            }
        }
        return route('admin.content');
    }

    public static function can(?User $user, string $permission): bool
    {
        return $user !== null && $user->is_active && $user->can($permission);
    }

    public static function content(?User $user, string $collection, string $action, ?Model $record = null, mixed $status = null): bool
    {
        $module = self::module($collection);
        return self::can($user, $module.'.'.$action)
            && (($record?->status !== 'published' && $status !== 'published') || self::can($user, $module.'.publish'));
    }

    public static function category(?User $user, string $group, string $action, ?Category $category = null): bool
    {
        $module = self::group($group);
        if (!self::can($user, $module.'.'.$action)) {
            return false;
        }
        if ($category === null || self::can($user, $module.'.publish')) {
            return true;
        }
        // Relabeling a used category and changing a default category both affect the public site.
        return $category->treatments()->where('status', 'published')->lockForUpdate()->first() === null
            && $category->articles()->where('status', 'published')->lockForUpdate()->first() === null
            && SiteSetting::where('group', $group)->where('values->default_category', $category->slug)->lockForUpdate()->first() === null;
    }

    public static function settings(?User $user, string $group): bool
    {
        $module = self::group($group);
        return self::can($user, $module.'.update')
            && ($module === 'settings' || self::can($user, $module.'.publish'));
    }
}
