<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Enums\UserRole;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Maintenance\Services\MaintenanceService;
use Modules\Vehicle\Models\VehiclePmsItem;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Batch E: PMS sync, the bell of an admin without an area, signing out other devices */
class BatchEFixesTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function pms(int $vehicleId, string $name, ?int $km = null, ?string $date = null): VehiclePmsItem
    {
        return VehiclePmsItem::create(['vehicle_id' => $vehicleId, 'item_name' => $name, 'category' => 'Engine', 'interval_km' => 5000, 'last_done_km' => $km, 'last_done_date' => $date]);
    }

    private function complete(VehicleMaintenance $job, array $changes = []): void
    {
        $this->actingAs($this->superAdmin());
        app(MaintenanceService::class)->update($job, array_merge(['status' => 'completed'], $changes), $job->vehicle);
    }

    public function test_completing_a_job_updates_its_only_matching_pms_item(): void
    {
        $vehicle = $this->vehicle('Palawan', ['odometer' => 20000]);
        $oil = $this->pms($vehicle->id, 'Oil Change & Filter', 10000, '2026-01-10');
        $job = $this->maintenance($vehicle, ['type' => 'Oil Change', 'title' => 'Change oil', 'odometer' => 20000, 'status' => 'ongoing']);

        $this->complete($job, ['completed_date' => '2026-05-02']);

        $oil->refresh();
        $this->assertSame(20000, (int) $oil->last_done_km);
        $this->assertSame('2026-05-02', $oil->last_done_date->toDateString());
    }

    public function test_re_saving_an_old_completed_job_does_not_set_pms_back(): void
    {
        $vehicle = $this->vehicle('Palawan', ['odometer' => 30000]);
        $oil = $this->pms($vehicle->id, 'Oil Change & Filter', 25000, '2026-05-02');
        $january = $this->maintenance($vehicle, ['type' => 'Oil Change', 'title' => 'Change oil', 'odometer' => 15000, 'status' => 'completed', 'completed_date' => '2026-01-10']);

        // Fixing the shop name on January's job
        $this->complete($january->fresh(), ['vendor' => 'Corrected Shop', 'completed_date' => '2026-01-10']);

        $oil->refresh();
        $this->assertSame(25000, (int) $oil->last_done_km);
        $this->assertSame('2026-05-02', $oil->last_done_date->toDateString());
    }

    public function test_an_older_job_completed_late_never_moves_an_item_back(): void
    {
        $vehicle = $this->vehicle('Palawan', ['odometer' => 30000]);
        $oil = $this->pms($vehicle->id, 'Oil Change & Filter', 25000, '2026-05-02');
        $late = $this->maintenance($vehicle, ['type' => 'Oil Change', 'title' => 'Change oil', 'odometer' => 15000, 'status' => 'ongoing']);

        $this->complete($late, ['completed_date' => '2026-01-10']);

        $this->assertSame(25000, (int) $oil->fresh()->last_done_km);
    }

    public function test_a_title_matching_two_items_updates_neither(): void
    {
        $vehicle = $this->vehicle('Palawan', ['odometer' => 20000]);
        $fluid = $this->pms($vehicle->id, 'Brake Fluid', 10000);
        $pads = $this->pms($vehicle->id, 'Brake Pads', 10000);
        $job = $this->maintenance($vehicle, ['type' => 'Repair', 'title' => 'Brake', 'odometer' => 20000, 'status' => 'ongoing']);

        $this->complete($job, ['completed_date' => '2026-05-02']);

        $this->assertSame(10000, (int) $fluid->fresh()->last_done_km);
        $this->assertSame(10000, (int) $pads->fresh()->last_done_km);
    }

    public function test_an_admin_without_an_area_gets_an_empty_bell(): void
    {
        $this->tripRequest($this->vehicle('Palawan'), ['requester_name' => 'Secret Requester']);
        $arealess = User::factory()->create(['role' => UserRole::ADMIN, 'location' => null]);

        $this->actingAs($arealess)->get('/my-profile')->assertOk()->assertDontSee('Secret Requester')->assertSee('No notifications yet');
        $this->actingAs($this->admin('Palawan'))->get('/my-profile')->assertOk()->assertSee('Secret Requester');
    }

    public function test_a_password_change_signs_the_account_out_on_other_devices(): void
    {
        $driver = $this->driver('Palawan');

        // "Another device": a session that signed in with the old password
        $this->actingAs($driver)->get('/my-profile')->assertOk();

        $driver->forceFill(['password' => Hash::make('Fleet2026Changed')])->save();

        $this->get('/my-profile')->assertRedirect('/login');
    }

    public function test_change_password_is_rate_limited(): void
    {
        $this->actingAs($this->driver('Palawan'));

        for ($i = 1; $i <= 5; $i++) {
            $this->put('/user/password', ['current_password' => "wrong{$i}", 'password' => 'Fleet2026Palawan', 'password_confirmation' => 'Fleet2026Palawan']);
        }
        $this->put('/user/password', ['current_password' => 'wrong6', 'password' => 'Fleet2026Palawan', 'password_confirmation' => 'Fleet2026Palawan'])->assertStatus(429);
    }
}
