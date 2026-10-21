<?php

namespace Tests\Feature;

use App\Support\FleetSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\Settings\Models\FleetSetting;
use Modules\Vehicle\Models\VehiclePmsItem;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Pages must not run more queries as the fleet grows, and must not carry all of history */
class PerformanceGuardsTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function vehicleWithPms(): void
    {
        $vehicle = $this->vehicle('Palawan');
        foreach (['Engine oil', 'Brake fluid', 'Tires'] as $item) {
            VehiclePmsItem::create(['vehicle_id' => $vehicle->id, 'item_name' => $item, 'category' => 'Engine', 'interval_km' => 5000, 'last_done_km' => 6000]);
        }
        $this->maintenance($vehicle, ['scheduled_date' => now()->subDays(3)->toDateString()]);
    }

    private function queriesFor(string $url): int
    {
        app()->forgetScopedInstances();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_the_vehicle_list_runs_the_same_queries_for_3_or_15_vehicles(): void
    {
        $this->actingAs($this->superAdmin());
        foreach (range(1, 3) as $i) {
            $this->vehicleWithPms();
        }
        $this->queriesFor('/vehicles?per_page=50'); // first load also does one-time work (notification check)
        $few = $this->queriesFor('/vehicles?per_page=50');

        foreach (range(1, 12) as $i) {
            $this->vehicleWithPms();
        }
        $many = $this->queriesFor('/vehicles?per_page=50');

        $this->assertSame($few, $many, "Vehicle list: {$few} queries for 3 vehicles, {$many} for 15");
    }

    public function test_settings_are_read_once_per_request_and_refreshed_when_saved(): void
    {
        FleetSetting::create(['setting_key' => 'app_logo', 'setting_value' => ['path' => 'logo/a.png']]);
        $this->actingAs($this->superAdmin());

        app()->forgetScopedInstances();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/maintenance')->assertOk();
        $settingQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'fleet_settings'))->count();
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(3, $settingQueries); // logo + admin modules, once each

        $settings = app(FleetSettings::class);
        $this->assertSame(['path' => 'logo/a.png'], $settings->get('app_logo'));
        FleetSetting::where('setting_key', 'app_logo')->first()->update(['setting_value' => ['path' => 'logo/b.png']]);
        $this->assertSame(['path' => 'logo/b.png'], $settings->get('app_logo'));
    }

    public function test_fleet_cards_page_carries_only_top_ups_and_this_months_spend(): void
    {
        $card = $this->fleetCard($this->vehicle('Palawan'), ['monthly_allowance' => 20000]);
        $log = fn (float $amount, string $source, $when) => FleetCardLog::forceCreate([
            'fleet_card_id' => $card->id, 'vehicle_id' => $card->vehicle_id, 'distance_source' => $source, 'distance_km' => 0, 'rate_peso_per_km' => 0,
            'consumed_amount' => $amount, 'balance_before' => 5000, 'balance_after' => 5000 - $amount,
            'created_at' => $when, 'updated_at' => $when,
        ]);
        $log(1200, 'manual', now());
        $log(800, 'manual', now()->subMonths(2));
        $log(-3000, 'recharge', now());

        $response = $this->actingAs($this->admin('Palawan'))->get('/fleet-cards')->assertOk();

        $response->assertSee(now()->format('F').': ₱1,200.00 of ₱20,000.00 allowance');
        $cardOnPage = $response->viewData('cards')->first();
        $this->assertCount(1, $cardOnPage->logs, 'Only the top-up is embedded in the page');
        $this->assertEquals(-3000, $cardOnPage->logs->first()->consumed_amount);
    }

    public function test_public_availability_only_returns_trips_that_have_not_ended(): void
    {
        $vehicle = $this->vehicle('Palawan');
        $this->tripRequest($vehicle, ['status' => 'approved', 'travel_date' => now()->subDays(10)->toDateString(), 'travel_date_end' => now()->subDays(9)->toDateString()]);
        $this->tripRequest($vehicle, ['status' => 'approved', 'travel_date' => now()->subDay()->toDateString(), 'travel_date_end' => now()->addDay()->toDateString()]);
        $this->tripRequest($vehicle, ['status' => 'pending', 'travel_date' => now()->addDays(5)->toDateString(), 'travel_date_end' => now()->addDays(5)->toDateString()]);

        $this->getJson("/request-vehicle/{$vehicle->id}/availability")->assertOk()->assertJsonCount(2, 'bookings');
    }
}
