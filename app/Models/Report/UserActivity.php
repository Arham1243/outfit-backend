<?php

namespace App\Models\Report;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserActivity extends Model
{
    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'user_name',
        'email',
        'event',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $event, ?User $user, ?string $email, Request $request): ?self
    {
        try {
            return self::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'email' => $email ?? $user?->email,
                'event' => $event,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to log user activity', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
