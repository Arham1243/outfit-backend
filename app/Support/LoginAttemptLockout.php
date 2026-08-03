<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LoginAttemptLockout
{
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_MINUTES = 10;

    public const CONTEXT_APP = 'app';

    public const LOCKED_MESSAGE = 'Your account has been locked due to too many failed attempts. Use forget password link below to reset your password or contact your system administrator.';

    public static function isLocked(string $email, string $context): bool
    {
        return Cache::has(self::lockKey($email, $context));
    }

    public static function lockedResponseIfNeeded(string $email, string $context): ?JsonResponse
    {
        if (! self::isLocked($email, $context)) {
            return null;
        }

        return self::lockedJsonResponse();
    }

    public static function lockedJsonResponse(): JsonResponse
    {
        return response()->json([
            'message' => self::LOCKED_MESSAGE,
            'errors' => ['email' => [self::LOCKED_MESSAGE]],
        ], 422);
    }

    /**
     * @return 'failed'|'newly_locked'|'already_locked'
     */
    public static function recordFailure(string $email, string $context): string
    {
        if (self::isLocked($email, $context)) {
            return 'already_locked';
        }

        $attemptsKey = self::attemptsKey($email, $context);
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;

        Cache::put($attemptsKey, $attempts, now()->addMinutes(self::LOCKOUT_MINUTES));

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::put(
                self::lockKey($email, $context),
                true,
                now()->addMinutes(self::LOCKOUT_MINUTES)
            );
            Cache::forget($attemptsKey);

            return 'newly_locked';
        }

        return 'failed';
    }

    public static function isLockoutResult(string $result): bool
    {
        return in_array($result, ['newly_locked', 'already_locked'], true);
    }

    public static function clear(string $email, string $context): void
    {
        Cache::forget(self::attemptsKey($email, $context));
        Cache::forget(self::lockKey($email, $context));
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private static function attemptsKey(string $email, string $context): string
    {
        return "login_attempts:{$context}:".self::normalizeEmail($email);
    }

    private static function lockKey(string $email, string $context): string
    {
        return "login_locked:{$context}:".self::normalizeEmail($email);
    }
}
