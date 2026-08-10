<?php

use App\Services\OutfitGeneration\Providers\OpenAiCombinationProvider;
use App\Services\OutfitGeneration\Providers\OpenAiOutfitGenerationProvider;

/*
|--------------------------------------------------------------------------
| Outfit Generation
|--------------------------------------------------------------------------
|
| OpenAI is the only outfit generation provider.
|
*/

return [

    'default' => env('OUTFIT_GENERATION_PROVIDER', 'openai'),

    'max_combinations' => (int) env('OUTFIT_MAX_COMBINATIONS', 4),
    'dedupe_days' => (int) env('OUTFIT_DEDUPE_DAYS', 30),

    'providers' => [

        'openai' => [
            'enabled' => filter_var(env('OPENAI_OUTFIT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'driver' => OpenAiOutfitGenerationProvider::class,
            'combination_driver' => OpenAiCombinationProvider::class,
            'base_model_cache_version' => env('OPENAI_BASE_MODEL_CACHE_VERSION', 'v2-ai-face-isolation'),
        ],

    ],

];
