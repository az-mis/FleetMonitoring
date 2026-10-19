<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One period during which a driver was the primary driver of a vehicle. */
class VehicleAssignmentHistory extends Model
{
    protected $fillable = ['vehicle_id', 'driver_id', 'driver_name', 'assigned_at', 'unassigned_at', 'assigned_by'];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
