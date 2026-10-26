<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveCategoryRequest;
use App\Http\Requests\SaveContentRequest;
use App\Http\Requests\SaveSettingsRequest;
use App\Http\Requests\UploadImageRequest;
use App\Services\CmsContent;
use App\Services\CmsEditor;
use App\Services\CmsAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminContentController extends Controller
{
    public function __construct(private readonly CmsContent $content, private readonly CmsEditor $editor)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->content->state($request->user()))->header('Cache-Control', 'no-store, private');
    }

    public function store(SaveContentRequest $request, string $collection): JsonResponse
    {
        $this->editor->save($collection, null, $request->validated(), (int) $request->user()->id);
        return $this->state($request, $this->label($collection).' berhasil ditambahkan.');
    }

    public function update(SaveContentRequest $request, string $collection, string $id): JsonResponse
    {
        $this->editor->save($collection, $id, $request->validated(), (int) $request->user()->id);
        return $this->state($request, $this->label($collection).' berhasil diperbarui.');
    }

    public function destroy(Request $request, string $collection, string $id): JsonResponse
    {
        abort_unless(CmsAccess::can($request->user(), CmsAccess::module($collection).'.delete'), 403);
        $this->editor->delete($collection, $id, (int) $request->user()->id);
        return $this->state($request, $this->label($collection).' berhasil dihapus.');
    }

    public function duplicate(Request $request, string $collection, string $id): JsonResponse
    {
        $module = CmsAccess::module($collection);
        abort_unless(CmsAccess::can($request->user(), $module.'.view') && CmsAccess::can($request->user(), $module.'.create'), 403);
        $this->editor->duplicate($collection, $id, (int) $request->user()->id);
        return $this->state($request, 'Salinan '.$this->label($collection).' berhasil dibuat sebagai draft.');
    }

    public function categoryStore(SaveCategoryRequest $request, string $group): JsonResponse
    {
        $this->editor->saveCategory($group, null, $request->validated(), (int) $request->user()->id);
        return $this->state($request, 'Kategori berhasil ditambahkan.');
    }

    public function categoryUpdate(SaveCategoryRequest $request, string $group, string $id): JsonResponse
    {
        $this->editor->saveCategory($group, $id, $request->validated(), (int) $request->user()->id);
        return $this->state($request, 'Kategori berhasil diperbarui.');
    }

    public function categoryDestroy(Request $request, string $group, string $id): JsonResponse
    {
        abort_unless(CmsAccess::can($request->user(), CmsAccess::group($group).'.delete'), 403);
        $this->editor->deleteCategory($group, $id, (int) $request->user()->id);
        return $this->state($request, 'Kategori berhasil dihapus.');
    }

    public function settings(SaveSettingsRequest $request, string $group): JsonResponse
    {
        $this->editor->settings($group, $request->validated(), (int) $request->user()->id);
        return $this->state($request, 'Pengaturan berhasil disimpan.');
    }

    public function upload(UploadImageRequest $request): JsonResponse
    {
        $url = $this->editor->upload($request->file('image'), (int) $request->user()->id);
        return response()->json(['url' => $url, 'message' => 'Gambar berhasil diunggah.'], 201);
    }

    private function label(string $collection): string
    {
        return match ($collection) {
            'skin', 'shape' => 'Treatment',
            'doctors' => 'Profil dokter',
            'articles' => 'Artikel',
            'services' => 'Layanan',
            'reviews' => 'Ulasan',
            default => 'Banner',
        };
    }

    private function state(Request $request, string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'state' => $this->content->state($request->user())])->header('Cache-Control', 'no-store, private');
    }
}
