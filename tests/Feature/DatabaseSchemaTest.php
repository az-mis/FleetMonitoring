<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_migrations_run_and_core_tables_exist(): void
    {
        foreach ([
            'users', 'vehicles', 'vehicle_types', 'vehicle_requests', 'vehicle_maintenances',
            'vehicle_issue_reports', 'fleet_cards', 'fleet_card_logs', 'destinations',
            'approving_officers', 'approving_officer_signatories', 'fleet_settings',
            'vehicle_inspections', 'vehicle_pms_items', 'vehicle_pms_templates', 'trip_ticket_completions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }
}
