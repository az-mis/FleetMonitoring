<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleInspection extends Model
{
    protected $fillable = [
        'vehicle_id',
        'inspector_id',
        'odometer_km',
        'overall_status',
        'change_oil_done',
        'notes',
        'inspected_at',
    ];

    protected $casts = [
        'change_oil_done' => 'boolean',
        'inspected_at'     => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VehicleInspectionItem::class, 'inspection_id');
    }
}
