<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who held each vehicle and when (decision D7). Adds a table only; existing tables are not changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('driver_name'); // kept even if the driver account is later removed
            $table->timestamp('assigned_at')->nullable(); // null: assigned before history tracking began
            $table->timestamp('unassigned_at')->nullable(); // null: current assignment
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'unassigned_at']);
        });

        // Start the history with each vehicle's current driver (start date unknown)
        $current = DB::table('vehicles')
            ->join('users', 'users.id', '=', 'vehicles.assigned_driver_id')
            ->get(['vehicles.id as vehicle_id', 'users.id as driver_id', 'users.name as driver_name']);

        foreach ($current as $row) {
            DB::table('vehicle_assignment_histories')->insert([
                'vehicle_id' => $row->vehicle_id,
                'driver_id' => $row->driver_id,
                'driver_name' => $row->driver_name,
                'assigned_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_assignment_histories');
    }
};
