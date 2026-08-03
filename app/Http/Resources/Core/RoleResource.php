<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'is_system' => (bool) $this->is_system,
            'system' => (bool) $this->is_system,
            'status' => $this->status,
            'is_in_use' => (int) ($this->assigned_users_count ?? 0) > 0,
        ];
    }
}
