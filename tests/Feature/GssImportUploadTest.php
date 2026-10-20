<?php

namespace Tests\Feature;

use App\Models\ImportRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\FleetCard\Models\FleetCardMonthlySpend;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\VehicleRequest\Models\TripTicketCompletion;
use Modules\VehicleRequest\Models\VehicleRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * Importing an updated GSS workbook from the Reports page: Super Admin only, checked first, saved on confirmation,
 * and a re-import updates the rows it imported before instead of adding them again.
 */
class GssImportUploadTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ([...$this->files, ...(glob(storage_path('app/imports/gss2026-review*.xlsx')) ?: []), ...(glob(storage_path('app/private/imports/uploads/*.xlsx')) ?: [])] as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    /** A workbook with one Palawan trip per given row, a repair and a fleet card spend row */
    private function upload(array $trips, string $repairStatus = 'PR On PROCESS', float $spend = 1000): UploadedFile
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);
        $sheets = [
            'TRIP TICKET MONITORING' => [4 => ['A' => 'PALAWAN']] + array_combine(range(5, 4 + count($trips)), $trips),
            'INDEX FOR REPAIR OF VEHICLES' => [3 => ['A' => 1, 'B' => 'PALAWAN', 'D' => 46076, 'F' => 'SKP 674', 'J' => 'Brake pad replacement', 'L' => 4500, 'M' => $repairStatus]],
            'INDEX FOR FLEETCARD' => [1 => ['B' => 'SHELL FLEET CARD'], 3 => ['A' => 1, 'B' => 'PRES', 'C' => $spend]],
        ];
        foreach ($sheets as $title => $rows) {
            $ws = $book->createSheet();
            $ws->setTitle($title);
            foreach ($rows as $row => $cells) {
                foreach ($cells as $col => $value) {
                    $ws->setCellValue($col.$row, $value);
                }
            }
        }
        $path = storage_path('app/gss-upload-test-'.uniqid().'.xlsx');
        (new Xlsx($book))->save($path);
        $this->files[] = $path;

        return new UploadedFile($path, 'GSS 2026.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function trip(string $ticket, string $destination, array $extra = []): array
    {
        return $extra + ['A' => $ticket, 'B' => 46027, 'C' => 'PRES', 'E' => 'January 5, 2026', 'G' => 'Field visit', 'H' => $destination, 'I' => 'SKP 674', 'J' => 'Celso Delos Reyes'];
    }

    public function test_only_a_super_admin_can_import(): void
    {
        $admin = $this->admin('Palawan');
        $this->actingAs($admin)->post('/reports/gss-import', ['workbook' => $this->upload([$this->trip('26-01-001', 'City Proper')]), 'year' => 2026])->assertForbidden();
        $this->actingAs($admin)->get('/reports/gss-import')->assertForbidden();
        $this->actingAs($admin)->post('/reports/gss-import/commit', ['token' => 'x'])->assertForbidden();
        $this->actingAs($admin)->get('/reports/gss-import/review/gss2026-review.xlsx')->assertForbidden();
        $this->actingAs($this->driver('Palawan'))->post('/reports/gss-import', ['year' => 2026])->assertForbidden();
        $this->assertSame(0, VehicleRequest::count());
    }

    public function test_the_file_is_checked_first_and_saved_only_when_confirmed(): void
    {
        $super = $this->superAdmin();
        $this->vehicle('Palawan', ['plate_number' => 'SKP 674']);

        $this->actingAs($super)->post('/reports/gss-import', ['workbook' => UploadedFile::fake()->create('notes.pdf', 10), 'year' => 2026])
            ->assertSessionHasErrors('workbook');

        $this->actingAs($super)->post('/reports/gss-import', ['workbook' => $this->upload([$this->trip('26-01-001', 'City Proper')]), 'year' => 2026])
            ->assertRedirect('/reports/gss-import');
        $this->assertSame(0, VehicleRequest::count(), 'the check saves nothing');
        $this->actingAs($super)->get('/reports/gss-import')->assertOk()
            ->assertSee('Workbook checked')->assertSee('Nothing has been saved yet')->assertSee('TRIP TICKET MONITORING')->assertSee('Import now');
        $this->actingAs($super)->get('/reports/gss-import/review/gss2026-review-dry-run.xlsx')->assertOk()->assertDownload('gss2026-review-dry-run.xlsx');
        $this->actingAs($super)->get('/reports/gss-import/review/..%2F..%2F.env')->assertNotFound();

        $token = session('gss_import')['token'];
        $this->actingAs($super)->post('/reports/gss-import/commit', ['token' => $token])->assertRedirect('/reports/gss-import');
        $this->assertSame(1, VehicleRequest::count());
        $this->assertSame(1, VehicleMaintenance::count());
        $this->actingAs($super)->get('/reports/gss-import')->assertOk()->assertSee('Workbook imported')->assertSee('Saved.')->assertDontSee('Import now');

        // The uploaded file is gone once imported, so the same check cannot be imported twice
        $this->actingAs($super)->post('/reports/gss-import/commit', ['token' => $token])->assertSessionHasErrors('gss_import');
        $this->assertSame(1, VehicleRequest::count());
    }

    public function test_an_updated_workbook_updates_what_it_imported_before(): void
    {
        $super = $this->superAdmin();
        $this->vehicle('Palawan', ['plate_number' => 'SKP 674']);
        $import = function (array $trips, string $repairStatus, float $spend) use ($super): void {
            $this->actingAs($super)->post('/reports/gss-import', ['workbook' => $this->upload($trips, $repairStatus, $spend), 'year' => 2026]);
            $this->actingAs($super)->post('/reports/gss-import/commit', ['token' => session('gss_import')['token']]);
        };

        $import([$this->trip('26-01-001', 'City Proper'), $this->trip('26-01-002', 'Irawan', ['K' => 1000])], 'PR On PROCESS', 1000);
        $this->assertSame(2, VehicleRequest::count());
        $first = VehicleRequest::where('trip_ticket_number', '26-01-001')->firstOrFail();

        // A driver logs Part B in the system for the second trip; the workbook must not replace it
        $second = VehicleRequest::where('trip_ticket_number', '26-01-002')->firstOrFail();
        $second->completion->update(['odo_beginning' => 5000, 'odo_ending' => 5100, 'remarks' => 'Logged by the driver']);
        // GSS records the March statement in the system; the workbook must not replace it either
        FleetCardMonthlySpend::where('month', 1)->update(['source' => 'statement', 'amount' => 999]);

        // The updated workbook: a corrected destination, a corrected travel date, a new trip, a paid repair, new readings
        $import([
            $this->trip('26-01-001', 'City Proper and DA-PAC'),
            $this->trip('26-01-002', 'Irawan', ['E' => 'January 6, 2026', 'K' => 2000]),
            $this->trip('26-01-003', 'Brooke\'s Point'),
        ], 'REIMBURSEMENT', 1500);

        $this->assertSame(3, VehicleRequest::count(), 'no trip is added twice');
        $this->assertSame('City Proper and DA-PAC', $first->fresh()->destination);
        $this->assertSame($first->reference_code, $first->fresh()->reference_code);
        $this->assertSame('2026-01-06', $second->fresh()->travel_date->toDateString(), 'found again by its ticket number');
        $this->assertEquals(5000, (float) TripTicketCompletion::where('vehicle_request_id', $second->id)->value('odo_beginning'));
        $this->assertSame(1, VehicleMaintenance::count());
        $this->assertSame('Reimbursement', VehicleMaintenance::first()->procurements()->value('status'));
        $this->assertEquals(999, (float) FleetCardMonthlySpend::where('month', 1)->value('amount'));
        $this->assertSame(['created' => 1, 'updated' => 2], array_intersect_key(session('gss_import')['counts']['TRIP TICKET MONITORING'], ['created' => 0, 'updated' => 0]));
        $this->assertSame(0, ImportRecord::where('key', 'like', 'trip:%')->count() - 3, 'one import record per trip');
    }
}
