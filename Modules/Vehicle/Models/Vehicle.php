<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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
    ];

    public function inspections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehicleInspection::class)->latest('inspected_at');
    }

    public function pmsItems(): \Illuminate\Database\Eloquent\Relations\HasMany
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
                        'template_id'     => $template->id,
                        'category'        => $template->category,
                        'interval_km'     => $template->interval_km,
                        'interval_months' => $template->interval_months,
                    ]);
                } else {
                    // Auto-seed from template
                    $this->pmsItems()->create([
                        'template_id'     => $template->id,
                        'item_name'       => $template->item_name,
                        'category'        => $template->category,
                        'interval_km'     => $template->interval_km,
                        'interval_months' => $template->interval_months,
                        'notes'           => $template->notes,
                    ]);
                }
            }
        }
    }

    public function maintenances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Maintenance\Models\VehicleMaintenance::class);
    }

    /**
     * Compute a 0–100 Vehicle Health Score.
     * Formula: equally weighted across oil status, PMS items, active maintenance burden.
     */
    public function getHealthScoreAttribute(): array
    {
        $points = 0;
        $total  = 0;

        // ── Oil Change ────────────────────────────────────────────
        $oilStatus = $this->change_oil_status['status'];
        $total += 2;
        $points += match ($oilStatus) {
            'ok'       => 2,
            'due_soon' => 1,
            default    => 0,
        };

        // ── PMS Items ─────────────────────────────────────────────
        $pmsItems = $this->relationLoaded('pmsItems') ? $this->pmsItems : $this->pmsItems()->get();
        $overdue  = 0;
        $dueSoon  = 0;
        $onTrack  = 0;
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
        $overdueMaint = $this->maintenances()
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->whereDate('scheduled_date', '<', now()->toDateString())
            ->count();
        $total  += 2;
        $points += $overdueMaint === 0 ? 2 : 0;

        $score = $total > 0 ? (int) round(($points / $total) * 100) : 100;

        return [
            'score'    => $score,
            'overdue'  => $overdue,
            'due_soon' => $dueSoon,
            'on_track' => $onTrack,
            'label'    => $score >= 80 ? 'Good' : ($score >= 50 ? 'Fair' : 'Poor'),
            'color'    => $score >= 80 ? 'emerald' : ($score >= 50 ? 'amber' : 'red'),
        ];
    }

    public function latestInspection(): \Illuminate\Database\Eloquent\Relations\HasOne
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
            $label = 'Change Oil Overdue (' . number_format(abs($diff)) . ' km past due)';
            $color = 'red';
        } elseif ($diff <= 500) {
            $status = 'due_soon';
            $label = 'Change Oil Due Soon (' . number_format($diff) . ' km remaining)';
            $color = 'amber';
        } else {
            $status = 'ok';
            $label = 'Change Oil OK (' . number_format($diff) . ' km remaining)';
            $color = 'emerald';
        }

        return [
            'status'     => $status,
            'label'      => $label,
            'color'      => $color,
            'next_km'    => $nextOil,
            'diff_km'    => $diff,
            'last_km'    => $lastOil,
            'interval'   => $interval,
        ];
    }

    protected $appends = ['photo_url'];

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? Storage::disk('public')->url($this->photo) : null,
        );
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function assignedDriver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_driver_id');
    }
}
