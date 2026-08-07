<?php

namespace App\Support;

use App\Services\OutfitGeneration\OutfitGenerationManager;
use App\Support\FaceMode;
use Illuminate\Support\Facades\Storage;

class BaseModelFingerprint
{
    public static function for(
        ?int $height,
        ?string $gender = null,
        ?string $provider = null,
        ?string $faceMode = null,
        ?string $faceImagePath = null
    ): string {
        $manager = app(OutfitGenerationManager::class);
        $provider = $provider ?? $manager->defaultDriverName();
        $version = $manager->baseModelCacheVersion($provider);
        $mode = in_array($faceMode, FaceMode::all(), true) ? $faceMode : FaceMode::AI_MODEL;
        $faceKey = FaceMode::requiresFaceImage($mode) ? self::faceContentHash($faceImagePath) : '';

        return hash('sha256', $provider.'|'.$version.'|'.$mode.'|'.$faceKey.'|'.($height ?? '').'|'.($gender ?? ''));
    }

    private static function faceContentHash(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        if (Storage::disk('public')->exists($path)) {
            return hash('sha256', Storage::disk('public')->get($path));
        }

        if (Storage::disk('s3')->exists($path)) {
            return hash('sha256', Storage::disk('s3')->get($path));
        }

        return hash('sha256', $path);
    }
}
