<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasFormattedDates
{
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->dateFields)) {
            $this->attributes[$key] = $value
                ? Carbon::parse($value)->format('Y-m-d')
                : null;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->dateFields) && $value) {
            return Carbon::parse($value)->toISOString();
        }

        return $value;
    }
}
