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
        'vehicle_type',
        'fuel_type',
    ];

    protected $appends = ['photo_url'];

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? Storage::disk('public')->url($this->photo) : null,
        );
    }
}
