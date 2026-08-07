<?php

namespace App\Providers;

use App\Contracts\OutfitGeneration\OutfitCombinationProvider;
use App\Contracts\OutfitGeneration\OutfitGenerationProvider;
use App\Services\OutfitGeneration\OutfitGenerationManager;
use Illuminate\Support\ServiceProvider;

class OutfitGenerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OutfitGenerationManager::class);

        $this->app->singleton(OutfitGenerationProvider::class, function ($app) {
            return $app->make(OutfitGenerationManager::class)->driver();
        });

        $this->app->singleton(OutfitCombinationProvider::class, function ($app) {
            return $app->make(OutfitGenerationManager::class)->combinationDriver();
        });
    }
}
