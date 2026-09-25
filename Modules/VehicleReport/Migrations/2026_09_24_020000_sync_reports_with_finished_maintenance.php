<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\VehicleReport\Services\VehicleReportService;

/**
 * One-time catch-up: maintenance records that were moved to ongoing / completed /
 * cancelled before reports followed them left the driver's report stuck on
 * "Maintenance Scheduled". Bring those reports up to date.
 */
return new class extends Migration {
    public function up(): void
    {
        $service = app(VehicleReportService::class);

        VehicleMaintenance::query()
            ->whereIn('status', ['ongoing', 'completed', 'cancelled'])
            ->whereIn('id', fn ($q) => $q->select('related_maintenance_id')->from('vehicle_issue_reports')->whereNotNull('related_maintenance_id'))
            ->get()
            ->each(fn (VehicleMaintenance $maintenance) => $service->syncFromMaintenance($maintenance, true));
    }

    public function down(): void
    {
        // Data-only catch-up; nothing to undo.
    }
};
