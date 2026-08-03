<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/core/public.php';
require __DIR__.'/cron/api.php';

Route::middleware(['auth.any:sanctum', 'check.active'])->group(function () {
    require __DIR__.'/core/api.php';
});

Route::middleware(['auth.any:sanctum', 'check.active'])->group(function () {
    require __DIR__.'/user/api.php';
    require __DIR__.'/reports/api.php';
});
