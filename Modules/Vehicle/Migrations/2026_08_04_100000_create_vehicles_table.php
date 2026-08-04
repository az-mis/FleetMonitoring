<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('photo')->nullable();
            $table->string('chassis_number');
            $table->string('engine_number');
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->string('color');
            $table->unsignedInteger('odometer')->default(0);
            $table->string('vehicle_type')->nullable();
            $table->string('fuel_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
