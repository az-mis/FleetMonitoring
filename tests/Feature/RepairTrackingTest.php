<?php

namespace Tests\Feature;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Maintenance\Models\Procurement;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * GSS workbook plan, phase 4: repair details (repair index, pre/post repair CM value, PMS log) and the
 * purchase requests that pay for vehicle work.
 */
class RepairTrackingTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function prPayload(array $overrides = []): array
    {
        return array_merge([
            'description' => 'Repair and replacement of parts for Toyota Hi-Ace Grandia',
            'category' => 'Vehicle parts',
            'pr_number' => '2026-06-1455',
            'pr_date' => '2026-06-11',
            'amount' => '31450',
            'status' => 'PR on process',
        ], $overrides);
    }

    public function test_admin_records_the_repair_details(): void
    {
        $admin = $this->admin('Palawan');
        $record = $this->maintenance($this->vehicle('Palawan', ['plate_number' => 'ZMX 441']));
        $amad = Office::where('code', 'AMAD')->value('id');

        $this->actingAs($admin)->get("/maintenance/{$record->id}/repair")->assertOk()->assertSee('ZMX 441')->assertSee('Purchase requests');

        $this->actingAs($admin)->put("/maintenance/{$record->id}/repair", [
            'requisitioner_office_id' => $amad,
            'pre_inspection_date' => '2026-02-23',
            'defect_nature' => 'Wheel bearing noise, worn brake pads',
            'parts_replaced' => 'Wheel bearing inner/outer, brake pad',
            'post_inspection_date' => '2026-02-25',
            'post_inspection_findings' => 'Replaced wheel bearing and brake pad',
            'current_market_value' => '500000',
            'cmv_issued_at' => '2026-02-25',
            'cmv_issued_by' => 'GSS-QC',
        ])->assertRedirect("/maintenance/{$record->id}/repair")->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame('AMAD', $record->requisitionerOffice->code);
        $this->assertSame('Wheel bearing inner/outer, brake pad', $record->parts_replaced);
        $this->assertEquals(500000, (float) $record->current_market_value);

        $this->actingAs($admin)->put("/maintenance/{$record->id}/repair", ['pre_inspection_date' => '2026-02-25', 'post_inspection_date' => '2026-02-20'])
            ->assertSessionHasErrors('post_inspection_date');
    }

    public function test_purchase_requests_are_added_updated_and_removed(): void
    {
        $admin = $this->admin('Palawan');
        $record = $this->maintenance($this->vehicle('Palawan'));

        $this->actingAs($admin)->post("/maintenance/{$record->id}/procurements", $this->prPayload())->assertSessionHasNoErrors();
        $pr = Procurement::firstOrFail();
        $this->assertSame($record->vehicle_id, $pr->vehicle_id);
        $this->assertSame('Palawan', $pr->location);
        $this->assertSame($admin->id, $pr->created_by);

        $this->actingAs($admin)->put("/maintenance/{$record->id}/procurements/{$pr->id}", $this->prPayload([
            'status' => 'For payment', 'jo_po_number' => 'JO-2026-490', 'jo_po_date' => '2026-06-20', 'coa_date' => '2026-07-03',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('For payment', $pr->fresh()->status);
        $this->actingAs($admin)->get("/maintenance/{$record->id}/repair")->assertSee('JO-2026-490')->assertSee('For payment');

        $this->actingAs($admin)->delete("/maintenance/{$record->id}/procurements/{$pr->id}")->assertSessionHasNoErrors();
        $this->assertSame(0, Procurement::count());
    }

    public function test_purchase_request_values_are_validated(): void
    {
        $admin = $this->admin('Palawan');
        $record = $this->maintenance($this->vehicle('Palawan'));

        $this->actingAs($admin)->post("/maintenance/{$record->id}/procurements", $this->prPayload([
            'description' => '', 'status' => 'Paid', 'category' => 'Aircon', 'jo_po_date' => '2026-06-01', 'coa_date' => '2026-05-01',
        ]))->assertSessionHasErrors(['description', 'status', 'category', 'jo_po_date', 'coa_date']);
        $this->assertSame(0, Procurement::count());
    }

    public function test_purchase_requests_stay_within_the_admins_area_and_record(): void
    {
        $romblon = $this->maintenance($this->vehicle('Romblon'));
        $palawan = $this->maintenance($this->vehicle('Palawan'));
        $pr = $romblon->procurements()->create($this->prPayload(['location' => 'Romblon']));

        $this->actingAs($this->admin('Palawan'))->get("/maintenance/{$romblon->id}/repair")->assertForbidden();
        $this->actingAs($this->admin('Palawan'))->post("/maintenance/{$romblon->id}/procurements", $this->prPayload())->assertForbidden();
        $this->actingAs($this->driver('Romblon'))->get("/maintenance/{$romblon->id}/repair")->assertForbidden();
        // A request can only be changed through the record it belongs to
        $this->actingAs($this->admin('Palawan'))->delete("/maintenance/{$palawan->id}/procurements/{$pr->id}")->assertNotFound();
        $this->assertSame(1, Procurement::count());
    }

    public function test_awaiting_payment_filter_lists_work_whose_pr_waits_for_payment(): void
    {
        $admin = $this->admin('Palawan');
        $paid = $this->maintenance($this->vehicle('Palawan', ['plate_number' => 'PAID 001']), ['status' => 'completed', 'completed_date' => now()->toDateString()]);
        $waiting = $this->maintenance($this->vehicle('Palawan', ['plate_number' => 'WAIT 002']), ['status' => 'completed', 'completed_date' => now()->toDateString()]);
        $reimburse = $this->maintenance($this->vehicle('Palawan', ['plate_number' => 'REIM 003']));
        $paid->procurements()->create($this->prPayload(['status' => 'Completed', 'location' => 'Palawan']));
        $waiting->procurements()->create($this->prPayload(['status' => 'For payment', 'location' => 'Palawan']));
        $reimburse->procurements()->create($this->prPayload(['status' => 'Reimbursement', 'location' => 'Palawan']));

        $this->actingAs($admin)->get('/maintenance?status=awaiting_payment')->assertOk()
            ->assertSee('Awaiting payment')
            // Rows by id: every plate also appears in the page's vehicle picker
            ->assertSee("maintenance-row-{$waiting->id}", false)->assertSee("maintenance-row-{$reimburse->id}", false)
            ->assertDontSee("maintenance-row-{$paid->id}", false)
            ->assertSee('PR: For payment');
    }

    public function test_a_notification_opens_the_list_where_its_record_is_and_marks_it(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        // Twelve newer active records push the completed one past the first page of the "active" list
        foreach (range(1, 12) as $i) {
            $this->maintenance($vehicle, ['scheduled_date' => now()->addDays(20 + $i)->toDateString(), 'title' => "Upcoming job {$i}"]);
        }
        $done = $this->maintenance($vehicle, ['status' => 'completed', 'completed_date' => now()->toDateString(), 'scheduled_date' => now()->subDay()->toDateString(), 'title' => 'Finished brake job']);

        $this->actingAs($admin)->get("/maintenance?highlight_id={$done->id}")
            ->assertRedirect("/maintenance?highlight_id={$done->id}&status=all&page=2");
        $this->actingAs($admin)->get("/maintenance?highlight_id={$done->id}&status=all&page=2")->assertOk()
            ->assertSee('From your notification:')->assertSee('Finished brake job')
            ->assertSee('data-highlighted="1"', false);

        // A record of another area is not shown, and the page says so
        $romblon = $this->maintenance($this->vehicle('Romblon'));
        $this->actingAs($admin)->get("/maintenance?highlight_id={$romblon->id}")->assertOk()
            ->assertSee('no longer exists, or belongs to another area');
    }

    public function test_purchase_request_is_kept_when_its_maintenance_record_is_deleted(): void
    {
        $record = $this->maintenance($this->vehicle('Palawan'));
        $pr = $record->procurements()->create($this->prPayload(['location' => 'Palawan']));

        $this->actingAs($this->superAdmin())->delete("/maintenance/{$record->id}")->assertSessionHas('success');

        $this->assertNull($pr->fresh()->vehicle_maintenance_id);
        $this->assertSame('2026-06-1455', $pr->fresh()->pr_number);
    }
}
