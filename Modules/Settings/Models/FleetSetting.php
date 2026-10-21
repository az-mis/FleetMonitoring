<?php

declare(strict_types=1);

namespace Modules\Settings\Models;

use App\Models\User;
use App\Support\FleetSettings;
use Illuminate\Database\Eloquent\Model;

class FleetSetting extends Model
{
    protected $fillable = ['setting_key', 'setting_value', 'updated_by'];

    protected static function booted(): void
    {
        // Settings are read once per request (App\Support\FleetSettings): drop that copy when one changes
        static::saved(fn () => app(FleetSettings::class)->forget());
        static::deleted(fn () => app(FleetSettings::class)->forget());
    }

    protected function casts(): array
    {
        return ['setting_value' => 'array'];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
