<?php

namespace App\Support;

class BaseModelFingerprint
{
    public static function for(?int $height, ?string $gender = null): string
    {
        $version = (string) config('services.fashn.base_model_cache_version', 'v3-generic');

        return hash('sha256', $version.'|generic|'.($height ?? '').'|'.($gender ?? ''));
    }
}
