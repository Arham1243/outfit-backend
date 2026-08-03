<?php

use App\Http\Controllers\Core\LanguageController;
use App\Http\Controllers\Core\RoleController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\Role\PermissionController;
use App\Http\Controllers\Role\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::get('languages/active', [LanguageController::class, 'activeOptions']);

Route::get('permissions/me', [PermissionController::class, 'me']);
Route::get('profiles/{user}', [UserController::class, 'show']);
Route::put('profiles/{user}', [UserController::class, 'update']);
Route::middleware(['auth.sanctum_user'])->group(function () {
    Route::post('users/list', [UserController::class, 'search']);
    Route::get('users/list/{user}/permissions', [UserController::class, 'getPermissions']);
});
Route::post('roles/list', [RoleController::class, 'search']);
Route::get('roles/list/{role}/permissions', [RolePermissionController::class, 'show']);
