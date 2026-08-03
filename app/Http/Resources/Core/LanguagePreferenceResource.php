<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Resources\Json\JsonResource;

class LanguagePreferenceResource extends JsonResource
{
    public function toArray($request): array
    {
        $region = $this->region_code !== '' && $this->region_code !== null
            ? $this->region_code
            : null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'region_code' => $region,
            'locale' => $this->locale,
            'is_rtl' => (bool) $this->is_rtl,
        ];
    }
}
