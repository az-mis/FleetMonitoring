<?php

declare(strict_types=1);

namespace Modules\VehicleReport\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;

class VehicleIssueReport extends Model
{
    protected $fillable = [
        'vehicle_id',
        'plate_number',
        'vehicle_label',
        'driver_id',
        'driver_name',
        'issue_type',
        'priority',
        'description',
        'status',
        'admin_notes',
        'related_maintenance_id',
        'related_maintenance_title',
        'location',
        'reviewed_by',
        'reviewed_by_name',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function relatedMaintenance(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenance::class, 'related_maintenance_id');
    }
}
