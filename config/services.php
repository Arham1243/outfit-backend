<?php

$cronApiTokenRaw = env('CRON_API_TOKEN');
$sharedCronApiToken =
    is_string($cronApiTokenRaw) && trim($cronApiTokenRaw) !== ''
        ? $cronApiTokenRaw
        : null;

return [

    /*
     * GET /api/cron/artisan-schedule-run?token=… runs php artisan schedule:run.
     * Errors are appended to storage/logs/schedule-run.log
     */
    'artisan_schedule_run' => [
        'token' => $sharedCronApiToken,
    ],

    'huggingface' => [
        'token' => env('HF_API_TOKEN'),
        'image_classification_url' => env(
            'HF_IMAGE_CLASSIFICATION_URL',
            'https://router.huggingface.co/hf-inference/models/google/vit-base-patch16-224'
        ),
    ],

    'fashn' => [
        'api_key' => env('FASHN_API_KEY'),
        'base_url' => env('FASHN_BASE_URL', 'https://api.fashn.ai'),
        'max_combinations' => (int) env('FASHN_MAX_COMBINATIONS', 2),
        'max_attempts' => (int) env('FASHN_MAX_ATTEMPTS', 2),
        'poll_interval_seconds' => (int) env('FASHN_POLL_INTERVAL_SECONDS', 3),
        'poll_timeout_seconds' => (int) env('FASHN_POLL_TIMEOUT_SECONDS', 600),
        'generation_mode' => env('FASHN_GENERATION_MODE', 'fast'),
        'resolution' => env('FASHN_RESOLUTION', '1k'),
        'dedupe_days' => (int) env('FASHN_DEDUPE_DAYS', 30),
        'base_model_cache_version' => env('FASHN_BASE_MODEL_CACHE_VERSION', 'v3-generic'),
        'model_create_generation_mode' => env('FASHN_MODEL_CREATE_MODE', 'balanced'),
        'model_create_aspect_ratio' => env('FASHN_MODEL_CREATE_ASPECT_RATIO', '2:3'),
        'model_create_resolution' => env('FASHN_MODEL_CREATE_RESOLUTION', '2k'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'combination_model' => env('OPENAI_COMBINATION_MODEL', 'gpt-5.1'),
        'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-2'),
        'image_size' => env('OPENAI_IMAGE_SIZE', '1024x1536'),
        'image_quality' => env('OPENAI_IMAGE_QUALITY', 'high'),
        'studio_reference_path' => env('OPENAI_STUDIO_REFERENCE_PATH', 'assets/images/studio_template.jpg'),
    ],

];
