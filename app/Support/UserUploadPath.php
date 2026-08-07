<?php

namespace App\Support;

use Illuminate\Support\Str;

class UserUploadPath
{
    public static function root(string $userUuid): string
    {
        return 'uploads/users/'.$userUuid;
    }

    public static function profileDir(string $userUuid): string
    {
        return self::root($userUuid).'/profile';
    }

    public static function faceDir(string $userUuid): string
    {
        return self::root($userUuid).'/face';
    }

    public static function wardrobeDir(string $userUuid): string
    {
        return self::root($userUuid).'/wardrobe';
    }

    public static function generatedOutfitsDir(string $userUuid): string
    {
        return self::root($userUuid).'/generated-outfits';
    }

    public static function baseModel(string $userUuid): string
    {
        return self::root($userUuid).'/base-model.jpg';
    }

    public static function generatedOutfit(string $userUuid, string $outfitUuid): string
    {
        return self::generatedOutfitsDir($userUuid).'/'.$outfitUuid.'.jpg';
    }

    public static function ensureUuid(object $model): string
    {
        if (! empty($model->uuid)) {
            return (string) $model->uuid;
        }

        $model->uuid = (string) Str::uuid();

        return $model->uuid;
    }
}
