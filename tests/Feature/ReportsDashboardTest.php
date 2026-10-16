<?php

namespace Tests\Feature;

use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\FleetCard\Models\FleetCardLog;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * Dashboard and report queries use MySQL functions (DATE_FORMAT), so these run against MySQL:
 *   DB_CONNECTION=mysql DB_DATABASE=FleetDB_test php artisan test
 */
class ReportsDashboardTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Report and dashboard queries require MySQL (run with DB_CONNECTION=mysql DB_DATABASE=FleetDB_test).');
        }
    }

    public function test_dashboard_and_reports_render_for_each_admin_role(): void
    {
        $vehicle = $this->vehicle('Palawan');
        $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-001']);

        foreach ([$this->superAdmin(), $this->admin('Palawan')] as $user) {
            $this->actingAs($user)->get('/dashboard')->assertOk();
            $this->actingAs($user)->get('/reports')->assertOk();
            $this->actingAs($user)->get('/reports/print')->assertOk();
        }

        $this->actingAs($this->driver())->get('/dashboard')->assertOk();
        $this->actingAs($this->driver())->get('/reports')->assertForbidden();
    }

    public function test_invalid_custom_report_dates_do_not_crash(): void
    {
        $admin = $this->admin('Palawan');

        $this->actingAs($admin)->get('/reports?range=custom&from=abc&to=xyz')->assertOk();
        $this->actingAs($admin)->get('/reports?range=custom&from=2026-12-31&to=2026-01-01')->assertOk();
        $this->actingAs($admin)->get('/reports/export/csv')->assertNotFound();
    }

    public function test_excel_export_writes_user_text_as_plain_text_not_formulas(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $this->tripRequest($vehicle, [
            'status' => 'approved',
            'trip_ticket_number' => '26-10-777',
            'requester_name' => '=HYPERLINK("http://example.test","click")',
            'travel_date' => now()->toDateString(),
            'travel_date_end' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get('/reports/export/xlsx?range=month');
        $response->assertOk();

        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());

        $found = false;
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    if (str_contains((string) $cell->getValue(), 'HYPERLINK')) {
                        $found = true;
                        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
                    }
                }
            }
        }
        $this->assertTrue($found, 'Exported workbook should contain the requester name as text.');
    }

    public function test_multi_destination_trips_read_naturally_in_reports_and_exports(): void
    {
        $admin = $this->admin('Palawan');
        $this->tripRequest($this->vehicle('Palawan'), [
            'status' => 'approved',
            'trip_ticket_number' => '26-10-778',
            'destination' => 'Roxas Agriculture Office;;Brookes Point',
            'travel_date' => now()->toDateString(),
            'travel_date_end' => now()->toDateString(),
        ]);

        foreach (['/reports?range=month', '/reports/print?range=month'] as $page) {
            $this->actingAs($admin)->get($page)->assertOk()
                ->assertSee('Roxas Agriculture Office, Brookes Point')
                ->assertDontSee('Office;;Brookes');
        }

        $response = $this->actingAs($admin)->get('/reports/export/xlsx?range=month');
        $tripLog = IOFactory::load($response->baseResponse->getFile()->getPathname())->getSheetByName('Trip Log');
        $values = collect($tripLog->toArray())->flatten()->filter()->all();

        $this->assertContains('Roxas Agriculture Office, Brookes Point', $values);
    }

    public function test_fuel_spend_excludes_card_top_ups(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'));

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 50]); // ₱1,000 spend
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/recharge", ['amount' => 3000]);                                     // top-up, not spend
        $this->assertSame(2, FleetCardLog::count());

        $costs = app(ReportService::class)->costPerKmAnalysis('Palawan', now()->startOfMonth(), now()->endOfMonth());

        $this->assertEquals(1000, (float) $costs['fuel_spent']);
    }
}
