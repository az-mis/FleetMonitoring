<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GSS workbook plan, phase 5: each fleet card's provider, division and monthly allowance ("Petron Fleet Card"
 * sheet), and the monthly spend per division ("Index for Fleetcard" sheet) as read from the providers' statements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_cards', function (Blueprint $table): void {
            $table->string('provider', 20)->nullable()->after('card_name');
            $table->foreignId('office_id')->nullable()->after('location')->constrained('offices')->nullOnDelete();
            $table->decimal('monthly_allowance', 12, 2)->nullable()->after('initial_budget');
            $table->string('allowed_products', 60)->nullable()->after('monthly_allowance');
        });

        Schema::create('fleet_card_monthly_spend', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 20);
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            // The division as written on the statement or workbook, kept when it matches no office
            $table->string('division_label', 150);
            $table->string('location')->nullable()->index();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 14, 2);
            $table->string('source', 20)->default('statement'); // statement (entered by GSS) | import (GSS 2026 workbook)
            // e.g. BAR-PRES and PRDP vehicles, listed under the total but not counted in it
            $table->boolean('excluded_from_total')->default(false);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['provider', 'division_label', 'year', 'month'], 'fleet_card_monthly_spend_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_card_monthly_spend');

        Schema::table('fleet_cards', function (Blueprint $table): void {
            $table->dropForeign(['office_id']);
            $table->dropColumn(['provider', 'office_id', 'monthly_allowance', 'allowed_products']);
        });
    }
};
