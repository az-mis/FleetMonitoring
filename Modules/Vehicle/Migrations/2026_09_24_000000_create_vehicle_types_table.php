<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 60)->unique();
            $table->string('icon', 20)->default('car');
            $table->timestamps();
        });

        // The four types that already exist as chips on the Vehicles page.
        $now = now();
        DB::table('vehicle_types')->insert([
            ['name' => 'Car',        'slug' => 'car',        'icon' => 'car',        'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Truck',      'slug' => 'truck',      'icon' => 'truck',      'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Van',        'slug' => 'van',        'icon' => 'van',        'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Motorcycle', 'slug' => 'motorcycle', 'icon' => 'motorcycle', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
