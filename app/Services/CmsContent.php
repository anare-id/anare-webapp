<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\CmsRecordResource;
use App\Models\Activity;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class CmsContent
{
    public function state(User $user): array
    {
        $state = [];
        $collections = app(CmsCollections::class);
        foreach (['skin', 'shape', 'doctors', 'articles', 'services', 'reviews'] as $collection) {
            if (!CmsAccess::can($user, CmsAccess::module($collection).'.view')) {
                $state[$collection] = [];
                continue;
            }
            $query = $collections->query($collection);
            if (in_array($collection, ['skin', 'shape', 'articles'], true)) {
                $query->with('category');
            }
            $state[$collection] = $this->records($query);
        }
        $state += ['categories' => [], 'category_records' => [], 'heroes' => [], 'settings' => [], 'activity' => []];
        foreach (['skin', 'shape', 'articles'] as $group) {
            $records = CmsAccess::can($user, $group.'.view')
                ? Category::where('group', $group)->orderBy('sort_order')->orderBy('id')->get()
                : collect();
            $state['categories'][$group] = (object) $records->pluck('title', 'slug')->all();
            $state['category_records'][$group] = $records->map(fn (Category $category): array => [
                'id' => (string) $category->id, 'slug' => $category->slug, 'title' => $category->title,
                'title_en' => $category->title_en, 'sort_order' => $category->sort_order,
            ])->all();
        }
        foreach (['home', 'skin', 'shape', 'doctors'] as $section) {
            $state['heroes'][$section] = CmsAccess::can($user, $section.'.view')
                ? $this->records(HeroSlide::where('section', $section)) : [];
        }
        $settingGroups = array_values(array_filter(
            ['home', 'skin', 'shape', 'doctors', 'contact', 'social'],
            fn (string $group): bool => CmsAccess::can($user, CmsAccess::group($group).'.view')
        ));
        if ($settingGroups !== []) {
            $state['settings'] = SiteSetting::whereIn('group', $settingGroups)->pluck('values', 'group')->all();
        }
        if (CmsAccess::can($user, 'activity.view')) {
            $state['activity'] = Activity::latest('id')->limit(50)->get()->map(fn (Activity $activity): array => [
                'id' => (string) $activity->id, 'title' => $activity->title, 'date' => $activity->created_at->toIso8601String(),
            ])->all();
        }
        $state['access'] = [
            'permissions' => array_values(array_intersect(array_keys(CmsAccess::permissions()), $user->getAllPermissions()->pluck('name')->all())),
            'roles' => $user->getRoleNames()->values()->all(),
        ];
        return $state;
    }

    private function records(Builder $query): array
    {
        return $query->orderBy('sort_order')->orderBy('id')->get()->map(fn ($record): array => (new CmsRecordResource($record))->resolve())->all();
    }
}
