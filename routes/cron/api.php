<?php

use App\Http\Controllers\Cron\ArtisanScheduleRunController;
use Illuminate\Support\Facades\Route;

Route::get('/cron/artisan-schedule-run', ArtisanScheduleRunController::class);
