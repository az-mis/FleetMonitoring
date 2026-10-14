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
            if (!Schema::hasColumn('fleet_card_logs', 'category')) {
                $table->string('category', 50)->default('fuel')->after('distance_source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleet_card_logs', function (Blueprint $table) {
            if (Schema::hasColumn('fleet_card_logs', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
