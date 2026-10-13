<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehiclePmsTemplate extends Model
{
    protected $fillable = [
        'item_name',
        'category',
        'interval_km',
        'interval_months',
        'applicable_types',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'applicable_types' => 'array',
        'is_active' => 'boolean',
        'interval_km' => 'integer',
        'interval_months' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pmsItems(): HasMany
    {
        return $this->hasMany(VehiclePmsItem::class, 'template_id');
    }

    /**
     * Checks if this template applies to a given vehicle type (e.g. 'Car', 'Truck', 'Van').
     */
    public function appliesToType(?string $vehicleType): bool
    {
        if (empty($this->applicable_types)) {
            return true; // applies to all vehicle types
        }

        if (!$vehicleType) {
            return true;
        }

        return in_array($vehicleType, $this->applicable_types, true);
    }
}
