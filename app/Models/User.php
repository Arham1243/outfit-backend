<?php

namespace App\Models;

use App\Models\Core\GeneratedOutfit;
use App\Models\Core\Language;
use App\Models\Core\Role;
use App\Models\Core\Wardrobe;
use App\Support\BaseModelFingerprint;
use App\Support\FaceMode;
use App\Traits\HasFormattedDates;
use App\Traits\HasUuid;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFormattedDates, HasPermissions, HasRoles, HasUuid;

    protected $table = 'users';

    protected $guard_name = 'sanctum';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'dark_mode' => 'boolean',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public $dateFields = [
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    public function setEmailAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['email'] = $value;

            return;
        }

        $this->attributes['email'] = mb_strtolower(trim((string) $value));
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->role_id && ! is_numeric($model->role_id)) {
                $role = \App\Models\Core\Role::where('uuid', $model->role_id)->first();
                $model->role_id = $role ? $role->id : null;
            }
        });
    }
    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image ? asset($this->profile_image) : null;
    }

    public function getFaceImageUrlAttribute(): ?string
    {
        return $this->face_image ? asset($this->face_image) : null;
    }

    public function getBaseModelImageUrlAttribute(): ?string
    {
        return $this->base_model_image ? asset($this->base_model_image) : null;
    }

    public function baseModelFingerprint(): string
    {
        return BaseModelFingerprint::for(
            $this->height,
            $this->gender,
            null,
            $this->faceMode(),
            is_string($this->face_image) ? $this->face_image : null
        );
    }

    public function faceMode(): string
    {
        $mode = is_string($this->face_mode) ? $this->face_mode : FaceMode::AI_MODEL;

        return in_array($mode, FaceMode::all(), true) ? $mode : FaceMode::AI_MODEL;
    }

    public function usesFaceImage(): bool
    {
        return FaceMode::requiresFaceImage($this->faceMode());
    }

    public function hasValidBaseModelCache(): bool
    {
        return ! empty($this->base_model_image)
            && ! empty($this->base_model_fingerprint)
            && $this->base_model_fingerprint === $this->baseModelFingerprint();
    }

    public function clearBaseModelCache(): void
    {
        $this->base_model_image = null;
        $this->base_model_fingerprint = null;
    }

    public function preferredLanguage()
    {
        return $this->belongsTo(Language::class, 'preferred_language_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function wardrobes()
    {
        return $this->hasMany(Wardrobe::class);
    }

    public function generatedOutfits()
    {
        return $this->hasMany(GeneratedOutfit::class);
    }

    /**
     * Tenant app permissions come from users.role_id → role_has_permissions.
     *
     * @return array<int, string>
     */
    public function rolePermissionNames(): array
    {
        return $this->getAllPermissions()->pluck('name')->values()->all();
    }

    public function getAllPermissions(): Collection
    {
        $this->loadMissing('role.permissions');

        return $this->role?->permissions ?? collect();
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (! $this->role) {
            return false;
        }

        return $this->role->hasPermissionTo($permission, $guardName);
    }

    public function recordSuccessfulLogin(): bool
    {
        $this->last_login_at = now();
        $this->save();

        return $this->wasRecentlyCreated || $this->last_login_at->diffInMinutes($this->created_at) < 5;
    }
}
