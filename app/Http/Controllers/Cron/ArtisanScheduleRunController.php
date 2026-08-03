<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class ArtisanScheduleRunController extends Controller
{
    private const LOG_FILE = 'logs/schedule-run.log';

    public function __invoke(Request $request): Response
    {
        $token = (string) $request->query('token', '');
        $expected = (string) config('services.artisan_schedule_run.token', '');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            abort(404);
        }

        try {
            $exitCode = Artisan::call('schedule:run');
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                $this->logError('schedule:run exited with non-zero status', [
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]);

                return response('ERROR exit='.$exitCode, 500);
            }

            return response(
                $output !== '' ? 'OK '.$output : 'OK',
                200
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('ERROR', 500);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logError(string $message, array $context = []): void
    {
        $payload = array_merge([
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ], $context);

        $line = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;

        file_put_contents(
            storage_path(self::LOG_FILE),
            $line,
            FILE_APPEND | LOCK_EX
        );
    }
}
