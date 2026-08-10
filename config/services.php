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
