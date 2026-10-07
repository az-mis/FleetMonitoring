<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('card_number')->unique();
            $table->string('card_name');
            $table->string('location')->default('Palawan');
            $table->decimal('initial_budget', 12, 2)->default(5000.00);
            $table->decimal('rate_peso', 10, 2)->default(100.00);
            $table->decimal('rate_km', 10, 2)->default(5.00);
            $table->decimal('current_balance', 12, 2)->default(5000.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fleet_card_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_card_id')->constrained('fleet_cards')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('vehicle_request_id')->nullable()->constrained('vehicle_requests')->nullOnDelete();
            $table->string('distance_source')->default('odometer'); // odometer, destination_master, manual
            $table->integer('start_odometer')->nullable();
            $table->integer('end_odometer')->nullable();
            $table->decimal('distance_km', 10, 2);
            $table->decimal('rate_peso_per_km', 10, 4);
            $table->decimal('consumed_amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('remarks')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_card_logs');
        Schema::dropIfExists('fleet_cards');
    }
};
