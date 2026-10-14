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
        Schema::table('fleet_card_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('fleet_card_logs', 'liters_used')) {
                $table->decimal('liters_used', 10, 2)->nullable()->after('distance_km');
            }
            if (!Schema::hasColumn('fleet_card_logs', 'fuel_price_per_liter')) {
                $table->decimal('fuel_price_per_liter', 10, 2)->nullable()->after('liters_used');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleet_card_logs', function (Blueprint $table) {
            if (Schema::hasColumn('fleet_card_logs', 'fuel_price_per_liter')) {
                $table->dropColumn('fuel_price_per_liter');
            }
            if (Schema::hasColumn('fleet_card_logs', 'liters_used')) {
                $table->dropColumn('liters_used');
            }
        });
    }
};
