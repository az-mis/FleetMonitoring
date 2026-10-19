<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GSS workbook plan, phase 4: the repair details GSS keeps in its "Index for Repair of Vehicles",
 * "Vehicle Pre & Post Repair CM Value" and "Vehicle PMS" sheets, and the purchase requests
 * ("PR of Goods and Services") that pay for vehicle work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_maintenances', function (Blueprint $table): void {
            $table->foreignId('requisitioner_office_id')->nullable()->after('location')->constrained('offices')->nullOnDelete();
            $table->string('pm_request_no', 30)->nullable()->after('requisitioner_office_id');
            $table->date('pre_inspection_date')->nullable()->after('pm_request_no');
            $table->text('defect_nature')->nullable()->after('pre_inspection_date');
            $table->text('parts_replaced')->nullable()->after('defect_nature');
            $table->date('post_inspection_date')->nullable()->after('parts_replaced');
            $table->text('post_inspection_findings')->nullable()->after('post_inspection_date');
            $table->decimal('current_market_value', 14, 2)->nullable()->after('post_inspection_findings');
            $table->date('cmv_issued_at')->nullable()->after('current_market_value');
            $table->string('cmv_issued_by', 100)->nullable()->after('cmv_issued_at');
        });

        Schema::create('procurements', function (Blueprint $table): void {
            $table->id();
            // Kept if the maintenance record is deleted, since a PR is a financial record of its own
            $table->foreignId('vehicle_maintenance_id')->nullable()->constrained('vehicle_maintenances')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('location')->nullable()->index();
            $table->string('description', 255);
            $table->string('category', 60)->default('Vehicle');
            $table->string('pr_number', 40)->nullable()->index();
            $table->date('pr_date')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('status', 30)->index();
            $table->string('jo_po_number', 60)->nullable();
            $table->date('jo_po_date')->nullable();
            $table->date('coa_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurements');

        Schema::table('vehicle_maintenances', function (Blueprint $table): void {
            $table->dropForeign(['requisitioner_office_id']);
            $table->dropColumn([
                'requisitioner_office_id', 'pm_request_no', 'pre_inspection_date', 'defect_nature', 'parts_replaced',
                'post_inspection_date', 'post_inspection_findings', 'current_market_value', 'cmv_issued_at', 'cmv_issued_by',
            ]);
        });
    }
};
