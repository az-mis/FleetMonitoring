<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Models;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\Vehicle\Models\Vehicle;

class VehicleRequest extends Model
{
    protected $fillable = [
        'reference_code', 'requester_name', 'location', 'requester_office', 'office_id',
        'requester_contact', 'requester_is_passenger', 'vehicle_id',
        'destination', 'distance_km', 'travel_date', 'travel_date_end', 'purpose',
        'passengers', 'previous_trip_ticket_date', 'status', 'decline_reason',
        'trip_ticket_number', 'confirmed_driver_id', 'driver_name', 'approved_by', 'approved_at',
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

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function confirmedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_driver_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completion(): HasOne
    {
        return $this->hasOne(TripTicketCompletion::class, 'vehicle_request_id');
    }

    public function fleetCardLogs(): HasMany
    {
        return $this->hasMany(FleetCardLog::class, 'vehicle_request_id');
    }
}
