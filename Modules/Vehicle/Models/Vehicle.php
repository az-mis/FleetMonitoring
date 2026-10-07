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
