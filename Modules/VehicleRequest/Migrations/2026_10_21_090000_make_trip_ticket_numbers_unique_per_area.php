<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GSS workbook plan, phase 3: trip ticket numbers are unique per area. Several areas share the
 * 0001-MM-YYYY format, so the same number can exist in two areas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_requests', function (Blueprint $table): void {
            $table->unique(['location', 'trip_ticket_number']);
            $table->index('trip_ticket_number');
            $table->dropUnique(['trip_ticket_number']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_requests', function (Blueprint $table): void {
            $table->unique('trip_ticket_number');
            $table->dropIndex(['trip_ticket_number']);
            $table->dropUnique(['location', 'trip_ticket_number']);
        });
    }
};
