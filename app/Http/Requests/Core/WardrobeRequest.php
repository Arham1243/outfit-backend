<?php

namespace App\Http\Requests\Core;

use Orion\Http\Requests\Request;

class WardrobeRequest extends Request
{
    public function commonRules(): array
    {
        return [
            'image' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function storeRules(): array
    {
        return [
            'image' => ['required', 'string'],
        ];
    }
}
