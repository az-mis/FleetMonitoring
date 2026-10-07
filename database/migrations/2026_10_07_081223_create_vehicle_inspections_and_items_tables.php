<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('last_change_oil_odometer')->nullable()->default(0)->after('odometer');
            $table->unsignedInteger('change_oil_interval_km')->default(5000)->after('last_change_oil_odometer');
        });

        Schema::create('vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('odometer_km')->default(0);
            $table->string('overall_status')->default('passed'); // passed, needs_attention, failed
            $table->boolean('change_oil_done')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('inspected_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('vehicle_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('vehicle_inspections')->cascadeOnDelete();
            $table->string('category'); // Oils & Fluids, Brakes & Steering, Tires & Wheels, Electrical & Lights, Body & Cabin
            $table->string('item_name');
            $table->string('status')->default('pass'); // pass, attention, fail
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_inspection_items');
        Schema::dropIfExists('vehicle_inspections');
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['last_change_oil_odometer', 'change_oil_interval_km']);
        });
    }
};
