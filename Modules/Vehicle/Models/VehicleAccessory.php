<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An accessory recorded on a vehicle's property record (e.g. bedliner, canopy, back-up camera). */
class VehicleAccessory extends Model
{
    protected $fillable = ['vehicle_id', 'description', 'date_acquired', 'value'];

    protected function casts(): array
    {
        return ['date_acquired' => 'date', 'value' => 'decimal:2'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
