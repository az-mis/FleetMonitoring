<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bell's notifications, saved when they first appear so they stay after the record is dealt with
 * (a completed maintenance, an approved request), with read/unread kept per user on the server.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_notifications', function (Blueprint $table) {
            $table->id();
            // One notification per alert, e.g. "maint_due:12", "oil:5:42000", "travel:88"
            $table->string('key', 100)->unique();
            $table->string('type', 30);
            $table->string('location', 100)->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable();
            // What the record looked like when the alert appeared, shown if the record is later deleted
            $table->json('data')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('fleet_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_notification_id')->constrained('fleet_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['fleet_notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_notification_reads');
        Schema::dropIfExists('fleet_notifications');
    }
};
