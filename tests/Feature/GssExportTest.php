<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Services\Gss\GssExportService;
use App\Services\Gss\GssScope;
use App\Services\Gss\GssWorkbookWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\FleetCard\Models\FleetCardMonthlySpend;
use Modules\VehicleRequest\Models\TripTicketCompletion;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * GSS workbook plan, phase 6: the export's sheets carry the workbook's own headers, in the same order.
 * The expected headers below are copied from "GSS 2026.xlsx".
 */
class GssExportTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private const WORKBOOK_HEADERS = [
        'PETRON FLEET CARD' => [['AREA', 'PLATE NUMBER / VEHICLE TYPE', 'ALLOWED PRODUCTS']],
        'VEHICLE INVENTORY' => [['NO.', 'ARTICLE', 'DESCRIPTION', 'UNIT OF MEASURE', 'QUANTITY', 'PAR NUMBER', 'PROPERTY NUMBER', 'PLATE NUMBER', 'LOCATION', 'DATE ACQUIRED', 'TOTAL VALUE', 'CUSTODIAN', 'For Reconciliation', 'STATUS', 'END USER', 'REMARKS', 'Condition', 'HISTORY']],
        'List of Vehicle' => [['NO.', 'NAME OF DRIVER', 'MOBILE NUMBER', 'PLACE OF ASSIGNMENT', 'FLEET CARD ALLOWANCE', 'PLATE NO.', 'DIVISION/END USER', 'NAME OF PERSON ASSIGNED VEHICLE', 'VEHICLE TYPE', 'YEAR MODEL', 'STATUS', 'OR CR COPY ORIG. (Y/N)', 'PLACE OF REGISTRATION AND RENEWAL', 'TIRE TYPE', 'INSURANCE STATUS', 'REGISTRATION STATUS 2026', 'SCHEDULE OF RENEWAL REGISTRATION', 'Early Warning Devce', 'TOOLS']],
        'FUEL WEEKLY CONSUMPTION' => [
            ['NO.', 'VEHICLE', 'NAME OF DRIVER', 'PLATE NUMBER', 'FUEL TYPE', 'DATE TRIP', 'TRIP TICKET NUMBER', 'BALANCE IN TANK (BEFORE TRAVEL)', 'DESTINATION', 'FUEL PURCHASED (liters)', 'BALANCE IN TANK (AFTER TRAVEL)', 'DISTANCE TRAVELED ( in kms)', 'FUEL CONSUMED PER WEEK (liters)', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', 'WEEKS', 'LITERS'],
        ],
        'INDEX FOR REPAIR OF VEHICLES' => [['NO.', 'AREA', 'REQUISITIONER/END-USER', 'DATE PRE-REPAIR', 'VEHICLE TYPE/ MODEL/ YEAR', 'PLATE NUMBER', 'PROPERTY NUMBER', 'DRIVER ASSIGNED', 'DATE OF POST-REPAIR', 'NATURE OF DEFECT', 'PARTS NEED TO BE REPLACE', 'REPAIR COST', 'STATUS', 'REMARKS']],
        'INDEX FOR FLEETCARD' => [['NO.', 'DIVISION/PROGRAM', 'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPT', 'OCT', 'NOVEMBER', 'DECEMBER']],
        'VEHICLE PRE & POST REPAIR CM VA' => [['DATE OF REQUEST', 'NAME OF REQUESTER', 'PLATE NO.', 'TYPE OF VEHICLE', 'MODEL', 'END-USER', 'PRE INSPECTION DATE', 'COMPLAINTS, OR DEFFECTS TO BE DONE FOR REPAIR OR REPLACEMENT', 'POST INSPECTION', 'POST INSPECTION DATE', 'ACQUISITION DATE/YEAR', 'ACQUISITION COST', 'C . M. VALUE', 'MARKET VALUE DATE OF ISSUANCE', 'ISSUED BY']],
        'VEHICLE PMS' => [
            ['PM REQUEST NO.', 'DATE REQUESTED', 'NAME OF DRIVER', 'REQUISITIONER/ END-USER', 'VEHICLE DESCRIPTION (TYPE/MODEL/YEAR)', 'PLATE NO.', 'Current Odometer', '', '', 'DATE COMPLETED', 'Total Cost (₱)', 'Status', 'Remarks'],
            ['', '', '', '', '', '', 'KM', 'LTR', 'DATE READ', '', '', '', ''],
        ],
        'TRIP TICKET MONITORING' => [
            ['TRIP TICKET NUMBER', 'DATE RECORDED', 'REQUESTER NAME OR PROGRAM', 'PASSENGERS NAME OR LISTING', 'DATE OF TRAVEL', 'DATE OF RETURN', 'PURPOSE OF TRAVEL', 'DESTINATION', 'VEHICLE PLATE NUMBER', 'DRIVER', 'SPEEDOMETER READING', ''],
            ['', '', '', '', '', '', '', '', '', '', 'BEFORE', 'AFTER'],
        ],
        'RP VEHICLE REQUEST MONITORING' => [['RP VEHICLE REQUEST NO.', 'DATE REQUEST', 'DRIVER', 'TYPE OF VEHICLE', 'DATE OF TRAVEL', 'PLATE NO.', 'PURPOSE OF TRAVEL', 'DESTINATION', 'REQUESTED BY']],
        'PR OF GOODS AND SERVICES' => [['NO.', 'DESCRIPTION', '', 'PR NUMBER', 'DATE OF PR', 'AMOUNT', 'STATUS', 'REMARKS', 'REMARKS', 'JO/PO NUMBER/ DATE', 'COA DATE']],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function office(string $code): int
    {
        return (int) Office::where('code', $code)->value('id');
    }

    private function book(?string $location = null): Spreadsheet
    {
        $path = app(GssExportService::class)->export(new GssScope(2026, $location, 'Test Admin'));
        $book = IOFactory::load($path);
        @unlink($path);

        return $book;
    }

    /** @return list<list<string>> the sheet's header rows */
    private function headerRows(Worksheet $ws, int $rows, int $columns): array
    {
        $out = [];
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 1; $c <= $columns; $c++) {
                $out[$r][] = (string) $ws->getCell([$c, GssWorkbookWriter::HEADER_ROW + $r])->getValue();
            }
        }

        return $out;
    }

    /** All non-empty cell values of the sheet below its header rows, row by row */
    private function body(Worksheet $ws): array
    {
        $headerRows = count(self::WORKBOOK_HEADERS[$ws->getTitle()]);

        return array_values(array_filter(array_map(
            fn (array $row) => array_values(array_filter($row, fn ($v) => $v !== null && $v !== '')),
            // toArray() is zero-based, so index HEADER_ROW - 1 is the first header row
            array_slice($ws->toArray(null, true, false, false), GssWorkbookWriter::HEADER_ROW - 1 + $headerRows)
        )));
    }

    public function test_every_sheet_has_the_workbook_headers_in_order(): void
    {
        $book = $this->book();

        $this->assertSame(array_keys(self::WORKBOOK_HEADERS), $book->getSheetNames());
        foreach (self::WORKBOOK_HEADERS as $title => $headers) {
            $this->assertSame($headers, $this->headerRows($book->getSheetByName($title), count($headers), count($headers[0])), "Headers of {$title}");
        }
    }

    public function test_trip_tickets_are_grouped_by_area_with_part_b_readings(): void
    {
        $qc = $this->vehicle('Quezon City Satellite Office', ['plate_number' => 'SDA 1850']);
        $pal = $this->vehicle('Palawan', ['plate_number' => 'SNE 6224']);
        $qcTrip = $this->tripRequest($qc, ['status' => 'approved', 'trip_ticket_number' => '001-26', 'travel_date' => '2026-01-05', 'travel_date_end' => '2026-01-09', 'approved_at' => '2026-01-02', 'confirmed_driver_id' => $this->driver('Quezon City Satellite Office', ['name' => 'Ruben Nario'])->id]);
        $palTrip = $this->tripRequest($pal, ['status' => 'approved', 'trip_ticket_number' => '26-03-045', 'travel_date' => '2026-03-16', 'travel_date_end' => '2026-03-16', 'office_id' => $this->office('PRES')]);
        TripTicketCompletion::create(['vehicle_request_id' => $palTrip->id, 'trip_ticket_number' => '26-03-045', 'vehicle_id' => $pal->id, 'odo_beginning' => 20000, 'odo_ending' => 20180, 'distance_travelled' => 180, 'fuel_used_liters' => 18, 'fuel_balance_in_tank' => 30, 'fuel_ending_balance' => 12]);
        $this->tripRequest($pal, ['status' => 'approved', 'trip_ticket_number' => '25-12-900', 'travel_date' => '2025-12-20', 'travel_date_end' => '2025-12-20']);

        $rows = $this->body($this->book()->getSheetByName('TRIP TICKET MONITORING'));
        $this->assertSame('QUEZON CITY', $rows[0][0]);
        $this->assertSame('001-26', $rows[1][0]);
        $this->assertContains('Ruben Nario', $rows[1]);
        $this->assertSame('PALAWAN', $rows[2][0]);
        $this->assertSame(['26-03-045', 'PRES'], array_slice($rows[3], 0, 2));
        $this->assertSame([20000, 20180], array_map('intval', array_slice($rows[3], -2)));
        $this->assertCount(4, $rows, 'trips of another year are left out');

        $fuel = $this->body($this->book()->getSheetByName('FUEL WEEKLY CONSUMPTION'));
        $this->assertCount(1, $fuel);
        $this->assertContains('26-03-045', $fuel[0]);
        $this->assertSame(18, (int) end($fuel[0]));
    }

    public function test_fleetcard_index_totals_leave_out_excluded_vehicles(): void
    {
        $spend = fn (string $label, int $month, float $amount, bool $excluded = false, ?string $code = null) => FleetCardMonthlySpend::create([
            'provider' => 'Shell', 'division_label' => $label, 'office_id' => $code ? $this->office($code) : null,
            'year' => 2026, 'month' => $month, 'amount' => $amount, 'source' => 'import', 'excluded_from_total' => $excluded,
        ]);
        $spend('Palawan Research Experiment Station (PRES)', 1, 119263.04, code: 'PRES');
        $spend('Regional Agricultural Engineering Division (RAED)', 1, 31062.15, code: 'RAED');
        $spend('BAR-PRES VEHICLE', 1, 14550.29, true);

        $rows = $this->body($this->book()->getSheetByName('INDEX FOR FLEETCARD'));
        $this->assertSame('SHELL FLEET CARD', $rows[0][0]);
        $total = collect($rows)->first(fn ($r) => $r[0] === 'TOTAL:');
        $this->assertEqualsWithDelta(150325.19, $total[1], 0.001);
        $this->assertSame('EXCLUDED VEHICLE', collect($rows)->first(fn ($r) => $r[0] === 'EXCLUDED VEHICLE')[0]);
        $overall = collect($rows)->first(fn ($r) => $r[0] === 'OVER ALL TOTAL:');
        $this->assertEqualsWithDelta(164875.48, $overall[1], 0.001);
    }

    public function test_repair_index_ends_with_the_year_end_summary(): void
    {
        $vehicle = $this->vehicle('Palawan', ['plate_number' => 'ZMX 441']);
        $this->maintenance($vehicle, ['scheduled_date' => '2026-02-20', 'status' => 'completed', 'completed_date' => '2026-02-25', 'cost' => 4500, 'defect_nature' => 'Wheel bearing']);
        $this->maintenance($vehicle, ['scheduled_date' => '2026-11-03', 'cost' => 1200]);
        $this->maintenance($vehicle, ['scheduled_date' => '2026-04-01', 'type' => 'Preventive', 'title' => 'PMS 10,000 km', 'odometer' => 10000, 'pm_request_no' => 'PM-07']);
        $this->maintenance($vehicle, ['scheduled_date' => '2026-05-01', 'status' => 'cancelled']);

        $rows = $this->body($this->book()->getSheetByName('INDEX FOR REPAIR OF VEHICLES'));
        $this->assertSame([1, 'PALAWAN'], array_slice($rows[0], 0, 2));
        $this->assertContains('Wheel bearing', $rows[0]);
        $summary = collect($rows)->mapWithKeys(fn ($r) => [$r[0] => $r[1] ?? null]);
        $this->assertSame(1, $summary['Total Units Repaired:']);
        $this->assertEqualsWithDelta(5700, $summary['Total Repair Cost:'], 0.001);
        $this->assertSame(1, $summary['Pending Repairs:']);
        $this->assertSame(1, $summary['Completed Repairs:']);

        $pms = $this->body($this->book()->getSheetByName('VEHICLE PMS'));
        $this->assertCount(1, $pms);
        $this->assertSame('PM-07', $pms[0][0]);
    }

    public function test_admin_downloads_only_their_own_area(): void
    {
        $this->vehicle('Palawan', ['plate_number' => 'PAL 0001']);
        $this->vehicle('Romblon', ['plate_number' => 'ROM 0001']);

        $response = $this->actingAs($this->admin('Palawan'))->get('/reports/gss-workbook?year=2026&location=Romblon&sheets[]=list-of-vehicle');
        $response->assertOk()->assertDownload('GSS_2026_Palawan.xlsx');
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());

        $this->assertSame(['List of Vehicle'], $book->getSheetNames());
        $plates = array_column($this->body($book->getSheetByName('List of Vehicle')), 3);
        $this->assertSame(['PAL 0001'], $plates);

        $this->actingAs($this->driver('Palawan'))->get('/reports/gss-workbook')->assertForbidden();
        $this->actingAs($this->admin('Palawan'))->get('/reports/gss-workbook?sheets[]=nope')->assertSessionHasErrors('sheets.0');
    }
}
