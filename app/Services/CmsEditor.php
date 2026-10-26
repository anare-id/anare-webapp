<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;

final class CmsEditor
{
    public function __construct(private readonly CmsCollections $collections, private readonly ImageOptimizer $images)
    {
    }

    public function save(string $collection, ?string $id, array $attributes, int $userId): void
    {
        DB::transaction(function () use ($collection, $id, $attributes, $userId): void {
            $user = $this->actor($userId);
            $record = $id === null ? $this->collections->query($collection)->getModel()->newInstance() : $this->collections->query($collection)->lockForUpdate()->findOrFail($id);
            if (in_array($collection, ['skin', 'shape', 'articles'], true)) {
                $categoryId = $attributes['category_id'] ?? $record->category_id;
                Category::query()->lockForUpdate()->findOrFail($categoryId);
            }
            abort_unless(CmsAccess::content($user, $collection, $id === null ? 'create' : 'update', $record, $attributes['status'] ?? null), 403);
            $record->fill($attributes + $this->collections->attributes($collection));
            $record->save();
            $this->activity($userId, ($id === null ? 'Menambahkan ' : 'Memperbarui ').$collection.': '.$record->title);
        });
    }

    public function delete(string $collection, string $id, int $userId): void
    {
        DB::transaction(function () use ($collection, $id, $userId): void {
            $user = $this->actor($userId);
            $record = $this->collections->query($collection)->lockForUpdate()->findOrFail($id);
            abort_unless(CmsAccess::content($user, $collection, 'delete', $record), 403);
            $title = $record->title;
            $record->delete();
            $this->activity($userId, 'Menghapus '.$collection.': '.$title);
        });
    }

    public function duplicate(string $collection, string $id, int $userId): void
    {
        DB::transaction(function () use ($collection, $id, $userId): void {
            $user = $this->actor($userId);
            $module = CmsAccess::module($collection);
            abort_unless(CmsAccess::can($user, $module.'.view') && CmsAccess::can($user, $module.'.create'), 403);
            $original = $this->collections->query($collection)->lockForUpdate()->findOrFail($id);
            $copy = $original->replicate();
            $copy->title = mb_substr($original->title.' (Salinan)', 0, 255);
            $copy->status = 'draft';
            if (in_array('featured', $copy->getFillable(), true)) {
                $copy->featured = false;
            }
            if (in_array('slug', $copy->getFillable(), true)) {
                $base = mb_substr($original->slug, 0, 140).'-copy';
                $slug = $base;
                for ($number = 2; $this->collections->query($collection)->where('slug', $slug)->exists(); $number++) {
                    $slug = $base.'-'.$number;
                }
                $copy->slug = $slug;
            }
            $copy->sort_order = $original->sort_order + 1;
            $copy->save();
            $this->activity($userId, 'Menduplikasi '.$collection.': '.$original->title);
        });
    }

    public function saveCategory(string $group, ?string $id, array $attributes, int $userId): void
    {
        DB::transaction(function () use ($group, $id, $attributes, $userId): void {
            $user = $this->actor($userId);
            $category = $id === null ? new Category(['group' => $group]) : Category::where('group', $group)->lockForUpdate()->findOrFail($id);
            abort_unless(CmsAccess::category($user, $group, 'update', $id === null ? null : $category), 403);
            $oldSlug = $category->slug;
            $category->fill($attributes)->save();
            $setting = SiteSetting::where('group', $group)->lockForUpdate()->first();
            if ($setting && ($setting->values['default_category'] ?? null) === $oldSlug && $oldSlug !== $category->slug) {
                $setting->update(['values' => array_replace($setting->values, ['default_category' => $category->slug])]);
            }
            $this->activity($userId, ($id === null ? 'Menambahkan' : 'Memperbarui').' kategori '.$group.': '.$category->title);
        });
    }

    public function deleteCategory(string $group, string $id, int $userId): void
    {
        DB::transaction(function () use ($group, $id, $userId): void {
            $category = Category::where('group', $group)->lockForUpdate()->findOrFail($id);
            abort_unless(CmsAccess::category($this->actor($userId), $group, 'delete', $category), 403);
            if ($category->treatments()->exists() || $category->articles()->exists()) {
                throw ValidationException::withMessages(['category' => 'Kategori masih digunakan. Pindahkan atau hapus kontennya terlebih dahulu.']);
            }
            $setting = SiteSetting::where('group', $group)->lockForUpdate()->first();
            if ($setting && ($setting->values['default_category'] ?? null) === $category->slug) {
                $replacement = Category::where('group', $group)->whereKeyNot($category->id)->orderBy('sort_order')->first();
                if ($replacement === null) {
                    throw ValidationException::withMessages(['category' => 'Kategori default terakhir tidak dapat dihapus.']);
                }
                $setting->update(['values' => array_replace($setting->values, ['default_category' => $replacement->slug])]);
            }
            $category->delete();
            $this->activity($userId, 'Menghapus kategori '.$group.': '.$category->title);
        });
    }

    public function settings(string $group, array $values, int $userId): void
    {
        DB::transaction(function () use ($group, $values, $userId): void {
            abort_unless(CmsAccess::settings($this->actor($userId), $group), 403);
            $setting = SiteSetting::where('group', $group)->lockForUpdate()->first();
            if ($setting === null) {
                SiteSetting::create(['group' => $group, 'values' => $values]);
            } else {
                $setting->update(['values' => array_replace($setting->values, $values)]);
            }
            $this->activity($userId, 'Memperbarui pengaturan '.$group);
        });
    }

    public function upload(UploadedFile $image, int $userId): string
    {
        abort_unless(CmsAccess::can($this->actor($userId), 'media.upload'), 403);
        $optimized = $this->images->encode($image->getPathname());
        $path = 'cms/'.Str::random(40).'.'.$optimized['extension'];
        if (!Storage::disk('public')->put($path, $optimized['bytes'])) {
            throw new \RuntimeException('Gambar gagal disimpan ke storage.');
        }
        try {
            DB::transaction(fn () => $this->activity($userId, 'Mengunggah foto: '.basename($path)));
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
        return '/storage/'.$path;
    }

    private function actor(int $userId): User
    {
        $user = User::query()->findOrFail($userId);
        abort_unless(CmsAccess::can($user, 'admin.access'), 403);
        return $user;
    }

    private function activity(int $userId, string $title): void
    {
        Activity::create(['user_id' => $userId, 'title' => mb_substr($title, 0, 255)]);
    }
}
