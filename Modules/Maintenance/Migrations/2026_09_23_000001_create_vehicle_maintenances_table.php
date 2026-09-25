<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number');
            $table->string('vehicle_label')->nullable();
            $table->string('type'); // Preventive, Repair, Inspection, Oil Change, Tire, Battery, Other
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, ongoing, completed, cancelled
            $table->string('priority')->default('normal'); // normal, high, urgent
            $table->date('scheduled_date');
            $table->date('scheduled_date_end')->nullable();
            $table->date('completed_date')->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('vendor')->nullable();
            $table->text('notes')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name')->nullable();
            $table->unsignedBigInteger('source_report_id')->nullable(); // links to vehicle_issue_reports if added later
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};
