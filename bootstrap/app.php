<?php

use App\Http\Middleware\AuthAnyGuardMiddleware;
use App\Http\Middleware\AuthenticateMiddleware;
use App\Http\Middleware\AuthenticateSanctumUser;
use App\Http\Middleware\BypassMenuAccessMiddleware;
use App\Http\Middleware\BypassMenuLimitMiddleware;
use App\Http\Middleware\CheckIfUserIsActive;
use App\Http\Middleware\GranularPermissionMiddleware;
use App\Http\Middleware\RolePermissionMiddleware;
use App\Http\Middleware\SetLocaleFromRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            HandleCors::class,
        ]);
        $middleware->redirectGuestsTo(fn () => config('app.frontend_url'));
        $middleware->api(prepend: [
            SetLocaleFromRequest::class,
        ]);
        $middleware->alias([
            'auth.api' => AuthenticateMiddleware::class,
            'auth.any' => AuthAnyGuardMiddleware::class,
            'auth.sanctum_user' => AuthenticateSanctumUser::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'role.permission' => RolePermissionMiddleware::class,
            'granular.permission' => GranularPermissionMiddleware::class,
            'check.active' => CheckIfUserIsActive::class,
            'menu.limit' => BypassMenuLimitMiddleware::class,
            'menu.access' => BypassMenuAccessMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Drain the database queue every minute. --stop-when-empty ensures the
        // worker exits before the next cron tick so jobs can't pile up on shared
        // hosting where supervisor is unavailable. --max-time leaves headroom
        // before PHP max_execution_time and the next tick.
        $schedule->command('queue:work --stop-when-empty --max-time=50 --tries=1 --sleep=0')
            ->everyMinute()
            ->withoutOverlapping()
            ->name('qbo-queue-worker');

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => __('messages.unauthenticated'),
                ], 401);
            }

            return parent::render($request, $e);
        });

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return $response;
            }

            return app(HandleCors::class)->handle(
                $request,
                static fn () => $response
            );
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return app(HandleCors::class)->handle(
                    $request,
                    static fn () => response()->json([
                        'message' => $e->getMessage(),
                        'errors' => $e->errors(),
                    ], 422)
                );
            }

            return null;
        });
    })
    ->create();
