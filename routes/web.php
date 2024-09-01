<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReleasesController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use UniSharp\LaravelFilemanager\Lfm;

Route::get('/', [HomePageController::class, 'show'])->name('homepage');
Route::get('/about', [AboutController::class, 'show'])->name('about');
Route::get('/news', [NewsController::class, 'show'])->name('news');
Route::get('/events', [EventController::class, 'show'])->name('events');
Route::get('/releases', [ReleasesController::class, 'show'])->name('releases');
Route::get('/contact', [ContactController::class, 'show'])->name('contact-us');
Route::post('/contact', [ContactController::class, 'store'])->name('contact-us')->middleware('throttle:1,1');

Route::get('set-locale/{localeName}',[LanguageController::class, 'changeLanguage'])->name('change.locale');

Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*Route::prefix('acp')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('acp.dashboard');
    Route::prefix('overview')->group(function (){
        Route::any('/', redirect(route('admin.dashboard')));
        Route::get('/fragments', [AcpDashboardController::class, 'index'])->name('acp.view.fragments');
        Route::get('/access-tokens', [AcpDashboardController::class, 'index'])->name('acp.view.fragments');
        Route::get('/site-settings', [AcpDashboardController::class, 'index'])->name('acp.view.fragments');
        Route::get('/project', [AcpDashboardController::class, 'index'])->name('acp.view.fragments');
    });

    Route::prefix('manage')->group(function () {
        Route::any('/', redirect(route('admin.dashboard')));
        Route::get('/fragment/{id}', [FragmentController::class, 'acp_index'])->name('acp.manage.fragment');
        Route::patch('/fragment/{id}', [FragmentController::class, 'update'])->name('acp.update.fragment');
        Route::delete('/fragment/{id}', [FragmentController::class, 'destroy'])->name('acp.destroy.fragment');
        Route::get('/access-token/{id}', [AccessTokenController::class, 'acp_index'])->name('acp.manage.access.token');
        Route::patch('/access-token/{id}', [AccessTokenController::class, 'update'])->name('acp.update.access.token');
        Route::patch('/access-token/{id}/ban', [AccessTokenController::class, 'ban_token'])->name('acp.ban.access.token');
        Route::delete('/access-token/{id}', [AccessTokenController::class, 'destroy'])->name('acp.destroy.access.token');
    });
})->middleware('auth');*/

// Analytics Opt-Out Routes
// Route::get('/?pixel_optout=true', )->name('pixsl.opt_out');

Route::group(['prefix' => 'lfm', 'middleware' => ['web', 'auth']], function () {
    Lfm::routes();
});

require __DIR__.'/auth.php';
