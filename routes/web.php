<?php

use App\Http\Controllers\SkinTreatmentController;
use App\Http\Controllers\ShapeTreatmentController;
use App\Http\Controllers\InstagramFeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GoogleReviewsController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminPermissionController;
use App\Http\Middleware\EnsureAdmin;
use App\Services\CmsCollections;
use Illuminate\Support\Facades\Route;

Route::post('/language/{locale}', LocaleController::class)->name('locale.update');
Route::get('/', HomeController::class)->name('home');
Route::get('/instagram/feed', InstagramFeedController::class)->name('instagram.feed');
Route::get('/google/reviews', GoogleReviewsController::class)->name('google.reviews');
Route::get('/skin', [SkinTreatmentController::class, 'index'])->name('skin');
Route::get('/shape', [ShapeTreatmentController::class, 'index'])->name('shape');
Route::get('/skin/{slug}', [SkinTreatmentController::class, 'show'])->name('skin.detail');
Route::get('/shape/{slug}', [ShapeTreatmentController::class, 'show'])->name('shape.detail');
Route::get('/doctors', [WebsiteController::class, 'doctors'])->name('doctors');
Route::get('/doctors/{slug}', [WebsiteController::class, 'doctor'])->name('doctors.detail');
Route::get('/articles', [WebsiteController::class, 'articles'])->name('articles');
Route::get('/articles/{slug}', [WebsiteController::class, 'article'])->name('articles.detail');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/login', [AdminController::class, 'authenticate'])->middleware('throttle:10,1')->name('authenticate');
    Route::post('/logout', [AdminController::class, 'logout'])->middleware('auth')->name('logout');
    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->whereNumber('role')->name('roles.update');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->whereNumber('role')->name('roles.destroy');
        Route::get('/permissions', [AdminPermissionController::class, 'index'])->name('permissions.index');
        Route::put('/permissions/{role}', [AdminPermissionController::class, 'update'])->whereNumber('role')->name('permissions.update');
        Route::get('/content', [AdminContentController::class, 'index'])->name('content');
        Route::post('/content/{collection}', [AdminContentController::class, 'store'])->where('collection', CmsCollections::NAMES);
        Route::put('/content/{collection}/{id}', [AdminContentController::class, 'update'])->where('collection', CmsCollections::NAMES)->whereNumber('id');
        Route::delete('/content/{collection}/{id}', [AdminContentController::class, 'destroy'])->where('collection', CmsCollections::NAMES)->whereNumber('id');
        Route::post('/content/{collection}/{id}/duplicate', [AdminContentController::class, 'duplicate'])->where('collection', CmsCollections::NAMES)->whereNumber('id');
        Route::put('/settings/{group}', [AdminContentController::class, 'settings'])->where('group', 'home|contact|social|skin|shape|doctors');
        Route::post('/categories/{group}', [AdminContentController::class, 'categoryStore'])->where('group', 'skin|shape|articles');
        Route::put('/categories/{group}/{id}', [AdminContentController::class, 'categoryUpdate'])->where('group', 'skin|shape|articles')->whereNumber('id');
        Route::delete('/categories/{group}/{id}', [AdminContentController::class, 'categoryDestroy'])->where('group', 'skin|shape|articles')->whereNumber('id');
        Route::post('/uploads', [AdminContentController::class, 'upload'])->middleware('throttle:30,1');
        Route::get('/{section}', [AdminController::class, 'index'])
            ->where('section', 'beranda|skin|shape|dokter|artikel|pengaturan')->name('section');
    });
});
