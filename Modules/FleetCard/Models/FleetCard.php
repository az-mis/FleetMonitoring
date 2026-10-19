<?php

namespace Modules\FleetCard\Models;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Vehicle\Models\Vehicle;

class FleetCard extends Model
{
    use HasFactory;

    public const PROVIDERS = ['Petron', 'Shell'];

    /** Products the GSS workbook lists as allowed on a card */
    public const PRODUCTS = ['Diesel Max', 'Turbo Diesel', 'Diesel', 'Gasoline'];

    protected $fillable = [
        'vehicle_id',
        'card_number',
        'card_name',
        'provider',
        'location',
        'office_id',
        'initial_budget',
        'monthly_allowance',
        'allowed_products',
        'rate_peso',
        'rate_km',
        'current_balance',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'initial_budget' => 'decimal:2',
        'rate_peso' => 'decimal:2',
        'rate_km' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'monthly_allowance' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** The division or program the card is charged to */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FleetCardLog::class)->latest();
    }

    /**
     * Compute cost per kilometer (e.g., 100 pesos / 5 km = 20 pesos/km)
     */
    public function getCostPerKmAttribute(): float
    {
        if ((float) $this->rate_km <= 0) {
            return 0.0;
        }

        return (float) $this->rate_peso / (float) $this->rate_km;
    }

    /**
     * Compute remaining distance range capacity based on current balance
     */
    public function getRemainingKmCapacityAttribute(): float
    {
        $costPerKm = $this->cost_per_km;
        if ($costPerKm <= 0) {
            return 0.0;
        }

        return (float) $this->current_balance / $costPerKm;
    }

    /**
     * Percent of budget remaining
     */
    public function getBalancePercentageAttribute(): float
    {
        if ((float) $this->initial_budget <= 0) {
            return 0.0;
        }

        return round(((float) $this->current_balance / (float) $this->initial_budget) * 100, 1);
    }
}
