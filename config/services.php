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

];
