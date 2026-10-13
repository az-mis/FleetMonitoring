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
        Schema::table('vehicle_pms_items', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('vehicle_id')->constrained('vehicle_pms_templates')->nullOnDelete();
        });

        Schema::table('vehicle_maintenances', function (Blueprint $table) {
            $table->foreignId('pms_item_id')->nullable()->after('source_report_id')->constrained('vehicle_pms_items')->nullOnDelete();
            $table->foreignId('pms_template_id')->nullable()->after('pms_item_id')->constrained('vehicle_pms_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_maintenances', function (Blueprint $table) {
            $table->dropForeign(['pms_item_id']);
            $table->dropForeign(['pms_template_id']);
            $table->dropColumn(['pms_item_id', 'pms_template_id']);
        });

        Schema::table('vehicle_pms_items', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id']);
        });
    }
};
