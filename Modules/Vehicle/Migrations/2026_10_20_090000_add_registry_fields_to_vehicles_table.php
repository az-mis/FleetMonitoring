<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The property record and paperwork GSS keeps per vehicle in its workbook
     * ("Vehicle Inventory" and "List of Vehicle" sheets). All optional.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Property record
            $table->string('par_number', 40)->nullable()->after('plate_number');
            $table->string('property_number', 40)->nullable()->after('par_number');
            $table->string('old_plate_number', 30)->nullable()->after('property_number');
            $table->string('conduction_sticker', 30)->nullable()->after('engine_number');
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 14, 2)->nullable();
            $table->string('custodian_name', 150)->nullable();
            $table->foreignId('end_user_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->string('assigned_person', 150)->nullable();
            $table->string('book_status', 40)->nullable();
            $table->string('condition', 40)->nullable();
            $table->text('registry_remarks')->nullable();
            $table->text('history')->nullable();

            // Registration and insurance
            $table->string('or_cr_status', 40)->nullable();
            $table->string('registration_place', 100)->nullable();
            $table->date('registration_expiry')->nullable();
            $table->string('renewal_schedule', 100)->nullable();
            $table->string('insurance_type', 60)->nullable();
            $table->date('insurance_from')->nullable();
            $table->date('insurance_to')->nullable();

            // Equipment and allowance
            $table->string('tire_type', 60)->nullable();
            $table->boolean('has_ewd')->nullable();
            $table->string('tools', 255)->nullable();
            $table->decimal('fleet_card_allowance', 12, 2)->nullable();
        });

        Schema::create('vehicle_accessories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('description', 200);
            $table->date('date_acquired')->nullable();
            $table->decimal('value', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_accessories');
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('end_user_office_id');
            $table->dropColumn([
                'par_number', 'property_number', 'old_plate_number', 'conduction_sticker', 'acquisition_date', 'acquisition_cost',
                'custodian_name', 'assigned_person', 'book_status', 'condition', 'registry_remarks', 'history',
                'or_cr_status', 'registration_place', 'registration_expiry', 'renewal_schedule', 'insurance_type', 'insurance_from', 'insurance_to',
                'tire_type', 'has_ewd', 'tools', 'fleet_card_allowance',
            ]);
        });
    }
};
