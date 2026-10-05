<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('approving_officer_signatories', function (Blueprint $table): void {
            // 1 = printed first (top) on the Trip Ticket, 2 = second. NULL = not on the ticket.
            $table->unsignedTinyInteger('ticket_order')->nullable()->after('show_on_ticket');
        });

        // Keep today's printed order: ticked signatories are numbered by their current list order.
        $officerIds = DB::table('approving_officer_signatories')->distinct()->pluck('approving_officer_id');
        foreach ($officerIds as $officerId) {
            $rank = 1;
            $rows = DB::table('approving_officer_signatories')
                ->where('approving_officer_id', $officerId)
                ->where('show_on_ticket', true)
                ->orderBy('sort_order')->orderBy('id')
                ->get();
            foreach ($rows as $row) {
                DB::table('approving_officer_signatories')->where('id', $row->id)->update(['ticket_order' => $rank++]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('approving_officer_signatories', function (Blueprint $table): void {
            $table->dropColumn('ticket_order');
        });
    }
};
