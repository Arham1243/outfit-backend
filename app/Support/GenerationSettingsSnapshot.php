<?php

namespace App\Support;

use App\Models\User;

class GenerationSettingsSnapshot
{
    /**
     * Capture the user profile settings applied during outfit generation.
     *
     * @return array{
     *     height: int|null,
     *     gender: string|null,
     *     face_mode: string,
     *     face_image_used: bool,
     *     stored_face_image: bool
     * }
     */
    public static function for(User $user): array
    {
        return [
            'height' => $user->height !== null ? (int) $user->height : null,
            'gender' => is_string($user->gender) ? $user->gender : null,
            'face_mode' => $user->faceMode(),
            'face_image_used' => $user->faceImageForGeneration() !== null,
            'stored_face_image' => ! empty($user->face_image),
        ];
    }
}
