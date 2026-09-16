<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_code', 7)->unique();
            $table->string('requester_name');
            $table->string('location');
            $table->string('requester_office')->nullable();
            $table->string('requester_contact')->nullable();
            $table->boolean('requester_is_passenger')->default(false);
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->string('destination');
            $table->date('travel_date');
            $table->date('travel_date_end')->nullable();
            $table->text('purpose');
            $table->json('passengers')->nullable();
            $table->date('previous_trip_ticket_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('decline_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'travel_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_requests');
    }
};
