<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GSS workbook plan, phase 7:
 * - import_records: what the one-time import of a GSS workbook created or filled in, so running it again changes
 *   nothing, and which rows GSS should check ("needs review");
 * - vehicle_requests.driver_name: the driver as written in the workbook when no driver account matches (no accounts
 *   are created by the import).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_records', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 40);             // e.g. gss2026
            $table->string('sheet', 60);
            $table->unsignedInteger('row');
            $table->string('key', 191);               // natural key of the row (plate, ticket number, PR number…)
            $table->string('record_type', 120)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('outcome', 20);            // created | updated
            $table->boolean('needs_review')->default(false);
            $table->text('reason')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['source', 'key']);
            $table->index(['record_type', 'record_id']);
        });

        Schema::table('vehicle_requests', function (Blueprint $table): void {
            $table->string('driver_name', 120)->nullable()->after('confirmed_driver_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_requests', function (Blueprint $table): void {
            $table->dropColumn('driver_name');
        });
        Schema::dropIfExists('import_records');
    }
};
