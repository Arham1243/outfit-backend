<?php

namespace App\Support;

use App\Services\OutfitGeneration\OutfitGenerationManager;

class BaseModelFingerprint
{
    public static function for(?int $height, ?string $gender = null, ?string $provider = null): string
    {
        $manager = app(OutfitGenerationManager::class);
        $provider = $provider ?? $manager->defaultDriverName();
        $version = $manager->baseModelCacheVersion($provider);

        return hash('sha256', $provider.'|'.$version.'|generic|'.($height ?? '').'|'.($gender ?? ''));
    }
}
