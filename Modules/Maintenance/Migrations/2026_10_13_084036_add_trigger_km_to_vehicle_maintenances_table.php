<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_maintenances', function (Blueprint $table) {
            // Odometer-based trigger: maintenance is due at this km reading
            $table->unsignedInteger('trigger_km')->nullable()->after('odometer');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_maintenances', function (Blueprint $table) {
            $table->dropColumn('trigger_km');
        });
    }
};
