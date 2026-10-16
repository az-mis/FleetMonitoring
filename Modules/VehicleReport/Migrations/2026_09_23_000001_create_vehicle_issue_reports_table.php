<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_issue_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number');
            $table->string('vehicle_label')->nullable();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->string('driver_name');
            $table->string('issue_type'); // Tire, Engine, Brake, Battery, Electrical, Body / Exterior, Other
            $table->string('priority')->default('normal'); // normal, high, urgent
            $table->text('description');
            $table->string('status')->default('submitted'); // submitted, reviewed, maintenance_scheduled, ongoing, resolved, dismissed
            $table->text('admin_notes')->nullable();
            // This migration sorts before create_vehicle_maintenances_table (same timestamp), so on a
            // fresh database the referenced table does not exist yet. The foreign key is added by
            // 2026_10_16_000000_add_related_maintenance_foreign_key_to_vehicle_issue_reports_table.
            $relatedMaintenance = $table->foreignId('related_maintenance_id')->nullable();
            if (Schema::hasTable('vehicle_maintenances')) {
                $relatedMaintenance->constrained('vehicle_maintenances')->nullOnDelete();
            }
            $table->string('related_maintenance_title')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewed_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_issue_reports');
    }
};
