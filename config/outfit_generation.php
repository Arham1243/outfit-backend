<?php

use App\Services\OutfitGeneration\Providers\FashnCombinationProvider;
use App\Services\OutfitGeneration\Providers\FashnOutfitGenerationProvider;
use App\Services\OutfitGeneration\Providers\OpenAiCombinationProvider;
use App\Services\OutfitGeneration\Providers\OpenAiOutfitGenerationProvider;

/*
|--------------------------------------------------------------------------
| Outfit Generation Providers
|--------------------------------------------------------------------------
|
| Switch the active provider via OUTFIT_GENERATION_PROVIDER. Each provider
| must implement App\Contracts\OutfitGeneration\OutfitGenerationProvider.
|
| To add a new provider:
| 1. Create a class under app/Services/OutfitGeneration/Providers/
| 2. Register it in the "providers" array below with enabled + driver
| 3. Set OUTFIT_GENERATION_PROVIDER to your provider key
|
*/

return [

    'default' => env('OUTFIT_GENERATION_PROVIDER', 'fashn'),

    'max_combinations' => (int) env('OUTFIT_MAX_COMBINATIONS', env('FASHN_MAX_COMBINATIONS', 4)),
    'dedupe_days' => (int) env('OUTFIT_DEDUPE_DAYS', env('FASHN_DEDUPE_DAYS', 30)),

    'providers' => [

        'fashn' => [
            'enabled' => filter_var(env('FASHN_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'driver' => FashnOutfitGenerationProvider::class,
            'combination_driver' => FashnCombinationProvider::class,
            'base_model_cache_version' => env('FASHN_BASE_MODEL_CACHE_VERSION', 'v4-ai-face-isolation'),
        ],

        'openai' => [
            'enabled' => filter_var(env('OPENAI_OUTFIT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'driver' => OpenAiOutfitGenerationProvider::class,
            'combination_driver' => OpenAiCombinationProvider::class,
            'base_model_cache_version' => env('OPENAI_BASE_MODEL_CACHE_VERSION', 'v2-ai-face-isolation'),
        ],

    ],

];
