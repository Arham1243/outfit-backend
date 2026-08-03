<?php

namespace App\Models\Core;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Language extends Model
{
    use HasFactory;
    use HasUuid;
    use LogsActivity;

    protected $table = 'languages';

    protected $fillable = [
        'code',
        'region_code',
        'name',
        'is_rtl',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_rtl' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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
            ->useLogName('language');
    }

    public function getLocaleAttribute(): string
    {
        $region = $this->region_code !== '' && $this->region_code !== null
            ? $this->region_code
            : null;

        return $region
            ? strtolower($this->code).'-'.strtoupper($region)
            : strtolower($this->code);
    }
}
