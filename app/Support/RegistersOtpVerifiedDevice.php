<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Persists OTP-verified device rows while tolerating:
 * - duplicate-key races between first() and create()
 * - legacy schema with a global UNIQUE on `device_fingerprint` only (one row
 *   per browser); in that case the existing row is updated for the current owner.
 */
final class RegistersOtpVerifiedDevice
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function upsert(
        string $modelClass,
        string $ownerColumn,
        int|string $ownerId,
        string $fingerprint,
        array $deviceAttributes,
    ): void {
        $device = $modelClass::query()
            ->where($ownerColumn, $ownerId)
            ->where('device_fingerprint', $fingerprint)
            ->first();

        if ($device) {
            $device->update($deviceAttributes);

            return;
        }

        try {
            $modelClass::query()->create(array_merge($deviceAttributes, [
                $ownerColumn => $ownerId,
                'device_fingerprint' => $fingerprint,
            ]));
        } catch (QueryException $e) {
            if (! self::isDuplicateKey($e)) {
                throw $e;
            }

            $device = $modelClass::query()
                ->where($ownerColumn, $ownerId)
                ->where('device_fingerprint', $fingerprint)
                ->first();
            if ($device) {
                $device->update($deviceAttributes);

                return;
            }

            $legacy = $modelClass::query()
                ->where('device_fingerprint', $fingerprint)
                ->first();
            if ($legacy) {
                $legacy->update(array_merge($deviceAttributes, [
                    $ownerColumn => $ownerId,
                ]));

                return;
            }

            throw $e;
        }
    }

    private static function isDuplicateKey(QueryException $e): bool
    {
        if ($e->getCode() === '23000') {
            return true;
        }

        $errorInfo = $e->errorInfo ?? [];
        if (isset($errorInfo[1]) && (int) $errorInfo[1] === 1062) {
            return true;
        }

        $message = $e->getMessage();

        return str_contains($message, '1062')
            || str_contains($message, 'Duplicate')
            || str_contains($message, 'UNIQUE constraint failed');
    }
}
