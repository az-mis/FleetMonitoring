<?php

namespace Modules\FleetCard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;

class FleetCardLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_card_id',
        'vehicle_id',
        'vehicle_request_id',
        'distance_source',
        'start_odometer',
        'end_odometer',
        'distance_km',
        'rate_peso_per_km',
        'consumed_amount',
        'balance_before',
        'balance_after',
        'remarks',
        'logged_by',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'rate_peso_per_km' => 'decimal:4',
        'consumed_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'start_odometer' => 'integer',
        'end_odometer' => 'integer',
    ];

    public function fleetCard(): BelongsTo
    {
        return $this->belongsTo(FleetCard::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vehicleRequest(): BelongsTo
    {
        return $this->belongsTo(VehicleRequest::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
