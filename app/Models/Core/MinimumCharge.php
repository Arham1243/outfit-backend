<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MinimumCharge extends Model
{
    use LogsActivity;
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $appends = ['decimal_time'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->logExcept([
                'updated_at',
                'created_at',
            ])
            ->dontSubmitEmptyLogs()
            ->useLogName('minimum_charge');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function setDecimalTimeAttribute($value)
    {
        $this->attributes['decimal_time'] = number_format((float) $value, 2, '.', '');
    }

    public function getDecimalTimeAttribute($value)
    {
        return number_format((float) $value, 2, '.', '');
    }

    protected static function booted()
    {
            static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });

    }
}
