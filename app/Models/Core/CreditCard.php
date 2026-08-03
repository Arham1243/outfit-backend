<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CreditCard extends Model
{
    use LogsActivity;
    protected $guarded = ['id', 'created_at', 'updated_at'];

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
            ->useLogName('credit_card');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function dependencies(): array
    {
        return [
            'users' => $this->users()->exists(),
        ];
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });

        static::deleting(function ($model) {
            $dependencies = $model->dependencies();
            if (in_array(true, $dependencies, true)) {
                throw ValidationException::withMessages([
                    'credit_card' => 'This credit card is in use and cannot be deleted.',
                ]);
            }
        });
    }
}
