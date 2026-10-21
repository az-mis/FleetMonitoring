<?php

namespace Modules\FleetCard\Models;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
     * Price of one kilometre: the fuel price per litre divided by the vehicle's fuel economy (km per litre).
     * Null when the vehicle has no fuel economy set, so a distance-only charge cannot be priced.
     */
    public function pricePerKm(?Vehicle $vehicle = null): ?float
    {
        $kmPerLiter = (float) (($vehicle ?? $this->vehicle)?->fuel_economy_km_per_liter ?? 0);

        return $kmPerLiter > 0 && (float) $this->rate_peso > 0 ? round((float) $this->rate_peso / $kmPerLiter, 4) : null;
    }

    public function getCostPerKmAttribute(): float
    {
        return $this->pricePerKm() ?? 0.0;
    }

    /** About how far the balance still goes at the vehicle's fuel economy */
    public function getRemainingKmCapacityAttribute(): float
    {
        $pricePerKm = $this->pricePerKm();

        return $pricePerKm ? max(0.0, (float) $this->current_balance / $pricePerKm) : 0.0;
    }

    /** Top-ups and expenses summed in the query, for lists (top-ups are logged with a negative amount) */
    public function scopeWithLedgerTotals(Builder $query): void
    {
        $query->withSum(['logs as top_ups_sum' => fn ($q) => $q->where('consumed_amount', '<', 0)], 'consumed_amount')
            ->withSum(['logs as spent_sum' => fn ($q) => $q->where('consumed_amount', '>', 0)], 'consumed_amount');
    }

    public function getTotalTopUpsAttribute(): float
    {
        $sum = array_key_exists('top_ups_sum', $this->attributes)
            ? $this->attributes['top_ups_sum']
            : $this->logs()->where('consumed_amount', '<', 0)->sum('consumed_amount');

        return abs((float) $sum);
    }

    public function getTotalSpentAttribute(): float
    {
        return (float) (array_key_exists('spent_sum', $this->attributes)
            ? $this->attributes['spent_sum']
            : $this->logs()->where('consumed_amount', '>', 0)->sum('consumed_amount'));
    }

    /** Everything put on the card: the starting budget plus every top-up */
    public function getTotalAllocatedAttribute(): float
    {
        return (float) $this->initial_budget + $this->total_top_ups;
    }

    /** Percent of everything put on the card that is still left */
    public function getBalancePercentageAttribute(): float
    {
        $allocated = $this->total_allocated;

        return $allocated > 0 ? round(((float) $this->current_balance / $allocated) * 100, 1) : 0.0;
    }
}
