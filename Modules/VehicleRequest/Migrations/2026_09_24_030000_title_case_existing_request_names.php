<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\VehicleRequest\Support\PersonName;

/**
 * One-time catch-up so requests submitted before Title Case was enforced
 * also print cleanly on their Trip Tickets.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('vehicle_requests')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $updates = [];

                $name = PersonName::titleCase($row->requester_name);
                if ($name !== '' && $name !== $row->requester_name) {
                    $updates['requester_name'] = $name;
                }

                if ($row->passengers) {
                    $decoded = json_decode($row->passengers, true);
                    if (is_array($decoded)) {
                        $fixed = PersonName::titleCaseAll($decoded);
                        if ($fixed !== $decoded) {
                            $updates['passengers'] = json_encode($fixed, JSON_UNESCAPED_UNICODE);
                        }
                    }
                }

                if ($updates) {
                    DB::table('vehicle_requests')->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        // Original capitalisation is not kept; nothing to undo.
    }
};
