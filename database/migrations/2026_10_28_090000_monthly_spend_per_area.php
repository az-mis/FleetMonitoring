<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monthly Spend statements are kept per area: two areas can each record "Shell / AFD / October", and a
 * region-wide statement (no location, as imported from the workbook) no longer collides with them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_card_monthly_spend', function (Blueprint $table) {
            $table->unique(['provider', 'division_label', 'location', 'year', 'month'], 'fleet_card_monthly_spend_area_unique');
        });
        Schema::table('fleet_card_monthly_spend', function (Blueprint $table) {
            $table->dropUnique('fleet_card_monthly_spend_unique');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_card_monthly_spend', function (Blueprint $table) {
            $table->unique(['provider', 'division_label', 'year', 'month'], 'fleet_card_monthly_spend_unique');
        });
        Schema::table('fleet_card_monthly_spend', function (Blueprint $table) {
            $table->dropUnique('fleet_card_monthly_spend_area_unique');
        });
    }
};
