<?php

namespace App\Support\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class DeletionGuard
{
    public static function throwIfSystem(Model $model, string $message): void
    {
        SystemRecordGuard::throwIfSystem($model, $message);
    }

    public static function throwIfInUse(array $dependencies, string $field, string $message): void
    {
        if (in_array(true, $dependencies, true)) {
            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }
    }
}
