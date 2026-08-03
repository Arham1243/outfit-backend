<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'status' => true,
            'message' => 'User logged in successfully',
            'access_token' => $this->access_token ?? null,
            'expires_in' => $this->expires_in ?? null,
            'is_first_login' => (bool) ($this->resource->getAttribute('is_first_login') ?? false),
            'first_login_at' => $this->first_login_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
