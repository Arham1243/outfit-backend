<?php

namespace App\Http\Requests\Core;

use Orion\Http\Requests\Request;

class LanguageRequest extends Request
{
    public function commonRules(): array
    {
        return [];
    }

    /**
     * Languages are seeded; the admin UI does not create rows.
     */
    public function storeRules(): array
    {
        return [];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:191'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
