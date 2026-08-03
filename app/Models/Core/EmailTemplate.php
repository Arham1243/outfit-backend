<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmailTemplate extends Model
{
    use LogsActivity;
    
    protected $table = 'email_templates';
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['bcc_recipients' => 'array', 'status' => 'boolean', 'is_system' => 'boolean'];

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
            ->useLogName('email_template');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
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
