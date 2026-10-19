<?php

declare(strict_types=1);

namespace Modules\FleetCard\Models;

use App\Models\Office;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A division's fleet card spend for one month, as read from the provider's statement (or imported from
 * the GSS workbook). Months without one are worked out from the cards' logged expenses.
 */
class FleetCardMonthlySpend extends Model
{
    protected $table = 'fleet_card_monthly_spend';

    protected $fillable = [
        'provider', 'office_id', 'division_label', 'location', 'year', 'month', 'amount', 'source',
        'excluded_from_total', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'amount' => 'decimal:2',
            'excluded_from_total' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
