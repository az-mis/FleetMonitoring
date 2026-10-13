<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vehicle\Models\Vehicle;

class TripTicketCompletion extends Model
{
    protected $fillable = [
        'vehicle_request_id',
        'trip_ticket_number',
        'vehicle_id',
        'driver_id',
        'departure_garage_time',
        'arrival_destination_time',
        'departure_destination_time',
        'arrival_garage_time',
        'odo_beginning',
        'odo_ending',
        'distance_travelled',
        'fuel_divisor',
        'fuel_used_liters',
        'fuel_balance_in_tank',
        'fuel_issued_office',
        'fuel_purchased_trip',
        'fuel_total_liters',
        'fuel_ending_balance',
        'gear_oil_issued',
        'lub_oil_issued',
        'grease_issued',
        'remarks',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'odo_beginning' => 'decimal:2',
            'odo_ending' => 'decimal:2',
            'distance_travelled' => 'decimal:2',
            'fuel_divisor' => 'decimal:2',
            'fuel_used_liters' => 'decimal:2',
            'fuel_balance_in_tank' => 'decimal:2',
            'fuel_issued_office' => 'decimal:2',
            'fuel_purchased_trip' => 'decimal:2',
            'fuel_total_liters' => 'decimal:2',
            'fuel_ending_balance' => 'decimal:2',
            'gear_oil_issued' => 'decimal:2',
            'lub_oil_issued' => 'decimal:2',
            'grease_issued' => 'decimal:2',
        ];
    }

    public function vehicleRequest(): BelongsTo
    {
        return $this->belongsTo(VehicleRequest::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
