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
            $total += 2;
            $s = $item->status;
            if ($s === 'overdue')        { $overdue++; }
            elseif ($s === 'due_soon')   { $dueSoon++; $points += 1; }
            else                         { $onTrack++;  $points += 2; }
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
