<?php

namespace App\Models\Core;

use App\Models\Accounting\Receipt;
use App\Models\Accounting\ReceiptInvoicePivot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DeductionType extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

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
            ->useLogName('deduction_type');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'deduction_type_id');
    }

    public function invoiceReceiptDeductions()
    {
        return $this->hasMany(ReceiptInvoicePivot::class, 'deduction_type_id');
    }

    public function dependencies(): array
    {
        return [
            'receipts' => $this->receipts()->exists(),
            'invoiceReceiptDeductions' => $this->invoiceReceiptDeductions()->exists(),
        ];
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });

        static::updating(function ($model) {
            if ($model->is_system && $model->isDirty('status') && ! $model->status) {
                throw ValidationException::withMessages([
                    'status' => 'System deduction types cannot be deactivated.',
                ]);
            }
        });

        static::deleting(function ($model) {
            if ($model->is_system) {
                throw ValidationException::withMessages([
                    'deduction_type' => 'System deduction types cannot be deleted.',
                ]);
            }

            $dependencies = $model->dependencies();
            if (in_array(true, $dependencies, true)) {
                throw ValidationException::withMessages([
                    'deduction_type' => 'This deduction type is in use and cannot be deleted.',
                ]);
            }
        });
    }
}
