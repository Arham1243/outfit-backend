<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'uuid' => $this->uuid,
            'role_id' => $this->role_id,
            'preferred_language_id' => $this->preferred_language_id,
            'preferred_language_uuid' => $this->preferredLanguage?->uuid,
            'preferred_language' => $this->whenLoaded(
                'preferredLanguage',
                fn () => new LanguagePreferenceResource($this->preferredLanguage)
            ),
            'dark_mode' => (bool) ($this->dark_mode ?? false),
            'sidebar_open' => (bool) ($this->sidebar_open ?? true),
            'name' => $this->name,
            'status' => $this->status,
            'email' => $this->email,
            'gender' => $this->gender,
            'profile_image' => $this->profile_image_url,
            'face_image' => $this->face_image_url,
            'face_mode' => $this->faceMode(),
            'height' => $this->height,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'role' => $this->whenLoaded('role', function () {
                return new RoleResource($this->role);
            }),
        ];
    }
}
