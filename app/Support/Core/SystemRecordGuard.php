<?php

namespace App\Support\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class SystemRecordGuard
{
    public static function isSystem(Model $model): bool
    {
        return (bool) ($model->is_system ?? false);
    }

    public static function throwIfSystem(Model $model, string $message): void
    {
        if (self::isSystem($model)) {
            throw ValidationException::withMessages([
                'base' => $message,
            ]);
        }
    }
}
