<?php

namespace App\Http\Requests\Core;

use App\Models\User;
use App\Support\FaceMode;
use App\Support\PreferredLanguageValidation;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class UserRequest extends Request
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->input('email'))) {
            $this->merge([
                'email' => mb_strtolower(trim($this->input('email'))),
            ]);
        }

        PreferredLanguageValidation::mergeUuidToId($this);

        if ($this->has('height') && $this->input('height') !== null && $this->input('height') !== '') {
            $this->merge([
                'height' => (int) round((float) $this->input('height')),
            ]);
        }

        if ($this->has('face_mode') && is_string($this->input('face_mode'))) {
            $this->merge([
                'face_mode' => trim($this->input('face_mode')),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $mode = $this->input('face_mode');

            if ($mode === null || $mode === '') {
                return;
            }

            if (! FaceMode::requiresFaceImage($mode)) {
                return;
            }

            $hasNewUpload = is_string($this->input('face_image'))
                && str_starts_with($this->input('face_image'), 'data:');

            if ($hasNewUpload) {
                return;
            }

            $routeUser = $this->route('user');
            $existingFace = $routeUser instanceof User
                ? $routeUser->face_image
                : ($routeUser ? User::where('uuid', $routeUser)->value('face_image') : null);

            if (! empty($existingFace)) {
                return;
            }

            $validator->errors()->add('face_image', __('outfit.face_image_required'));
        });
    }

    public function commonRules(): array
    {
        $routeUser = $this->route('user');
        $currentId = null;
        if ($routeUser !== null) {
            $currentId = $routeUser instanceof User
                ? $routeUser->id
                : User::where('uuid', $routeUser)->value('id');
        }

        return [
            'email' => [
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($currentId),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female'])],
            'height' => ['nullable', 'integer', 'min:50', 'max:300'],
            'face_image' => ['nullable', 'string'],
            'face_mode' => ['nullable', 'string', Rule::in(FaceMode::all())],
            'role_id' => ['nullable', 'exists:roles,id'],
            'preferred_language_id' => ['nullable', 'integer'],
            'password' => ['nullable', 'string', 'min:8'],
            ...PreferredLanguageValidation::rules(),
        ];
    }

    public function updateRules(): array
    {
        $rules = $this->commonRules();

        if ($this->isProfileUpdateRequest()) {
            $rules['email'] = ['prohibited'];
        }

        return $rules;
    }

    protected function isProfileUpdateRequest(): bool
    {
        return $this->is('api/profiles/*');
    }
}
