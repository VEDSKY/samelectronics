<?php

use App\Http\Controllers\SandboxController;
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
    Route::post('/login', [TerminalController::class, 'login']);
    Route::post('/logout', [TerminalController::class, 'logout']);

    /*
     | The hackable surface. Real file I/O and real SQL, but scoped entirely to
     | the throwaway sandbox; the sentinel resets it on every pulse.
     */
    Route::prefix('shell')->middleware('throttle:240,1')->group(function () {
        Route::get('/ls', [SandboxController::class, 'fsList']);
        Route::get('/cat', [SandboxController::class, 'fsRead']);
        Route::post('/write', [SandboxController::class, 'fsWrite']);
        Route::post('/mkdir', [SandboxController::class, 'fsMkdir']);
        Route::post('/rm', [SandboxController::class, 'fsRemove']);
        Route::post('/chmod', [SandboxController::class, 'fsChmod']);
        Route::post('/reflect', [SandboxController::class, 'reflect']);
        Route::post('/sql', [SandboxController::class, 'sql']);
        Route::get('/integrity', [SandboxController::class, 'integrity']);
    });

    Route::prefix('sentinel')->group(function () {
        Route::get('/status', [SandboxController::class, 'sentinelStatus']);
        Route::get('/log', [SandboxController::class, 'sentinelLog']);
        Route::post('/pulse', [SandboxController::class, 'sentinelPulse']);
    });

    Route::prefix('admin')->group(function () {
        Route::get('/stats', [TerminalController::class, 'adminStats']);
        Route::get('/{resource}', [TerminalController::class, 'adminIndex']);
        Route::post('/{resource}', [TerminalController::class, 'adminStore']);
        Route::get('/{resource}/{id}', [TerminalController::class, 'adminShow']);
        Route::patch('/{resource}/{id}', [TerminalController::class, 'adminUpdate']);
        Route::delete('/{resource}/{id}', [TerminalController::class, 'adminDestroy']);
    });
});
