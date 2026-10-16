<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fresh installs create vehicle_issue_reports before vehicle_maintenances, so the
 * related_maintenance_id foreign key is added here once both tables exist.
 * Databases that already have the constraint are left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $hasForeignKey = collect(Schema::getForeignKeys('vehicle_issue_reports'))
            ->contains(fn (array $foreignKey) => $foreignKey['columns'] === ['related_maintenance_id']);

        if ($hasForeignKey) {
            return;
        }

        Schema::table('vehicle_issue_reports', function (Blueprint $table) {
            $table->foreign('related_maintenance_id')
                ->references('id')
                ->on('vehicle_maintenances')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally left as a no-op: the constraint predates this migration on existing databases.
    }
};
