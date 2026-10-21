<?php
use App\Http\Controllers\SkinTreatmentController;
use App\Http\Controllers\ShapeTreatmentController;
use App\Http\Controllers\InstagramFeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GoogleReviewsController;
use App\Http\Controllers\LocaleController;
use App\Support\LocalizedContent;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\EnsureDemoAdmin;
use Illuminate\Support\Facades\Route;
Route::post('/language/{locale}', LocaleController::class)->name('locale.update');
Route::get('/', HomeController::class)->name('home');
Route::get('/instagram/feed', InstagramFeedController::class)->name('instagram.feed');
Route::get('/google/reviews', GoogleReviewsController::class)->name('google.reviews');
Route::get('/skin', [SkinTreatmentController::class, 'index'])->name('skin');
Route::get('/shape', [ShapeTreatmentController::class, 'index'])->name('shape');
Route::get('/skin/{slug}', [SkinTreatmentController::class, 'show'])->name('skin.detail');
Route::get('/shape/{slug}', [ShapeTreatmentController::class, 'show'])->name('shape.detail');
Route::view('/doctors', 'pages.doctors')->name('doctors');
Route::get('/doctors/{slug}', function (string $slug) {
    abort_unless(preg_match('/^doctor-([1-6])$/D', $slug, $matches), 404);
    $doctorIndex = (int) $matches[1];
    return view('pages.doctor-detail', ['slug' => $slug, 'doctorIndex' => $doctorIndex, 'doctor' => LocalizedContent::doctor($doctorIndex, config('doctors.profiles.'.$doctorIndex))]);
})->name('doctors.detail');
Route::view('/articles', 'pages.articles')->name('articles');
Route::get('/articles/{slug}', fn ($slug) => view('pages.article-detail', ['slug'=>$slug]))->name('articles.detail');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/login', [AdminController::class, 'authenticate'])->middleware('throttle:10,1')->name('authenticate');
    Route::middleware(EnsureDemoAdmin::class)->group(function () {
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/{section}', [AdminController::class, 'index'])
            ->where('section', 'beranda|skin|shape|dokter|artikel|pengaturan')->name('section');
    });
});
