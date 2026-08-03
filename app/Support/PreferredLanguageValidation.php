<?php

namespace App\Support;

use App\Models\Core\Language;
use Illuminate\Validation\Rule;

class PreferredLanguageValidation
{
    public static function rules(): array
    {
        return [
            'preferred_language_id' => [
                'nullable',
                'integer',
                Rule::exists('languages', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'preferred_language_uuid' => [
                'nullable',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $request = request();
                    if ($request->input('preferred_language_id') === null) {
                        $fail('The selected language is invalid or inactive.');
                    }
                },
            ],
        ];
    }

    public static function mergeUuidToId(\Illuminate\Http\Request $request): void
    {
        if (! $request->has('preferred_language_uuid')) {
            return;
        }

        $uuid = $request->input('preferred_language_uuid');

        if ($uuid === null || $uuid === '') {
            $request->merge(['preferred_language_id' => null]);

            return;
        }

        $request->merge([
            'preferred_language_id' => Language::query()
                ->where('uuid', $uuid)
                ->where('is_active', true)
                ->value('id'),
        ]);
    }

}
