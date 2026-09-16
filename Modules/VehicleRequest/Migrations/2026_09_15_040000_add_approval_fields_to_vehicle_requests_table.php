<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicle_requests', function (Blueprint $table): void {
            $table->string('trip_ticket_number')->nullable()->unique()->after('status');
            $table->foreignId('confirmed_driver_id')->nullable()->constrained('users')->nullOnDelete()->after('trip_ticket_number');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('confirmed_driver_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_requests', function (Blueprint $table): void {
            $table->dropForeign(['confirmed_driver_id']);
            $table->dropForeign(['approved_by']);
            $table->dropUnique(['trip_ticket_number']);
            $table->dropColumn(['trip_ticket_number', 'confirmed_driver_id', 'approved_by', 'approved_at']);
        });
    }
};
