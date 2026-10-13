<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_pms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('category')->default('replace'); // replace, inspect, lubricate, other
            $table->unsignedInteger('interval_km')->nullable();     // e.g. 5000 km
            $table->unsignedInteger('interval_months')->nullable(); // e.g. 6 months
            $table->json('applicable_types')->nullable();          // null = all vehicle types, or array of strings ['Car', 'Truck']
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_pms_templates');
    }
};
