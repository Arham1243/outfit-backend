<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InvoiceTemplate extends Model
{
    use LogsActivity;
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['is_default' => 'boolean', 'bcc_recipients' => 'array', 'print_payment_link' => 'boolean',];

    protected $appends = ['preview_path_url', 'preview_multiple_path_url'];

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
            ->useLogName('invoice_template');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getPreviewPathUrlAttribute()
    {
        return $this->preview_path ? asset($this->preview_path) : null;
    }

    public function getPreviewMultiplePathUrlAttribute()
    {
        return $this->preview_multiple_path ? asset($this->preview_multiple_path) : null;
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
