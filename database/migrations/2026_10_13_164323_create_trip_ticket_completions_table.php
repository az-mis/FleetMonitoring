<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_ticket_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_request_id')->constrained('vehicle_requests')->cascadeOnDelete();
            $table->string('trip_ticket_number')->index();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();

            // Part B.1 to B.4: Times
            $table->string('departure_garage_time')->nullable();
            $table->string('arrival_destination_time')->nullable();
            $table->string('departure_destination_time')->nullable();
            $table->string('arrival_garage_time')->nullable();

            // Speedometer Readings & Distance
            $table->decimal('odo_beginning', 10, 2)->default(0);
            $table->decimal('odo_ending', 10, 2)->default(0);
            $table->decimal('distance_travelled', 10, 2)->default(0);

            // Fuel Consumption Math
            $table->decimal('fuel_divisor', 8, 2)->nullable(); // Admin entered km/L
            $table->decimal('fuel_used_liters', 10, 2)->default(0); // distance / divisor

            // Fuel Tank Accounting
            $table->decimal('fuel_balance_in_tank', 10, 2)->default(0); // (a)
            $table->decimal('fuel_issued_office', 10, 2)->default(0);   // (b)
            $table->decimal('fuel_purchased_trip', 10, 2)->default(0);  // (c)
            $table->decimal('fuel_total_liters', 10, 2)->default(0);    // (a + b + c)
            $table->decimal('fuel_ending_balance', 10, 2)->default(0);  // Total - fuel_used_liters

            // Other Consumables
            $table->decimal('gear_oil_issued', 10, 2)->nullable();
            $table->decimal('lub_oil_issued', 10, 2)->nullable();
            $table->decimal('grease_issued', 10, 2)->nullable();

            $table->text('remarks')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_ticket_completions');
    }
};
