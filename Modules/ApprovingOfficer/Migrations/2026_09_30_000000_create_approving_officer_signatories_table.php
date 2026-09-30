<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approving_officer_signatories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('approving_officer_id')->constrained('approving_officers')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('title', 150)->nullable();
            $table->boolean('show_on_ticket')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Carry over the existing Signatory 1 / 2 data; both stay selected for the ticket.
        foreach (DB::table('approving_officers')->get() as $officer) {
            $order = 0;
            foreach ([1, 2] as $n) {
                $name = $officer->{"signee{$n}_name"};
                if (filled($name)) {
                    DB::table('approving_officer_signatories')->insert([
                        'approving_officer_id' => $officer->id,
                        'name' => $name,
                        'title' => $officer->{"signee{$n}_title"},
                        'show_on_ticket' => true,
                        'sort_order' => $order++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approving_officer_signatories');
    }
};
