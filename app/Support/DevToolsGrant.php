<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Stateless dev-tool token (encrypted, not stored in cache or session).
 * After PIN, redirect includes ?dt=… ; terminal keeps it in JS for POST / XHR.
 * Full page reload clears that, so the user must unlock again.
 */
class DevToolsGrant
{
    public static function create(): string
    {
        return Crypt::encryptString(json_encode([
            'n' => bin2hex(random_bytes(16)),
            'i' => time(),
        ], JSON_THROW_ON_ERROR));
    }

    public static function valid(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        try {
            $raw = Crypt::decryptString($token);
            json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
