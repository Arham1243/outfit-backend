<?php

use App\Http\Controllers\DBConsoleController;
use App\Http\Controllers\DevToolsUnlockController;
use App\Http\Controllers\EnvEditorController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\TerminalController;
use App\Http\Middleware\EnsureDevToolsUnlocked;
use Illuminate\Support\Facades\Route;

Route::get('/dev-tools/unlock', [DevToolsUnlockController::class, 'show'])->name('dev-tools.unlock.show');
Route::post('/dev-tools/unlock', [DevToolsUnlockController::class, 'unlock'])->name('dev-tools.unlock');

Route::middleware(EnsureDevToolsUnlocked::class)->group(function () {
    Route::get('/terminal', [TerminalController::class, 'index']);
    Route::post('/terminal/run', [TerminalController::class, 'run']);

    Route::get('/db-console', [DBConsoleController::class, 'index']);
    Route::post('/db-console', [DBConsoleController::class, 'run'])->name('db.console.run');

    Route::get('/env-editor', [EnvEditorController::class, 'index'])->name('env');
    Route::post('/env-editor', [EnvEditorController::class, 'save'])->name('env.save');

    Route::get('/logs', [LogController::class, 'read']);
    Route::get('/logs/delete', [LogController::class, 'delete']);
});
