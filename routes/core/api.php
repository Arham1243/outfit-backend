<?php

use App\Http\Controllers\Core\WardrobeController;
use App\Http\Controllers\Core\RoleController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\Role\PermissionController;
use App\Http\Controllers\Role\RolePermissionController;
use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;

Route::middleware(['granular.permission:core,core.wardrobe'])->group(function () {
    Orion::resource('wardrobes', WardrobeController::class);
});

Route::middleware(['granular.permission:core,core.roles'])->group(function () {
    Orion::resource('roles', RoleController::class);
    Route::post('roles/{role}/change-status', [RoleController::class, 'changeStatus']);
    Route::delete('roles/{role}/delete', [RoleController::class, 'deleteRole']);
});

Route::middleware(['granular.permission:core,core.users'])->group(function () {
    Orion::resource('users', UserController::class);
    Route::get('users/{user}/permissions', [UserController::class, 'getPermissions']);
    Route::post('users/{user}/change-status', [UserController::class, 'changeStatus']);
    Route::post('users/{user}/resend-welcome-email', [UserController::class, 'resendWelcomeEmail']);
});

Route::middleware(['granular.permission:core,core.roles'])->group(function () {
    Route::put('roles/{role}/permissions', [RolePermissionController::class, 'sync']);
    Route::get('roles/{role}/permissions', [RolePermissionController::class, 'show']);
    Route::get('permissions', [PermissionController::class, 'index']);
});
