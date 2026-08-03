<?php

namespace App\Support;

/**
 * Matches {@see CheckIfUserIsActive}: null/unknown status is treated as active.
 */
final class AccountStatus
{
    public static function isActive(mixed $status): bool
    {
        $isInactive = (is_bool($status) && $status !== true)
            || (is_string($status) && strtolower($status) !== 'active')
            || (is_numeric($status) && (int) $status !== 1);

        return ! $isInactive;
    }
}
