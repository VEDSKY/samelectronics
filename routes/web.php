<?php

use App\Http\Controllers\TerminalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Terminal Web Routes
|--------------------------------------------------------------------------
|
| The whole public site is a single terminal application. Server side it is
| one shell (TerminalController@index) plus a small JSON API used by the
| terminal kernel. Admin CRUD is exposed through the same terminal.
|
*/

Route::get('/', [TerminalController::class, 'index'])->name('terminal');

Route::prefix('api/terminal')->group(function () {
    Route::get('/bootstrap', [TerminalController::class, 'bootstrap']);
    Route::get('/content/{section}', [TerminalController::class, 'content']);
    Route::get('/catalog', [TerminalController::class, 'catalog']);
    Route::post('/contact', [TerminalController::class, 'contactStore']);
    Route::post('/login', [TerminalController::class, 'login']);
    Route::post('/logout', [TerminalController::class, 'logout']);
    Route::post('/register', [TerminalController::class, 'register']);

    Route::prefix('admin')->group(function () {
        Route::get('/stats', [TerminalController::class, 'adminStats']);
        Route::get('/{resource}', [TerminalController::class, 'adminIndex']);
        Route::post('/{resource}', [TerminalController::class, 'adminStore']);
        Route::get('/{resource}/{id}', [TerminalController::class, 'adminShow']);
        Route::patch('/{resource}/{id}', [TerminalController::class, 'adminUpdate']);
        Route::delete('/{resource}/{id}', [TerminalController::class, 'adminDestroy']);
    });
});
