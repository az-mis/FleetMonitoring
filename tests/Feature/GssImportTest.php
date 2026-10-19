<?php

namespace Tests\Feature;

use App\Models\ImportRecord;
use App\Services\Gss\Import\GssCellParser;
use App\Services\Gss\Import\GssImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FleetCard\Models\FleetCardMonthlySpend;
use Modules\Maintenance\Models\Procurement;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * GSS workbook plan, phase 7: the one-time import, with small workbooks built in the test.
 */
class GssImportTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        foreach (glob(storage_path('app/imports/gss2026-review*.xlsx')) ?: [] as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    /** @param array<string, array<int, array<string, mixed>>> $sheets sheet => [row => [column => value]] */
    private function workbook(array $sheets): string
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);
        foreach ($sheets as $title => $rows) {
            $ws = $book->createSheet();
            $ws->setTitle($title);
            foreach ($rows as $row => $cells) {
                foreach ($cells as $col => $value) {
                    $ws->setCellValue($col.$row, $value);
                }
            }
        }
        $path = storage_path('app/gss-import-test-'.uniqid().'.xlsx');
        (new Xlsx($book))->save($path);
        $this->files[] = $path;

        return $path;
    }

    private function sample(): string
    {
        return $this->workbook([
            'VEHICLE INVENTORY AS OF 2025' => [
                9 => ['C' => 'ARTICLE', 'D' => 'DESCRIPTION', 'I' => 'PLATE NUMBER'],
                11 => ['A' => 1, 'B' => 1, 'C' => 'PICK UP', 'D' => 'TOYOTA HI-LUX 4 X 2 G DSL M/T', 'E' => 'unit', 'F' => 1, 'G' => 'MV-001', 'H' => '01-00-MV01', 'I' => 'SNE6227', 'J' => 'Q.C. Satellite Office', 'K' => '23/10/2018', 'L' => 1248800, 'M' => 'Benjamin Gardoce', 'O' => 'BOOK UP', 'P' => 'RAED', 'R' => 'Serviceable'],
                12 => ['D' => 'Year Model: 2018'],
                13 => ['D' => 'Color: Silver Metallic'],
                14 => ['D' => 'Engine #: 2GD0564636'],
                15 => ['D' => 'Frame #: MR0GB8DD4J1419440'],
                16 => ['D' => 'Conduction Sticker: A8X 654'],
                17 => ['D' => 'Plate#: SNE6227'],
                18 => ['D' => 'Accessories'],
                19 => ['D' => 'TGA Bedliner', 'K' => '26/11/2018', 'L' => 118000],
                21 => ['A' => 2, 'B' => 1, 'C' => 'PICK UP', 'D' => 'NISSAN NAVARA', 'G' => 'MV-002', 'H' => '01-00-MV02', 'J' => 'central office'],
            ],
            'List of Vehicle' => [
                9 => ['A' => 'NO.', 'B' => 'NAME OF DRIVER', 'F' => 'PLATE NO.'],
                10 => ['A' => 1, 'B' => 'MARLON MATORE', 'D' => 'Oriental Mindoro', 'E' => 15000, 'F' => 'A8X654 ( SNE 6227 )', 'G' => 'REGULATORY DIV.', 'H' => 'BJ GARDOCE', 'I' => 'TOYOTA HI-LUX', 'J' => 2018, 'K' => 'SERVICEABLE', 'L' => 'YES', 'M' => 'LTO ORMIN', 'N' => '265/65/R17', 'O' => 'TPL/COMPRE INS - JAN 27, 2025 TO JAN 27, 2026', 'Q' => '1ST WEEK AUGUST', 'R' => 'YES', 'S' => '1 set jack'],
                11 => ['A' => 2, 'B' => 'RICKY P. ESPINA', 'D' => 'Oriental Mindoro', 'F' => 'SKP 906', 'I' => 'FORD EVEREST', 'J' => 2005],
                12 => ['A' => 3, 'D' => 'Quezon City', 'F' => 'NEW 2025 -NO PLATE', 'I' => 'NISSAN NAVARA'],
            ],
            'INDEX FOR FLEETCARD' => [
                1 => ['B' => 'SHELL FLEET CARD'],
                2 => ['B' => 'DIVISION/PROGRAM', 'C' => 'JANUARY', 'D' => 'FEBRUARY'],
                3 => ['A' => 1, 'B' => 'AMAD/KADIWA', 'C' => 36664.77, 'D' => 41181.89],
                4 => ['A' => 2, 'B' => 'RICE & RTD FOR RESEARCH & REG', 'C' => 3168.67],
                5 => ['A' => 'TOTAL:', 'C' => '=SUM(C3:C4)'],
                6 => ['A' => 'EXCLUDED VEHICLE'],
                7 => ['A' => 19, 'B' => 'BAR-PRES VEHICLE', 'C' => 14550.29],
            ],
            'TRIP TICKET MONITORING' => [
                2 => ['A' => 'TRIP TICKET NUMBER'],
                4 => ['A' => 'ORIENTAL MINDORO'],
                5 => ['A' => 'JANUARY'],
                6 => ['A' => '0011-01-2026', 'B' => 46027, 'C' => 'Research', 'D' => 'Romnel B. Salazar', 'E' => 'January 6-9, 2026', 'F' => 46031, 'G' => 'Fertilizer application', 'H' => 'Mansalay, Roxas', 'I' => 'SKP-906', 'J' => 'Felix T. Marquez'],
                7 => ['A' => '0011-01-2026', 'B' => 46028, 'C' => 'Research', 'E' => 46028, 'F' => 46028, 'H' => 'Calapan City', 'I' => 'SKP906', 'J' => 'Glen M. Malbon', 'K' => 360494, 'L' => 360600],
                8 => ['A' => 46113, 'B' => 46063, 'C' => 'CORN', 'E' => 46063, 'I' => 'SNE 6227'],
                9 => ['A' => '0020-01-2026', 'E' => 'Jan 20, 2026', 'I' => 'HI-ACE'],
            ],
            'INDEX FOR REPAIR OF VEHICLES' => [
                2 => ['A' => 'NO.', 'B' => 'AREA'],
                3 => ['A' => 1, 'B' => 'ORMIN', 'C' => 'AMAD', 'D' => 46076, 'E' => 'WAGON', 'F' => 'SKP 906', 'I' => 46077, 'J' => 'Wheel bearing, brake pad', 'K' => 'Wheel bearing inner/outer', 'L' => 4500, 'M' => 'REIMBURSEMENT'],
                4 => ['A' => 2, 'B' => 'Quezon City', 'C' => 'RAED', 'D' => '6.3.26', 'F' => 'SNE 6227', 'J' => 'PMS and 1 battery replacement', 'M' => 'svp', 'N' => 'RETURNED by BUDGET'],
                6 => ['B' => 'SUMMARY ( BY THE END OF THE YEAR)'],
                7 => ['B' => 'Total Units Repaired:'],
            ],
            'VEHICLE PMS ' => [
                1 => ['A' => 'PM REQUEST NO.'],
                3 => ['A' => 7, 'C' => 'AMADO CAABAY', 'D' => 'RAED', 'E' => 'Toyota Hi-Lux', 'F' => 'SNE 6227', 'G' => '14,228 kms', 'I' => 46174, 'J' => 46204, 'L' => 'DONE PMS', 'M' => 'FOR PAYMENT'],
            ],
            'PR OF GOODS AND SERVICES' => [
                1 => ['A' => 'NO.'],
                2 => ['A' => '2026 PROCUREMENT'],
                3 => ['A' => 1, 'B' => 'PEST CONTROL', 'C' => 'Other goods', 'D' => '2026-02-490'],
                4 => ['A' => 4, 'B' => 'REPAIR AND REPLACEMENT OF PARTS FOR SNE 6227', 'C' => 'Vehicle', 'D' => '2026-03-101', 'E' => '24-02-2026', 'F' => 31450, 'G' => 'c/o BAC done posted April 22', 'H' => 'COMPLETED , PAID JULY 2026', 'K' => '6.3.26'],
            ],
        ]);
    }

    public function test_cell_parser_reads_every_date_style_and_double_plates(): void
    {
        $range = GssCellParser::date('January 5 - 9, 2026');
        $this->assertSame(['2026-01-05', '2026-01-09', false], [$range['start']->toDateString(), $range['end']->toDateString(), $range['guessed']]);
        $this->assertSame('2026-07-01', GssCellParser::date('June 29- July 1, 2026')['end']->toDateString());
        $this->assertSame('2026-03-17', GssCellParser::date('MARCH 16 - 17, 2026')['end']->toDateString());
        $this->assertSame('2026-01-09', GssCellParser::date('JANUARY 5 TO 9, 2026')['end']->toDateString());
        $this->assertSame('2026-09-06', GssCellParser::date('Sept. 6, 2026')['start']->toDateString());
        $this->assertSame('2026-01-05', GssCellParser::date(46027)['start']->toDateString());
        $this->assertSame(['2026-02-24', false], [GssCellParser::date('24-02-2026')['start']->toDateString(), GssCellParser::date('24-02-2026')['guessed']]);
        $this->assertTrue(GssCellParser::date('6.3.26')['guessed'], 'day and month could swap');
        $this->assertTrue(GssCellParser::date('2017')['guessed']);
        $this->assertNull(GssCellParser::date('on process'));

        $this->assertSame(['plate' => 'SNE 6227', 'old' => 'A8X 654', 'ambiguous' => false], GssCellParser::plates('A8X654 ( SNE 6227 )'));
        $this->assertSame('SNE 8486', GssCellParser::plates('SNE 8486 (OY5094)')['plate']);
        $this->assertTrue(GssCellParser::plates('AIS 661/SDA 1850')['ambiguous'] === false);
        $this->assertSame('SDA 1850', GssCellParser::plates('AIS 661/SDA 1850')['plate']);
        $this->assertNull(GssCellParser::plates('NEW 2025 -NO PLATE')['plate']);
        $this->assertEquals(1235.85, GssCellParser::number('1,235.85KMS'));
        $this->assertSame('Quezon City Satellite Office', GssCellParser::location('Q.C. Satellite Office'));
        $this->assertSame('Occidental Mindoro', GssCellParser::location('OCCI. MIN.'));
    }

    public function test_dry_run_saves_nothing(): void
    {
        $result = app(GssImporter::class)->run($this->sample(), 2026, commit: false);

        $this->assertFalse($result['committed']);
        $this->assertSame(0, Vehicle::count());
        $this->assertSame(0, VehicleRequest::count());
        $this->assertSame(0, ImportRecord::count());
        $this->assertGreaterThan(0, $result['counts']['VEHICLE INVENTORY AS OF 2025']['created']);
        $this->assertFileExists($result['review_file']);
        $this->assertStringEndsWith('gss2026-review-dry-run.xlsx', $result['review_file']);
    }

    public function test_import_creates_records_and_flags_what_needs_review(): void
    {
        $driver = $this->driver('Oriental Mindoro', ['name' => 'Glen Malbon']);
        $result = app(GssImporter::class)->run($this->sample(), 2026, commit: true);

        // Vehicles: inventory first, then the List of Vehicle row fills the rest of the same vehicle
        $hilux = Vehicle::where('plate_number', 'SNE 6227')->firstOrFail();
        $this->assertSame(['Toyota', 'HI-LUX 4 X 2 G DSL M/T', 'Silver Metallic', 2018], [$hilux->brand, $hilux->model, $hilux->color, $hilux->year]);
        $this->assertSame('Quezon City Satellite Office', $hilux->location, 'the inventory row comes first');
        $this->assertSame('2018-10-23', $hilux->acquisition_date->toDateString());
        $this->assertSame('RAED', $hilux->endUserOffice->code);
        $this->assertSame('A8X 654', $hilux->old_plate_number);
        $this->assertSame('Original on file', $hilux->or_cr_status);
        $this->assertSame('2026-01-27', $hilux->insurance_to->toDateString());
        $this->assertSame(1, $hilux->accessories()->count());
        $this->assertSame(2, Vehicle::count(), 'SNE 6227 and SKP 906; the item and row without plates are skipped');

        // Trips: area and month headings, duplicate ticket kept with a suffix, ticket stored as a date dropped
        $trips = VehicleRequest::orderBy('id')->get();
        $this->assertCount(3, $trips);
        $this->assertSame(['0011-01-2026', '0011-01-2026 (2)', null], $trips->pluck('trip_ticket_number')->all());
        $this->assertSame('Oriental Mindoro', $trips[0]->location);
        $this->assertSame(['2026-01-06', '2026-01-09'], [$trips[0]->travel_date->toDateString(), $trips[0]->travel_date_end->toDateString()]);
        $this->assertSame('RD', $trips[0]->office->code);
        $this->assertSame($driver->id, $trips[1]->confirmed_driver_id);
        $this->assertSame(['Felix T. Marquez', null], [$trips[0]->driver_name, $trips[1]->driver_name], 'a driver without an account is kept by name');
        $this->assertEquals(106, (float) $trips[1]->completion->distance_travelled);

        // Repairs with their PR status, PMS, procurement (vehicle rows only) and fleet card spend
        $this->assertSame(3, VehicleMaintenance::count());
        $repair = VehicleMaintenance::where('defect_nature', 'Wheel bearing, brake pad')->firstOrFail();
        $this->assertSame(['completed', 'Repair', 'Reimbursement'], [$repair->status, $repair->type, $repair->procurements->first()->status]);
        $this->assertSame('Preventive', VehicleMaintenance::where('pm_request_no', '7')->value('type'));
        $pr = Procurement::where('pr_number', '2026-03-101')->firstOrFail();
        $this->assertSame(['Completed', '2026-02-24'], [$pr->status, $pr->pr_date->toDateString()]);
        $this->assertNull(Procurement::where('pr_number', '2026-02-490')->first(), 'other goods stay in Excel');
        $this->assertEquals(41181.89, (float) FleetCardMonthlySpend::where('month', 2)->value('amount'));
        $this->assertTrue(FleetCardMonthlySpend::where('division_label', 'BAR-PRES VEHICLE')->value('excluded_from_total'));
        $this->assertSame('Agribusiness & Marketing Assistance Division (AMAD)', FleetCardMonthlySpend::where('month', 1)->where('excluded_from_total', false)->orderBy('id')->value('division_label'));

        // Review list: guessed or unmatched rows are flagged, unusable rows listed as not imported
        $review = IOFactory::load($result['review_file'])->getActiveSheet()->toArray();
        $reasons = implode("\n", array_column($review, 3));
        $this->assertStringContainsString('appears more than once', $reasons);
        $this->assertStringContainsString('holds a date or number', $reasons);
        $this->assertStringContainsString('Vehicle "HI-ACE" not found', $reasons);
        $this->assertStringContainsString('"6.3.26" could not be read for sure', $reasons);
        $this->assertStringContainsString('matches no office', $reasons);
        $this->assertTrue(ImportRecord::where('needs_review', true)->exists());
    }

    public function test_running_the_import_again_changes_nothing(): void
    {
        $file = $this->sample();
        app(GssImporter::class)->run($file, 2026, commit: true);
        $before = [Vehicle::count(), VehicleRequest::count(), VehicleMaintenance::count(), Procurement::count(), FleetCardMonthlySpend::count(), ImportRecord::count()];

        $second = app(GssImporter::class)->run($file, 2026, commit: true);

        $this->assertSame($before, [Vehicle::count(), VehicleRequest::count(), VehicleMaintenance::count(), Procurement::count(), FleetCardMonthlySpend::count(), ImportRecord::count()]);
        foreach ($second['counts'] as $sheet => $counts) {
            $this->assertSame(0, $counts['created'] + $counts['updated'], "{$sheet} changed on the second run");
        }
        $this->assertGreaterThan(0, $second['counts']['TRIP TICKET MONITORING']['unchanged']);
    }

    public function test_existing_vehicles_only_get_their_empty_fields_filled(): void
    {
        $vehicle = $this->vehicle('Palawan', ['plate_number' => 'SNE 6227', 'brand' => 'Toyota', 'model' => 'Hilux Conquest', 'color' => 'White', 'par_number' => 'KEEP-ME']);

        app(GssImporter::class)->run($this->sample(), 2026, commit: true);

        $vehicle->refresh();
        $this->assertSame(['Hilux Conquest', 'White', 'Palawan', 'KEEP-ME'], [$vehicle->model, $vehicle->color, $vehicle->location, $vehicle->par_number]);
        $this->assertSame('01-00-MV01', $vehicle->property_number, 'an empty field is filled');
        $this->assertSame(1, Vehicle::where('plate_number', 'SNE 6227')->count());
    }

    public function test_command_runs_a_dry_run_by_default(): void
    {
        $file = $this->sample();

        $this->artisan('gss:import', ['file' => $file, '--year' => 2026])
            ->expectsOutputToContain('Dry run: nothing was saved')
            ->assertSuccessful();
        $this->assertSame(0, Vehicle::count());

        $this->artisan('gss:import', ['file' => $file, '--year' => 2026, '--commit' => true])->expectsOutputToContain('Saved.')->assertSuccessful();
        $this->assertSame(2, Vehicle::count());
        $this->artisan('gss:import', ['file' => 'missing.xlsx'])->assertFailed();
    }
}
