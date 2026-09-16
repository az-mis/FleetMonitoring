<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->foreignId('assigned_driver_id')->nullable()->after('fuel_type')->constrained('users')->nullOnDelete();
            $table->string('location')->nullable()->after('assigned_driver_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropForeign(['assigned_driver_id']);
            $table->dropColumn(['assigned_driver_id', 'location']);
        });
    }
};
