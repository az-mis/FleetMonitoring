<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vehicle\Models\Vehicle;

class VehicleRequest extends Model
{
    protected $fillable = [
        'reference_code', 'requester_name', 'location', 'requester_office',
        'requester_contact', 'requester_is_passenger', 'vehicle_id',
        'destination', 'distance_km', 'travel_date', 'travel_date_end', 'purpose',
        'passengers', 'previous_trip_ticket_date', 'status', 'decline_reason',
        'trip_ticket_number', 'confirmed_driver_id', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'requester_is_passenger' => 'boolean',
            'passengers' => 'array',
            'distance_km' => 'integer',
            'travel_date' => 'date',
            'travel_date_end' => 'date',
            'previous_trip_ticket_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function confirmedDriver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'confirmed_driver_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
