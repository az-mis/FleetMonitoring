<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for what every list and the dashboard filter on: the area (location), the status and the dates.
 * Admins always see one area, so location comes first.
 */
return new class extends Migration
{
    /** @var array<string, list<list<string>>> */
    private array $indexes = [
        'vehicle_requests' => [['location', 'status', 'travel_date'], ['created_at']],
        'vehicle_maintenances' => [['location', 'status', 'scheduled_date'], ['status', 'scheduled_date']],
        'vehicles' => [['location']],
        'vehicle_issue_reports' => [['location', 'status']],
        'users' => [['role', 'location']],
        'fleet_cards' => [['location']],
        'fleet_card_logs' => [['fleet_card_id', 'created_at'], ['created_at']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $columns) {
                    $blueprint->index($columns);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $columns) {
                    $blueprint->dropIndex($columns);
                }
            });
        }
    }
};
