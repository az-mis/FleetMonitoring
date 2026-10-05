<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Destination\Models\Destination;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $palawanDestinations = [
            ['name' => 'Aborlan, Palawan', 'distance_km' => 69],
            ['name' => 'Narra, Palawan', 'distance_km' => 95],
            ['name' => 'Española, Palawan', 'distance_km' => 130],
            ['name' => 'Quezon, Palawan', 'distance_km' => 142],
            ['name' => 'Rizal, Palawan', 'distance_km' => 215],
            ['name' => 'Bataraza, Palawan', 'distance_km' => 232],
            ['name' => 'Brooke\'s Pt. Palawan', 'distance_km' => 190],
            ['name' => 'Rio-Tuba, Palawan', 'distance_km' => 270],
            ['name' => 'Roxas, Palawan', 'distance_km' => 146],
            ['name' => 'San Vicente, Palawan', 'distance_km' => 160],
            ['name' => 'Taytay, Palawan', 'distance_km' => 225],
            ['name' => 'El Nido, Palawan', 'distance_km' => 275],
            ['name' => 'El Nido, Sibaltan Palawan', 'distance_km' => 325],
            ['name' => 'Dumaran, Palawan', 'distance_km' => 200],
            ['name' => 'Puerto Princesa City Proper', 'distance_km' => 75],
        ];

        foreach ($palawanDestinations as $item) {
            Destination::updateOrCreate(
                [
                    'name' => $item['name'],
                    'location' => 'Palawan',
                ],
                [
                    'distance_km' => $item['distance_km'],
                    'is_active' => true,
                ]
            );
        }
    }
}
