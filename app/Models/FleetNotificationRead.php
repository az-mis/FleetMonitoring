<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One user has read one notification */
class FleetNotificationRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['fleet_notification_id', 'user_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
