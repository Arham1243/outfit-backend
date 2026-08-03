<?php

namespace App\Models\Core;

use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuid;
    use LogsActivity;

    protected $casts = [
        'is_system' => 'boolean',
        'status' => 'boolean',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function assignedUsers()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function dependencies(): array
    {
        return [
            'assignedUsers' => $this->assignedUsers()->exists(),
        ];
    }

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
            ->useLogName('role');
    }

    protected static function booted()
    {
        static::updating(function (Role $role) {
            if ($role->getOriginal('is_system')) {
                throw ValidationException::withMessages([
                    'role' => 'System roles cannot be updated.',
                ]);
            }
        });

        static::deleting(function (Role $role) {
            if ($role->is_system) {
                throw ValidationException::withMessages([
                    'role' => 'System roles cannot be deleted.',
                ]);
            }

            $dependencies = $role->dependencies();
            if (in_array(true, $dependencies, true)) {
                throw ValidationException::withMessages([
                    'role' => 'This role is assigned to one or more users and cannot be deleted.',
                ]);
            }
        });
    }
}
