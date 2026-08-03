<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesOrionRouteModel
{
    /**
     * Orion may expose the route parameter as a UUID string instead of a resolved model.
     *
     * @param  class-string<Model>  $class
     */
    protected function routeModel(string $parameter, string $class): ?Model
    {
        $value = $this->route($parameter);

        if ($value instanceof $class) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return $class::query()->where('uuid', $value)->first();
        }

        return null;
    }
}
