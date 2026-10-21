<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fleet card money:
 * - vehicles.fuel_economy_km_per_liter: a distance-only charge costs km ÷ km/L × price per litre
 *   (it used to charge the litre price per kilometre);
 * - one charge per trip, enforced by the database (two quick clicks could charge a trip twice);
 * - fleet_cards.initial_budget goes back to meaning the starting allocation. Top-ups used to raise it
 *   (only sometimes), so it is recomputed from the ledger: balance = starting budget + top-ups − expenses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('fuel_economy_km_per_liter', 5, 2)->nullable();
        });

        Schema::table('fleet_card_logs', function (Blueprint $table) {
            $table->unique('vehicle_request_id');
        });

        // Top-ups are logged with a negative amount, so the sum of all amounts is expenses − top-ups
        DB::table('fleet_cards')->orderBy('id')->each(function (object $card) {
            $net = (float) DB::table('fleet_card_logs')->where('fleet_card_id', $card->id)->sum('consumed_amount');
            $starting = round((float) $card->current_balance + $net, 2);
            if ($starting >= 0 && abs($starting - (float) $card->initial_budget) > 0.004) {
                DB::table('fleet_cards')->where('id', $card->id)->update(['initial_budget' => $starting]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('fleet_card_logs', function (Blueprint $table) {
            $table->dropUnique(['vehicle_request_id']);
        });
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('fuel_economy_km_per_liter');
        });
    }
};
