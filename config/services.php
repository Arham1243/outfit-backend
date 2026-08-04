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

];
