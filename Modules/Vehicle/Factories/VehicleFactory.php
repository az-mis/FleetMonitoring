<?php

declare(strict_types=1);

namespace Modules\Vehicle\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Vehicle\Models\Vehicle;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Brand => models map, so we generate realistic pairings instead of
     * a random brand next to an unrelated model name.
     *
     * @var array<string, array<int, string>>
     */
    private array $catalog = [
        'Toyota' => ['Hilux', 'Fortuner', 'Innova', 'Vios', 'Corolla Altis', 'Land Cruiser', 'Camry', 'Avanza'],
        'Mitsubishi' => ['Montero Sport', 'Xpander', 'Mirage', 'L300', 'Strada', 'Outlander'],
        'Ford' => ['Ranger', 'Everest', 'Territory', 'Explorer', 'Ecosport'],
        'Honda' => ['Civic', 'City', 'CR-V', 'BR-V', 'Accord'],
        'Nissan' => ['Navara', 'Terra', 'Almera', 'X-Trail', 'Patrol'],
        'Isuzu' => ['D-Max', 'MU-X', 'Traviz'],
        'Hyundai' => ['Accent', 'Tucson', 'Starex', 'Santa Fe', 'H-100'],
        'Suzuki' => ['Ertiga', 'Jimny', 'S-Presso', 'APV'],
        'Kia' => ['Sportage', 'Carnival', 'Soluto', 'Seltos'],
        'Chevrolet' => ['Trailblazer', 'Colorado', 'Spark'],
    ];

    /**
     * @var array<int, string>
     */
    private array $colors = [
        'White', 'Black', 'Silver', 'Gray', 'Red', 'Blue', 'Dark Green',
        'Beige', 'Maroon', 'Bronze', 'Pearl White', 'Titanium Gray',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brand = array_rand($this->catalog);
        $model = $this->faker->randomElement($this->catalog[$brand]);

        return [
            'plate_number' => $this->uniquePlateNumber(),
            'photo' => null,
            'chassis_number' => strtoupper($this->faker->unique()->bothify('#?#??#?#??######')),
            'engine_number' => strtoupper($this->faker->unique()->bothify('#?#-#######')),
            'brand' => $brand,
            'model' => $model,
            'year' => $this->faker->numberBetween(2008, (int) date('Y')),
            'color' => $this->faker->randomElement($this->colors),
            'odometer' => $this->faker->numberBetween(500, 180000),
            'vehicle_type' => $this->faker->randomElement(['Car', 'Truck', 'Van', 'Motorcycle']),
            'fuel_type' => $this->faker->randomElement(['gasoline', 'diesel', 'electric', 'hybrid', 'other']),
        ];
    }

    /**
     * Generate a plate number in the common "ABC 1234" format, unique
     * within this factory run.
     */
    private function uniquePlateNumber(): string
    {
        do {
            $plate = strtoupper($this->faker->bothify('???')) . ' ' . $this->faker->numberBetween(1000, 9999);
        } while (Vehicle::query()->where('plate_number', $plate)->exists());

        return $plate;
    }
}
