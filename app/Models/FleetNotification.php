<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A saved bell notification (see App\Services\Notifications\NotificationFeed) */
class FleetNotification extends Model
{
    public const MAINTENANCE_DUE = 'maint_due';

    public const MAINTENANCE_COMPLETION = 'maint_completion';

    public const OIL_CHANGE = 'oil';

    public const TRAVEL_REQUEST = 'travel';

    protected $fillable = ['key', 'type', 'location', 'subject_id', 'data', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function reads(): HasMany
    {
        return $this->hasMany(FleetNotificationRead::class);
    }
}
