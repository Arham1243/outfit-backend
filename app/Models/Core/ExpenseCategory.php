<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExpenseCategory extends Model
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
            ->useLogName('expense_category');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function parent()
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    public function dependencies(): array
    {
        return [
            'children' => $this->children()->exists(),
        ];
    }

    public function adminModuleDependencies(): array
    {
        return [];
    }

    public function children()
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id');
    }

    protected static function booted()
    {
            static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });

        static::saving(function ($expenseCategory) {
            $expenseCategory->display_name = ($expenseCategory->parent && $expenseCategory->parent->name)
                ? $expenseCategory->parent->name . ': ' . $expenseCategory->name
                : $expenseCategory->name;
        });

        static::updated(function ($expenseCategory) {
            if ($expenseCategory->isDirty('name')) {
                $expenseCategory->children()->each(function ($child) use ($expenseCategory) {
                    $child->update([
                        'display_name' => $expenseCategory->name . ': ' . $child->name,
                    ]);
                });
            }
        });

        static::updating(function ($expenseCategory) {
            if ($expenseCategory->is_system && $expenseCategory->isDirty('status') && ! $expenseCategory->status) {
                throw ValidationException::withMessages([
                    'status' => 'System expense accounts cannot be deactivated.',
                ]);
            }

            if ($expenseCategory->isDirty('status') && $expenseCategory->status == 0) {

                $dependencies = $expenseCategory->adminModuleDependencies();

                if (in_array(true, $dependencies, true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Cannot set status to inactive because this account is in use.',
                    ]);
                }
            }
        });

        static::deleting(function ($model) {
            if ($model->is_system) {
                throw ValidationException::withMessages([
                    'expense_category' => 'System expense accounts cannot be deleted.',
                ]);
            }

            $dependencies = $model->dependencies();
            if (in_array(true, $dependencies, true)) {
                throw ValidationException::withMessages([
                    'expense_category' => 'This expense account is in use and cannot be deleted.',
                ]);
            }
        });
    }
}
