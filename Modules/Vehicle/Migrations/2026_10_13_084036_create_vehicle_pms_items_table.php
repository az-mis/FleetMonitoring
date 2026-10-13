<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_pms_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->string('category')->default('replace'); // replace, inspect, lubricate, other
            $table->unsignedInteger('interval_km')->nullable();   // e.g. 7500 km
            $table->unsignedInteger('interval_months')->nullable(); // e.g. 6 months
            $table->unsignedInteger('last_done_km')->nullable();
            $table->date('last_done_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_pms_items');
    }
};
