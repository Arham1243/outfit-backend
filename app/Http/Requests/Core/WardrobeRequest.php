<?php

namespace App\Http\Requests\Core;

use App\Support\WardrobeTypes;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class WardrobeRequest extends Request
{
    public function commonRules(): array
    {
        return [
            'image' => ['nullable', 'string'],
            'type' => ['nullable', 'string', Rule::in(WardrobeTypes::ALL)],
        ];
    }

    public function storeRules(): array
    {
        return [
            'image' => ['required', 'string'],
        ];
    }
}
