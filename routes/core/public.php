<?php

use App\Http\Controllers\Core\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/languages/active', [LanguageController::class, 'activeOptions']);
