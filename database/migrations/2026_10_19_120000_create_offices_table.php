<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The offices / divisions that request vehicles and own them ("end user" in the GSS workbook).
     * Seeded with the list the request forms used to hardcode, so existing requests keep matching by name.
     */
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150)->unique();
            $table->string('location', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        $offices = [
            ['ORED', 'Office of the Regional Executive Director (ORED)'],
            ['RAFIS', 'Regional Agriculture and Fisheries Information Section (RAFIS)'],
            ['ORTDO', 'Office of the Regional Technical Director for Operations (ORTDO)'],
            ['FOD', 'Field Operations Division (FOD)'],
            ['FOD-RICE', 'FOD - Rice Banner Program'],
            ['FOD-CORN', 'FOD - Corn Banner Program'],
            ['FOD-LIVESTOCK', 'FOD - Livestock Program'],
            ['FOD-HVCDP', 'FOD - High Value Crops Development Program (HVCDP)'],
            ['FOD-IDS', 'FOD - Institutional Development Section (IDS)'],
            ['FOD-DRRM', 'FOD - Disaster Risk Reduction and Management (DRRM)'],
            ['FOD-RSBSA', 'FOD - Registry System for Basic Sectors in Agriculture (RSBSA)'],
            ['FOD-SAAD', 'FOD - Special Area for Agricultural Development (SAAD)'],
            ['FOD-4KS', 'FOD - Kabuhayan at Kaunlaran ng Kababayang Katututbo (4Ks)'],
            ['FOD-NUPAP', 'FOD - National Urban and Peri-Urban Agriculture Program (NUPAP)'],
            ['FOD-F2C2', 'FOD - Farm and Fisheries Clustering and Consolidation Program (F2C2)'],
            ['FOD-PAFES', 'FOD - Province-led Agriculture and Fisheries Extension Systems (PAFES)'],
            ['FOD-HFIDP', 'FOD - Halal Food Industry Development Program (HFIDP)'],
            ['FOD-PRDP', 'FOD - Philippine Rural Development Program (PRDP)'],
            ['ORTDRR', 'Office of the Regional Technical Director for Research & Regulations (ORTDRR)'],
            ['RD', 'Research Division (RD)'],
            ['ORMAES', 'Oriental Mindoro Agricultural Experiment Station (ORMAES)'],
            ['PRES', 'Palawan Research Experiment Station (PRES)'],
            ['PRES-LRC', 'PRES - Livestock Resource Center (LRC)'],
            ['PRES-DPDC', 'PRES - Dairy Production and Development Center (DPDC)'],
            ['PRES-PAC', 'PRES - Palawan Agricultural Center (PAC)'],
            ['PRES-ABOABO', 'PRES - Abo-Abo Rural Agricultural Center'],
            ['REGDIV', 'Regulatory Division'],
            ['REGDIV-OAP', 'Regulatory Division - Organic Agriculture Program'],
            ['AFD', 'Administrative and Finance Division (AFD)'],
            ['AFD-ACCTG', 'AFD - Accounting Section'],
            ['AFD-CASH', 'AFD - Cashiering Unit'],
            ['AFD-BUDGET', 'AFD - Budget Section'],
            ['AFD-HRS', 'AFD - Human Resource Section'],
            ['AFD-RECORDS', 'AFD - Records Section'],
            ['AFD-GSS', 'AFD - General Services Section'],
            ['AFD-PSU', 'AFD - Property & Supply Unit'],
            ['BAC', 'Bids & Awards Committee (BAC)'],
            ['PMED', 'Planning, Monitoring & Evaluation Division (PMED)'],
            ['MIS', 'Management Information Systems Section (MIS)'],
            ['RAFC', 'Regional Agricultural and Fishery Council (RAFC)'],
            ['AMAD', 'Agribusiness & Marketing Assistance Division (AMAD)'],
            ['ILD', 'Integrated Laboratory Division (ILD)'],
            ['ILD-RSL', 'ILD - Regional Soils Laboratory'],
            ['ILD-RCPC', 'ILD - Regional Crop Protection Center (RCPC)'],
            ['RAED', 'Regional Agricultural Engineering Division (RAED)'],
            ['APCO-OCCIDENTAL', 'APCO - Occidental Mindoro (San Jose)'],
            ['APCO-ORIENTAL', 'APCO - Oriental Mindoro (Calapan City)'],
            ['APCO-MARINDUQUE', 'APCO - Marinduque (Boac)'],
            ['APCO-ROMBLON', 'APCO - Romblon (Odiongan)'],
            ['APCO-PALAWAN', 'APCO - Palawan (Puerto Princesa City)'],
            ['COA', 'Commission on Audit (COA)'],
        ];
        $now = now();
        foreach ($offices as $i => [$code, $name]) {
            DB::table('offices')->insert(['code' => $code, 'name' => $name, 'sort' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        Schema::table('vehicle_requests', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->after('requester_office')->constrained('offices')->nullOnDelete();
        });

        // Link existing requests whose office text matches an office name exactly
        foreach (DB::table('offices')->pluck('id', 'name') as $name => $id) {
            DB::table('vehicle_requests')->where('requester_office', $name)->whereNull('office_id')->update(['office_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('vehicle_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
        });
        Schema::dropIfExists('offices');
    }
};
