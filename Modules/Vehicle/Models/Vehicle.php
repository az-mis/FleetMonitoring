<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\FleetCard\Models\FleetCard;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Factories\VehicleFactory;

class Vehicle extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return VehicleFactory::new();
    }

    protected $fillable = [
        'plate_number',
        'photo',
        'chassis_number',
        'engine_number',
        'brand',
        'model',
        'year',
        'color',
        'odometer',
        'last_change_oil_odometer',
        'change_oil_interval_km',
        'vehicle_type',
        'fuel_type',
        'assigned_driver_id',
        'location',
        'created_by',
        'updated_by',
        // Property record and paperwork (GSS workbook)
        'par_number',
        'property_number',
        'old_plate_number',
        'conduction_sticker',
        'acquisition_date',
        'acquisition_cost',
        'custodian_name',
        'end_user_office_id',
        'assigned_person',
        'book_status',
        'condition',
        'registry_remarks',
        'history',
        'or_cr_status',
        'registration_place',
        'registration_expiry',
        'renewal_schedule',
        'insurance_type',
        'insurance_from',
        'insurance_to',
        'tire_type',
        'has_ewd',
        'tools',
        'fleet_card_allowance',
        'fuel_economy_km_per_liter',
    ];

    /** Days before an expiry date that the papers count as "due" */
    public const PAPERS_DUE_DAYS = 30;

    /** Conditions (Property & Registration) that keep a vehicle out of new trips */
    public const UNUSABLE_CONDITIONS = ['Unserviceable', 'For disposal'];

    /** Vehicles that may be booked: not marked Unserviceable or For disposal */
    public function scopeBookable(Builder $query): void
    {
        $query->where(fn ($q) => $q->whereNull('condition')->orWhereNotIn('condition', self::UNUSABLE_CONDITIONS));
    }

    public function isBookable(): bool
    {
        return ! in_array($this->condition, self::UNUSABLE_CONDITIONS, true);
    }

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'registration_expiry' => 'date',
            'insurance_from' => 'date',
            'insurance_to' => 'date',
            'has_ewd' => 'boolean',
            'fleet_card_allowance' => 'decimal:2',
            'fuel_economy_km_per_liter' => 'decimal:2',
        ];
    }

    public function accessories(): HasMany
    {
        return $this->hasMany(VehicleAccessory::class)->orderBy('date_acquired')->orderBy('id');
    }

    public function endUserOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'end_user_office_id');
    }

    /** Vehicles whose registration or insurance has expired or expires within PAPERS_DUE_DAYS */
    public function scopePapersDue(Builder $query): void
    {
        $limit = now()->addDays(self::PAPERS_DUE_DAYS)->toDateString();
        $query->where(fn ($q) => $q->whereDate('registration_expiry', '<=', $limit)->orWhereDate('insurance_to', '<=', $limit));
    }

    /** Registration status worked out from the expiry date: label + tone (success, warning, danger, neutral) */
    public function registrationStatus(): array
    {
        return self::expiryStatus($this->registration_expiry, 'Registered', 'No registration date');
    }

    public function insuranceStatus(): array
    {
        return self::expiryStatus($this->insurance_to, 'Insured', 'No insurance date');
    }

    private static function expiryStatus(?Carbon $date, string $okLabel, string $unknownLabel): array
    {
        if (! $date) {
            return ['label' => $unknownLabel, 'tone' => 'neutral', 'days' => null];
        }
        $days = (int) today()->diffInDays($date, false);
        if ($days < 0) {
            return ['label' => 'Expired '.$date->format('M j, Y'), 'tone' => 'danger', 'days' => $days];
        }
        if ($days <= self::PAPERS_DUE_DAYS) {
            return ['label' => 'Expires '.$date->format('M j, Y').' ('.$days.'d)', 'tone' => 'warning', 'days' => $days];
        }

        return ['label' => $okLabel.' until '.$date->format('M j, Y'), 'tone' => 'success', 'days' => $days];
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(VehicleInspection::class)->latest('inspected_at');
    }

    public function pmsItems(): HasMany
    {
        return $this->hasMany(VehiclePmsItem::class)->orderBy('category')->orderBy('item_name');
    }

    /**
     * Synchronize this vehicle's PMS items with active global templates that apply to its vehicle_type.
     */
    public function syncPmsWithTemplates(): void
    {
        $templates = VehiclePmsTemplate::where('is_active', true)->get();

        foreach ($templates as $template) {
            if ($template->appliesToType($this->vehicle_type)) {
                // Find existing item linked to this template or with the same name
                $item = $this->pmsItems()->where(function ($q) use ($template) {
                    $q->where('template_id', $template->id)
                        ->orWhere('item_name', $template->item_name);
                })->first();

                if ($item) {
                    // Update intervals/category from template if not customized
                    $item->update([
                        'template_id' => $template->id,
                        'category' => $template->category,
                        'interval_km' => $template->interval_km,
                        'interval_months' => $template->interval_months,
                    ]);
                } else {
                    // Auto-seed from template
                    $this->pmsItems()->create([
                        'template_id' => $template->id,
                        'item_name' => $template->item_name,
                        'category' => $template->category,
                        'interval_km' => $template->interval_km,
                        'interval_months' => $template->interval_months,
                        'notes' => $template->notes,
                    ]);
                }
            }
        }
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function fleetCards(): HasMany
    {
        return $this->hasMany(FleetCard::class);
    }

    /** Monthly allowance set on the vehicle's registered fleet card(s), or null when no card sets one */
    public function cardAllowance(): ?float
    {
        $amounts = $this->fleetCards->pluck('monthly_allowance')->filter(fn ($amount) => $amount !== null);

        return $amounts->isEmpty() ? null : (float) $amounts->sum();
    }

    /** The allowance GSS lists for the vehicle: its card's once one sets it, else the amount kept on the vehicle */
    public function effectiveFleetCardAllowance(): ?float
    {
        return $this->cardAllowance() ?? ($this->fleet_card_allowance !== null ? (float) $this->fleet_card_allowance : null);
    }

    /**
     * Compute a 0–100 Vehicle Health Score.
     * Formula: equally weighted across oil status, PMS items, active maintenance burden.
     */
    public function getHealthScoreAttribute(): array
    {
        return $this->healthScore ??= $this->computeHealthScore();
    }

    /**
     * What the health score needs, loaded for a whole list at once (instead of two queries per vehicle)
     */
    public function scopeWithHealthData(Builder $query): void
    {
        // chaperone(): each loaded item already knows its vehicle, so its status does not query it again
        // (only here: on the PMS JSON it would copy the whole vehicle into every item)
        $query->with(['pmsItems' => fn ($q) => $q->chaperone('vehicle')])->withCount(['maintenances as overdue_maintenance_count' => fn ($q) => $q
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->where('scheduled_date', '<', now()->toDateString())]);
    }

    private function computeHealthScore(): array
    {
        $points = 0;
        $total = 0;

        // ── Oil Change ────────────────────────────────────────────
        $oilStatus = $this->change_oil_status['status'];
        $total += 2;
        $points += match ($oilStatus) {
            'ok' => 2,
            'due_soon' => 1,
            default => 0,
        };

        // ── PMS Items ─────────────────────────────────────────────
        $pmsItems = $this->relationLoaded('pmsItems') ? $this->pmsItems : $this->pmsItems()->get();
        $overdue = 0;
        $dueSoon = 0;
        $onTrack = 0;
        foreach ($pmsItems as $item) {
            $s = $item->status;
            if ($s === 'overdue') {
                $total += 2;
                $overdue++;
            } elseif ($s === 'due_soon') {
                $total += 2;
                $dueSoon++;
                $points += 1;
            } elseif ($s === 'ok') {
                $total += 2;
                $onTrack++;
                $points += 2;
            }
            // 'unknown' items (not started yet, no interval or no last done) do not artificially inflate or penalize score
        }

        // ── Active Maintenance (overdue) ──────────────────────────
        $overdueMaint = $this->overdue_maintenance_count ?? $this->maintenances()
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->where('scheduled_date', '<', now()->toDateString())
            ->count();
        $total += 2;
        $points += $overdueMaint === 0 ? 2 : 0;

        $score = $total > 0 ? (int) round(($points / $total) * 100) : 100;

        return [
            'score' => $score,
            'overdue' => $overdue,
            'due_soon' => $dueSoon,
            'on_track' => $onTrack,
            'label' => $score >= 80 ? 'Good' : ($score >= 50 ? 'Fair' : 'Poor'),
            'color' => $score >= 80 ? 'emerald' : ($score >= 50 ? 'amber' : 'red'),
        ];
    }

    public function latestInspection(): HasOne
    {
        return $this->hasOne(VehicleInspection::class)->latestOfMany('inspected_at');
    }

    /**
     * Get Change Oil status & distance metrics
     */
    public function getChangeOilStatusAttribute(): array
    {
        $interval = $this->change_oil_interval_km ?: 5000;
        $lastOil = $this->last_change_oil_odometer ?: 0;
        $nextOil = $lastOil + $interval;
        $currentOdo = $this->odometer ?: 0;
        $diff = $nextOil - $currentOdo;

        if ($diff < 0) {
            $status = 'overdue';
            $label = 'Change Oil Overdue ('.number_format(abs($diff)).' km past due)';
            $color = 'red';
        } elseif ($diff <= 500) {
            $status = 'due_soon';
            $label = 'Change Oil Due Soon ('.number_format($diff).' km remaining)';
            $color = 'amber';
        } else {
            $status = 'ok';
            $label = 'Change Oil OK ('.number_format($diff).' km remaining)';
            $color = 'emerald';
        }

        return [
            'status' => $status,
            'label' => $label,
            'color' => $color,
            'next_km' => $nextOil,
            'diff_km' => $diff,
            'last_km' => $lastOil,
            'interval' => $interval,
        ];
    }

    protected $appends = ['photo_url'];

    /** health_score, worked out once per loaded vehicle (lists and the dashboard read it more than once) */
    private ?array $healthScore = null;

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? Storage::disk('public')->url($this->photo) : null,
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    /** Primary-driver history, newest first (decision D7). */
    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(VehicleAssignmentHistory::class)->latest('id');
    }
}
