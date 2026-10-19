<?php

declare(strict_types=1);

namespace App\Services\Gss\Import;

use App\Models\ImportRecord;
use App\Models\Office;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Enums\UserRole;
use Modules\FleetCard\Models\FleetCard;
use Modules\FleetCard\Models\FleetCardMonthlySpend;
use Modules\Maintenance\Models\Procurement;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Controllers\VehicleRegistryController;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleAccessory;
use Modules\VehicleRequest\Models\TripTicketCompletion;
use Modules\VehicleRequest\Models\VehicleRequest;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * One-time import of a GSS workbook ("GSS 2026.xlsx") into the system.
 *
 * - Each row is keyed (plate, ticket number, PR number, or the row's own values), recorded in import_records,
 *   and skipped on a second run, so running it twice changes nothing.
 * - Vehicles already in the system only get their empty fields filled; nothing typed in the system is overwritten.
 * - Rows read with a guess (a date that could be read two ways, a double plate, an unknown driver) are imported and
 *   marked "needs review"; rows that cannot be imported (no matching vehicle, no date) are skipped. Both go into
 *   an Excel review list with the reason.
 * - A dry run does everything inside a transaction and rolls it back.
 */
final class GssImporter
{
    public const SOURCE_PREFIX = 'gss';

    /** Workbook division names → office codes */
    private const OFFICE_ALIASES = [
        'AMAD/KADIWA' => 'AMAD', 'KADIWA' => 'AMAD', 'RTD FOR OPERATION' => 'ORTDO', 'RTD FOR OPERATIONS' => 'ORTDO',
        'RTD FOR RESEARCH & REG.' => 'ORTDRR', 'RTD FOR RESEARCH & REG' => 'ORTDRR', 'RTD RES. & REG.' => 'ORTDRR',
        'LIVESTOCK' => 'FOD-LIVESTOCK', 'REGULATORY' => 'REGDIV', 'REGULATORY DIV.' => 'REGDIV', 'RESEARCH DIVISION' => 'RD',
        'RESEARCH' => 'RD', 'RESEARCH DIV.' => 'RD', 'ADMIN & FINANCE DIVISION' => 'AFD', 'ADMIN AND FINANCE' => 'AFD',
        'ADMIN DIV.' => 'AFD', 'RICE' => 'FOD-RICE', 'CORN' => 'FOD-CORN', 'HVCDP' => 'FOD-HVCDP', 'APCO ORMIN' => 'APCO-ORIENTAL',
        'APCO OCCIMIN' => 'APCO-OCCIDENTAL', 'APCO PALAWAN' => 'APCO-PALAWAN', 'INTEGLAB' => 'ILD', 'INTEG LAB' => 'ILD',
        "NAT'L SOILS HEALTH PROGRAM" => 'ILD-RSL', 'GSS' => 'AFD-GSS', 'BUDGET' => 'AFD-BUDGET', 'BUDGET SECTION' => 'AFD-BUDGET',
        'H.R' => 'AFD-HRS', 'HR' => 'AFD-HRS', 'PRDP' => 'FOD-PRDP', 'RSBSA' => 'FOD-RSBSA', 'RICE/RSBSA' => 'FOD-RSBSA',
    ];

    /** Text sizes of the vehicle fields the workbook fills */
    private const VEHICLE_LIMITS = [
        'plate_number' => 30, 'old_plate_number' => 30, 'conduction_sticker' => 30, 'par_number' => 40, 'property_number' => 40,
        'brand' => 60, 'model' => 120, 'color' => 60, 'engine_number' => 60, 'chassis_number' => 60, 'vehicle_type' => 60,
        'custodian_name' => 150, 'assigned_person' => 150, 'book_status' => 40, 'condition' => 40, 'or_cr_status' => 40,
        'registration_place' => 100, 'renewal_schedule' => 100, 'insurance_type' => 60, 'tire_type' => 60, 'tools' => 255,
        'registry_remarks' => 2000, 'history' => 2000,
    ];

    private const PR_STATUS_WORDS = [
        'cancel' => 'Cancelled', 'paid' => 'Completed', 'completed' => 'Completed', 'for payment' => 'For payment',
        'payment' => 'For payment', 'reimburs' => 'Reimbursement', 'svp' => 'SVP', 'pr on process' => 'PR on process', 'process' => 'PR on process',
    ];

    private string $source;

    private int $year;

    /** @var array<string, array<string, int>> sheet => created|updated|skipped|unchanged|review */
    private array $counts = [];

    /** @var list<array<string, mixed>> */
    private array $review = [];

    /** @var array<string, int> plate key → vehicle id */
    private array $plates = [];

    /** @var array<string, int> office code → id */
    private array $officeCodes = [];

    /** @var array<int, string> office id → name */
    private array $officeNames = [];

    /** @var list<array{id: int, location: ?string, surname: string, initial: ?string}> */
    private array $drivers = [];

    /**
     * @return array{counts: array<string, array<string, int>>, review_file: string, review_rows: int, committed: bool}
     */
    public function run(string $path, int $year, bool $commit): array
    {
        $this->year = $year;
        $this->source = self::SOURCE_PREFIX.$year;
        $book = $this->load($path);

        DB::beginTransaction();
        try {
            $this->prepare();
            $this->sheet($book, 'VEHICLE INVENTORY', fn (Worksheet $ws) => $this->inventory($ws));
            $this->sheet($book, 'LIST OF VEHICLE', fn (Worksheet $ws) => $this->vehicleList($ws));
            $this->sheet($book, 'PETRON FLEET CARD', fn (Worksheet $ws) => $this->petron($ws));
            $this->sheet($book, 'INDEX FOR FLEETCARD', fn (Worksheet $ws) => $this->fleetcardIndex($ws));
            $this->sheet($book, 'TRIP TICKET MONITORING', fn (Worksheet $ws) => $this->trips($ws));
            $this->sheet($book, 'FUEL WEEKLY CONSUMPTION', fn (Worksheet $ws) => $this->fuel($ws));
            $this->sheet($book, 'INDEX FOR REPAIR OF VEHICLES', fn (Worksheet $ws) => $this->repairs($ws));
            $this->sheet($book, 'VEHICLE PRE & POST REPAIR', fn (Worksheet $ws) => $this->cmValues($ws));
            $this->sheet($book, 'VEHICLE PMS', fn (Worksheet $ws) => $this->pms($ws));
            $this->sheet($book, 'PR OF GOODS AND SERVICES', fn (Worksheet $ws) => $this->procurements($ws));
            $commit ? DB::commit() : DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            $book->disconnectWorksheets();
        }

        return [
            'counts' => $this->counts,
            'review_file' => $this->writeReview($commit),
            'review_rows' => count($this->review),
            'committed' => $commit,
        ];
    }

    /* ───────────────────────── setup ───────────────────────── */

    private function load(string $path): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $wanted = ['VEHICLE INVENTORY', 'LIST OF VEHICLE', 'PETRON FLEET CARD', 'INDEX FOR FLEETCARD', 'TRIP TICKET MONITORING', 'FUEL WEEKLY CONSUMPTION', 'INDEX FOR REPAIR OF VEHICLES', 'VEHICLE PRE & POST REPAIR', 'VEHICLE PMS', 'PR OF GOODS AND SERVICES'];
        $names = array_values(array_filter($reader->listWorksheetNames($path), function (string $name) use ($wanted): bool {
            foreach ($wanted as $prefix) {
                if (str_starts_with(strtoupper(trim($name)), $prefix)) {
                    return true;
                }
            }

            return false;
        }));
        $reader->setLoadSheetsOnly($names);

        return $reader->load($path);
    }

    private function prepare(): void
    {
        $this->plates = [];
        foreach (Vehicle::query()->get(['id', 'plate_number', 'old_plate_number', 'conduction_sticker']) as $vehicle) {
            $this->rememberPlates($vehicle);
        }
        foreach (Office::query()->get(['id', 'code', 'name']) as $office) {
            $this->officeCodes[strtoupper($office->code)] = $office->id;
            $this->officeNames[$office->id] = $office->name;
        }
        $this->drivers = User::query()->where('role', UserRole::DRIVER->value)->get(['id', 'name', 'location'])
            ->map(fn (User $u) => ['id' => $u->id, 'location' => $u->location] + $this->nameParts($u->name))
            ->filter(fn ($d) => $d['surname'] !== '')
            ->values()->all();
    }

    private function sheet(Spreadsheet $book, string $prefix, callable $handler): void
    {
        foreach ($book->getSheetNames() as $name) {
            if (str_starts_with(strtoupper(trim($name)), $prefix)) {
                $this->counts[$name] ??= ['created' => 0, 'updated' => 0, 'skipped' => 0, 'unchanged' => 0, 'review' => 0];
                $handler($book->getSheetByName($name));

                return;
            }
        }
    }

    /* ───────────────────────── vehicles ───────────────────────── */

    /** "VEHICLE INVENTORY AS OF 2025": an item row, then detail lines (Year Model:, Color:…) and its accessories */
    private function inventory(Worksheet $ws): void
    {
        $item = null;
        $flush = function () use (&$item, $ws): void {
            if ($item) {
                $this->inventoryItem($ws->getTitle(), $item);
            }
            $item = null;
        };

        foreach ($this->rows($ws, 10, 'T') as $r => $c) {
            $isItem = is_numeric($c['A'] ?? null) && (GssCellParser::text($c['C'] ?? null) || GssCellParser::text($c['D'] ?? null))
                && (GssCellParser::text($c['I'] ?? null) || GssCellParser::text($c['G'] ?? null) || GssCellParser::text($c['H'] ?? null));
            if ($isItem) {
                $flush();
                $item = ['row' => $r, 'cells' => $c, 'details' => [], 'accessories' => [], 'notes' => [], 'inAccessories' => false];

                continue;
            }
            if (! $item || ! ($line = GssCellParser::text($c['D'] ?? null))) {
                continue;
            }
            if (preg_match('/^(year model|color|engine ?#|frame ?#|conduction sticker|plate ?#)\s*:?\s*(.*)$/i', $line, $m)) {
                $item['details'][strtolower(str_replace([' ', '#'], '', $m[1]))] = GssCellParser::text($m[2]);
            } elseif (preg_match('/^accessories$/i', $line)) {
                $item['inAccessories'] = true;
            } elseif ($item['inAccessories'] && ! preg_match('/^w\/ /i', $line)) {
                $item['accessories'][] = ['description' => Str::limit($line, 200, ''), 'date' => GssCellParser::date($c['K'] ?? null)['start'] ?? null, 'value' => GssCellParser::number($c['L'] ?? null)];
            } else {
                $item['notes'][] = $line;
            }
        }
        $flush();
    }

    private function inventoryItem(string $sheet, array $item): void
    {
        $c = $item['cells'];
        $reasons = [];
        $plates = GssCellParser::plates(GssCellParser::text($c['I'] ?? null) ?? ($item['details']['plate'] ?? null));
        if (! $plates['plate']) {
            $this->skip($sheet, $item['row'], 'inventory:'.($c['H'] ?? $c['G'] ?? $item['row']), 'No plate number, so the item could not be matched to a vehicle.', $c);

            return;
        }
        $key = 'vehicle:'.GssCellParser::plateKey($plates['plate']);
        if ($this->seen($sheet, $key)) {
            return;
        }
        if ($plates['ambiguous']) {
            $reasons[] = "Two plates in \"{$c['I']}\"; {$plates['plate']} was used.";
        }

        $location = GssCellParser::location($c['J'] ?? null);
        if (! $location && ($location = GssCellParser::location($c['T'] ?? null))) {
            $reasons[] = 'Area "'.($c['J'] ?? '').'" not recognised; '.$location.' taken from column T.';
        } elseif (! $location) {
            $reasons[] = 'Area "'.($c['J'] ?? '').'" not recognised; set the location on the vehicle.';
        }
        // Early items list details on the lines below; later ones write them inline ("41. Wagon Mitsubishi Adventure Model 2004 Motor # …")
        $described = $this->describe(GssCellParser::text($c['D'] ?? null));
        $acquired = GssCellParser::date($c['K'] ?? null);
        if ($acquired && $acquired['guessed']) {
            $reasons[] = "Date acquired \"{$c['K']}\" read with a guess.";
        }
        $status = GssCellParser::text($c['O'] ?? null);
        $isStatus = $status !== null && strlen($status) <= 40 && preg_match('/book|reconcil/i', $status);

        $values = [
            'plate_number' => $plates['plate'],
            'old_plate_number' => $plates['old'],
            'brand' => $described['brand'],
            'model' => $described['model'],
            'year' => (int) ($item['details']['yearmodel'] ?? 0) ?: $described['year'],
            'color' => $item['details']['color'] ?? $described['color'] ?? '—',
            'engine_number' => $item['details']['engine'] ?? $described['engine'],
            'chassis_number' => $item['details']['frame'] ?? $described['chassis'],
            'conduction_sticker' => $item['details']['conductionsticker'] ?? null,
            'vehicle_type' => ($t = GssCellParser::text($c['C'] ?? null)) ? Str::title($t) : null,
            'location' => $location,
            'par_number' => GssCellParser::text($c['G'] ?? null),
            'property_number' => GssCellParser::text($c['H'] ?? null),
            'acquisition_date' => $acquired['start'] ?? null,
            'acquisition_cost' => GssCellParser::number($c['L'] ?? null),
            'custodian_name' => GssCellParser::text($c['M'] ?? null),
            // Column O holds the book status, but sometimes a transfer note; notes go to the remarks
            'book_status' => $isStatus ? strtoupper($status) : (GssCellParser::text($c['N'] ?? null) ? 'FOR RECONCILIATION' : null),
            'end_user_office_id' => $this->office(GssCellParser::text($c['P'] ?? null)),
            'registry_remarks' => Str::limit(collect([$isStatus ? null : $status, GssCellParser::text($c['Q'] ?? null), ...$item['notes']])->filter()->unique()->implode('; '), 2000, ''),
            'condition' => $this->condition(GssCellParser::text($c['R'] ?? null)),
            // Column S is sometimes only a running number
            'history' => is_numeric($c['S'] ?? null) ? null : GssCellParser::text($c['S'] ?? null),
        ];

        [$vehicle, $created] = $this->upsertVehicle($values);
        if ($created || $vehicle->accessories()->doesntExist()) {
            foreach ($item['accessories'] as $accessory) {
                VehicleAccessory::create(['vehicle_id' => $vehicle->id, 'description' => $accessory['description'], 'date_acquired' => $accessory['date'], 'value' => $accessory['value']]);
            }
        }
        $this->done($sheet, $item['row'], $key, $vehicle, $created ? 'created' : 'updated', $reasons, $c);
    }

    /** "List of Vehicle": driver, assignment, papers and equipment of each vehicle */
    private function vehicleList(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        foreach ($this->rows($ws, 10, 'S') as $r => $c) {
            if (! GssCellParser::text($c['F'] ?? null) || ! (is_numeric($c['A'] ?? null) || GssCellParser::text($c['B'] ?? null))) {
                continue;
            }
            $plates = GssCellParser::plates($c['F']);
            if (! $plates['plate']) {
                $this->skip($sheet, $r, 'list:row'.$r, "No plate number (\"{$c['F']}\").", $c);

                continue;
            }
            $key = 'list:'.GssCellParser::plateKey($plates['plate']);
            if ($this->seen($sheet, $key)) {
                continue;
            }
            $reasons = [];
            if ($plates['ambiguous']) {
                $reasons[] = "Two plates in \"{$c['F']}\"; {$plates['plate']} was used.";
            }
            $existing = $this->vehicleByPlates($plates);
            $described = $this->describe(GssCellParser::text($c['I'] ?? null));
            [$brand, $model] = [$described['brand'], $described['model']];
            if (! $existing) {
                $reasons[] = 'Vehicle not in the inventory sheet; created from this row.';
            }
            $location = GssCellParser::location($c['D'] ?? null);
            [$insuranceType, $from, $to] = $this->insurance(GssCellParser::text($c['O'] ?? null));
            if (GssCellParser::text($c['O'] ?? null) && ! $to) {
                $reasons[] = "Insurance dates not found in \"{$c['O']}\".";
            }
            $registered = $this->firstDate(GssCellParser::text($c['P'] ?? null));
            $driverName = GssCellParser::text($c['B'] ?? null);
            $driverId = $this->driver($driverName, $location);

            $values = [
                'plate_number' => $plates['plate'],
                'old_plate_number' => $plates['old'],
                'brand' => $brand,
                'model' => $model,
                'color' => '—',
                'year' => (int) ($c['J'] ?? 0) ?: null,
                'location' => $location,
                'fleet_card_allowance' => GssCellParser::number($c['E'] ?? null),
                'end_user_office_id' => $this->office(GssCellParser::text($c['G'] ?? null)),
                'assigned_person' => GssCellParser::text($c['H'] ?? null),
                'condition' => $this->condition(GssCellParser::text($c['K'] ?? null)),
                'or_cr_status' => $this->orCr(GssCellParser::text($c['L'] ?? null)),
                'registration_place' => Str::limit((string) GssCellParser::text($c['M'] ?? null), 100, '') ?: null,
                'tire_type' => Str::limit((string) GssCellParser::text($c['N'] ?? null), 60, '') ?: null,
                'insurance_type' => $insuranceType,
                'insurance_from' => $from,
                'insurance_to' => $to,
                'registration_expiry' => $registered?->addYear(),
                'renewal_schedule' => Str::limit((string) GssCellParser::text($c['Q'] ?? null), 100, '') ?: null,
                'has_ewd' => match (strtoupper((string) GssCellParser::text($c['R'] ?? null))) {
                    'YES', 'Y' => true, 'NO', 'N' => false, default => null
                },
                'tools' => Str::limit((string) GssCellParser::text($c['S'] ?? null), 255, '') ?: null,
                'assigned_driver_id' => $driverId,
            ];
            if ($registered) {
                $reasons[] = 'Registration valid-until date assumed one year after "'.$c['P'].'".';
            }
            if ($driverName && ! $driverId && ! preg_match('/no driver/i', $driverName)) {
                $reasons[] = "Driver \"{$driverName}\" has no driver account in {$location}; not assigned.";
            }

            [$vehicle, $created] = $this->upsertVehicle($values, $existing);
            $this->done($sheet, $r, $key, $vehicle, $created ? 'created' : 'updated', $reasons, $c);
        }
    }

    /* ───────────────────────── fleet cards ───────────────────────── */

    /** "PETRON FLEET CARD": marks the vehicle's registered card as Petron with its allowed products */
    private function petron(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        $area = null;
        foreach ($this->rows($ws, 2, 'C') as $r => $c) {
            if ($loc = GssCellParser::location($c['A'] ?? null)) {
                $area = $loc;
            }
            $label = GssCellParser::text($c['B'] ?? null);
            if (! $label || stripos($label, 'PLATE NUMBER') !== false) {
                continue;
            }
            $plate = $this->plateIn($label);
            $key = 'petron:'.($plate ? GssCellParser::plateKey($plate) : 'row'.$r);
            if ($this->seen($sheet, $key)) {
                continue;
            }
            // "D1-C250/SNE-5690 - Hauling Truck": every plate written for the vehicle, so trips using the other one match too
            $written = array_values(array_filter(array_map(
                fn ($part) => $this->plateIn($part) ?? trim((string) preg_split('/\s+-\s+/', $part)[0]),
                explode('/', $label),
            ), fn ($p) => preg_match('/\d/', $p) && strlen(GssCellParser::plateKey($p)) >= 4 && strlen(GssCellParser::plateKey($p)) <= 9));
            $vehicle = null;
            foreach (array_unique(array_filter([$plate, ...$written])) as $candidate) {
                if ($vehicle = $this->vehicleByPlates(['plate' => $candidate, 'old' => null])) {
                    break;
                }
            }
            if (! $vehicle) {
                $this->skip($sheet, $r, $key, "Vehicle \"{$label}\" not found.", $c);

                continue;
            }
            $aliases = array_values(array_filter($written, fn ($p) => GssCellParser::plateKey($p) !== GssCellParser::plateKey($vehicle->plate_number)));
            if ($aliases) {
                if (blank($vehicle->old_plate_number)) {
                    $vehicle->update(['old_plate_number' => Str::limit(GssCellParser::formatPlate($aliases[0]), 30, '')]);
                }
                $this->rememberPlates($vehicle, $aliases);
            }
            $card = FleetCard::where('vehicle_id', $vehicle->id)->first();
            if (! $card) {
                $this->skip($sheet, $r, $key, "No fleet card is registered for {$vehicle->plate_number} in the system yet; register it, then set the provider to Petron.", $c);

                continue;
            }
            $card->fill(array_filter([
                'provider' => $card->provider ?: 'Petron',
                'allowed_products' => $card->allowed_products ?: GssCellParser::text($c['C'] ?? null),
            ]))->save();
            $this->done($sheet, $r, $key, $card, 'updated', $area && $area !== $card->location ? ["Card is in {$card->location} but listed under {$area}."] : [], $c);
        }
    }

    /** "INDEX FOR FLEETCARD": monthly spend per division, per provider block; rows under EXCLUDED VEHICLE stay out of the total */
    private function fleetcardIndex(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        $provider = 'Shell';
        $excluded = false;
        foreach ($this->rows($ws, 1, 'N') as $r => $c) {
            $a = strtoupper((string) GssCellParser::text($c['A'] ?? null));
            $b = GssCellParser::text($c['B'] ?? null);
            if ($b && preg_match('/(SHELL|PETRON)\s+FLEET\s*CARD/i', $b, $m)) {
                $provider = ucfirst(strtolower($m[1]));
                $excluded = false;

                continue;
            }
            if (str_starts_with($a, 'EXCLUDED')) {
                $excluded = true;

                continue;
            }
            if (! $b || str_starts_with($a, 'TOTAL') || str_starts_with($a, 'OVER') || stripos($b, 'DIVISION/PROGRAM') !== false) {
                continue;
            }
            $officeId = $this->office($b, strict: true);
            $label = $officeId ? $this->officeNames[$officeId] : $b;
            foreach (range(1, 12) as $month) {
                $col = chr(ord('C') + $month - 1);
                $amount = GssCellParser::number($c[$col] ?? null);
                if ($amount === null || str_starts_with((string) ($c[$col] ?? ''), '=')) {
                    continue;
                }
                $key = "spend:{$provider}:".Str::slug($b).":{$month}";
                if ($this->seen($sheet, $key)) {
                    continue;
                }
                $spend = FleetCardMonthlySpend::updateOrCreate(
                    ['provider' => $provider, 'division_label' => $label, 'year' => $this->year, 'month' => $month],
                    ['office_id' => $officeId, 'amount' => $amount, 'source' => 'import', 'excluded_from_total' => $excluded],
                );
                $this->done($sheet, $r, $key, $spend, 'created', $officeId ? [] : ["Division \"{$b}\" matches no office; kept as written."], $c);
            }
        }
    }

    /* ───────────────────────── trips ───────────────────────── */

    /** "TRIP TICKET MONITORING": approved past trips by area, with speedometer readings as the trip's Part B */
    private function trips(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        $area = null;
        foreach ($this->rows($ws, 4, 'L') as $r => $c) {
            $a = GssCellParser::text($c['A'] ?? null);
            $filled = array_filter($c, fn ($v) => GssCellParser::text($v) !== null);
            if (count($filled) === 1 && $a !== null && ! is_numeric($c['A'])) {
                if ($loc = GssCellParser::location($a)) {
                    $area = $loc;
                }

                continue;
            }
            $plateText = GssCellParser::text($c['I'] ?? null);
            if (! $plateText && ! GssCellParser::text($c['E'] ?? null)) {
                continue;
            }
            $key = 'trip:'.sha1(json_encode([$area, $c['A'] ?? null, $c['B'] ?? null, $c['E'] ?? null, $c['I'] ?? null, $c['J'] ?? null]));
            if ($this->seen($sheet, $key)) {
                continue;
            }
            if (! $area) {
                $this->skip($sheet, $r, $key, 'No area heading above this row.', $c);

                continue;
            }
            $vehicle = $this->findVehicle($plateText);
            if (! $vehicle) {
                $this->skip($sheet, $r, $key, 'Vehicle "'.($plateText ?? '').'" not found; add the vehicle (or fix the plate) and import again.', $c);

                continue;
            }

            $reasons = [];
            $travel = GssCellParser::date($c['E'] ?? null, $this->year) ?? GssCellParser::date($c['B'] ?? null, $this->year);
            if (! $travel) {
                $this->skip($sheet, $r, $key, 'Travel date "'.($c['E'] ?? '').'" could not be read.', $c);

                continue;
            }
            $return = GssCellParser::date($c['F'] ?? null, $this->year);
            if ($travel['guessed'] || ($return['guessed'] ?? false)) {
                $reasons[] = 'Travel date read with a guess ("'.($c['E'] ?? '').'" / "'.($c['F'] ?? '').'").';
            }
            // A wrong year typed into a date (July 2027 on a trip recorded July 2026) is set back to the workbook's year
            $recordedOn = GssCellParser::date($c['B'] ?? null, $this->year)['start'] ?? null;
            $start = $this->sameYear($travel['start'], $recordedOn, 'Travel date', $reasons);
            $end = $this->sameYear($travel['end'], $start, 'Travel end date', $reasons);
            if ($return) {
                $back = $this->sameYear($return['end'], $start, 'Date of return', $reasons);
                // A return before the trip starts or over a month after it is a typing slip; the travel date is kept
                if ($back->lt($start) || $back->diffInDays($start) > 31) {
                    $reasons[] = 'Date of return '.$return['end']->format('M j, Y').' does not fit a trip starting '.$start->format('M j, Y').'; it was ignored.';
                } else {
                    $end = $back->max($end);
                }
            }
            $travel['start'] = $start;
            if ((int) $start->format('Y') !== $this->year) {
                $reasons[] = 'Travel date '.$start->format('M j, Y')." is outside {$this->year}; check the year.";
            }
            if (! GssCellParser::text($c['E'] ?? null)) {
                $reasons[] = 'No travel date; the date recorded was used.';
            }

            $ticket = $this->ticket($c['A'] ?? null, $area, $reasons);
            // A driver without an account is kept by name (no accounts are created)
            $driverName = GssCellParser::text($c['J'] ?? null);
            $driverId = $this->driver($driverName, $area);
            $program = GssCellParser::text($c['C'] ?? null);
            $passengers = array_values(array_filter(array_map('trim', preg_split('/,|&|\band\b/i', (string) GssCellParser::text($c['D'] ?? null)) ?: [])));
            $recorded = GssCellParser::date($c['B'] ?? null, $this->year);

            $trip = VehicleRequest::create([
                'reference_code' => $this->referenceCode(),
                'requester_name' => Str::limit($passengers[0] ?? $program ?? 'GSS workbook', 120, ''),
                'requester_office' => $program ? Str::limit($program, 150, '') : null,
                'office_id' => $this->office($program),
                'requester_is_passenger' => false,
                'passengers' => $passengers ?: null,
                'location' => $area,
                'vehicle_id' => $vehicle->id,
                'destination' => Str::limit(GssCellParser::text($c['H'] ?? null) ?? '—', 250, ''),
                'purpose' => Str::limit(GssCellParser::text($c['G'] ?? null) ?? '—', 500, ''),
                'travel_date' => $travel['start']->toDateString(),
                'travel_date_end' => $end->toDateString(),
                'status' => 'approved',
                'trip_ticket_number' => $ticket,
                'confirmed_driver_id' => $driverId,
                'driver_name' => $driverId ? null : ($driverName ? Str::limit($driverName, 120, '') : null),
                'approved_at' => ($recorded['start'] ?? $travel['start'])->toDateTimeString(),
            ]);
            $this->backdate($trip, $recorded['start'] ?? $travel['start']);

            $before = GssCellParser::number($c['K'] ?? null);
            $after = GssCellParser::number($c['L'] ?? null);
            if ($before !== null || $after !== null) {
                TripTicketCompletion::create([
                    'vehicle_request_id' => $trip->id,
                    'trip_ticket_number' => $ticket ?? 'IMPORT-'.$trip->id,
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $driverId,
                    'odo_beginning' => $before ?? 0,
                    'odo_ending' => $after ?? 0,
                    'distance_travelled' => $before !== null && $after !== null && $after > $before ? $after - $before : 0,
                    'remarks' => 'Imported from the GSS workbook',
                ]);
            }
            $this->done($sheet, $r, $key, $trip, 'created', $reasons, $c);
        }
    }

    /** "FUEL WEEKLY CONSUMPTION": the fuel side of each trip's Part B, matched by trip ticket number */
    private function fuel(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        $plate = null;
        foreach ($this->rows($ws, 4, 'N') as $r => $c) {
            if ($p = GssCellParser::text($c['D'] ?? null)) {
                $plate = $p;
            }
            $ticket = GssCellParser::text($c['G'] ?? null);
            if (! $ticket) {
                continue;
            }
            $key = 'fuel:'.sha1(json_encode([$ticket, $c['F'] ?? null, $plate]));
            if ($this->seen($sheet, $key)) {
                continue;
            }
            $vehicle = $this->findVehicle($plate);
            $trip = VehicleRequest::query()->where('trip_ticket_number', $ticket)
                ->when($vehicle, fn ($q) => $q->orderByRaw('vehicle_id = ? desc', [$vehicle->id]))
                ->first();
            if (! $trip) {
                $this->skip($sheet, $r, $key, "No trip with ticket {$ticket} (the Trip Ticket Monitoring row may be missing or skipped).", $c);

                continue;
            }
            if (($fuelType = GssCellParser::text($c['E'] ?? null)) && $vehicle && ! $vehicle->fuel_type) {
                $vehicle->update(['fuel_type' => Str::title($fuelType)]);
            }

            $values = array_filter([
                'fuel_balance_in_tank' => GssCellParser::number($c['H'] ?? null),
                'fuel_purchased_trip' => GssCellParser::number($c['J'] ?? null),
                'fuel_ending_balance' => GssCellParser::number($c['K'] ?? null),
                'distance_travelled' => GssCellParser::number($c['L'] ?? null),
            ], fn ($v) => $v !== null);
            $completion = $trip->completion;
            if ($completion) {
                foreach ($values as $field => $value) {
                    if ((float) $completion->{$field} === 0.0) {
                        $completion->{$field} = $value;
                    }
                }
                $completion->save();
                $outcome = 'updated';
            } else {
                $completion = TripTicketCompletion::create($values + [
                    'vehicle_request_id' => $trip->id, 'trip_ticket_number' => $ticket, 'vehicle_id' => $trip->vehicle_id,
                    'driver_id' => $trip->confirmed_driver_id, 'remarks' => 'Imported from the GSS workbook',
                ]);
                $outcome = 'created';
            }
            $this->done($sheet, $r, $key, $completion, $outcome, [], $c);
        }
    }

    /* ───────────────────────── repairs, PMS, PR ───────────────────────── */

    /** "INDEX FOR REPAIR OF VEHICLES": one maintenance record per row, with its PR status as a purchase request */
    private function repairs(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        foreach ($this->rows($ws, 3, 'N') as $r => $c) {
            if (str_starts_with(strtoupper((string) GssCellParser::text($c['B'] ?? null)), 'SUMMARY')) {
                break;
            }
            if (! GssCellParser::text($c['F'] ?? null) && ! GssCellParser::text($c['J'] ?? null)) {
                continue;
            }
            $key = 'repair:'.sha1(json_encode([$c['B'] ?? null, $c['D'] ?? null, $c['F'] ?? null, $c['J'] ?? null]));
            if ($this->seen($sheet, $key)) {
                continue;
            }
            $vehicle = $this->findVehicle(GssCellParser::text($c['F'] ?? null))
                ?? (($prop = GssCellParser::text($c['G'] ?? null)) ? Vehicle::where('property_number', $prop)->first() : null);
            if (! $vehicle) {
                $this->skip($sheet, $r, $key, 'Vehicle "'.($c['F'] ?? '').'" not found.', $c);

                continue;
            }
            $reasons = [];
            $pre = GssCellParser::date($c['D'] ?? null, $this->year);
            $post = GssCellParser::date($c['I'] ?? null, $this->year);
            foreach (['D' => $pre, 'I' => $post] as $col => $parsed) {
                if (GssCellParser::text($c[$col] ?? null) && (! $parsed || $parsed['guessed'])) {
                    $reasons[] = "Date \"{$c[$col]}\" could not be read for sure.";
                }
            }
            $scheduled = $pre['start'] ?? $post['start'] ?? null;
            if (! $scheduled) {
                $scheduled = CarbonImmutable::create($this->year, 1, 1);
                $reasons[] = 'No repair date; set to January 1.';
            }
            $defect = GssCellParser::text($c['J'] ?? null);
            $remarks = GssCellParser::text($c['N'] ?? null);
            $completed = $post !== null || preg_match('/complet|done|repaired/i', (string) $remarks);

            $record = VehicleMaintenance::create([
                'vehicle_id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'vehicle_label' => trim($vehicle->brand.' '.$vehicle->model),
                'type' => $this->repairType($defect),
                'title' => Str::limit($defect ?? 'Repair', 120),
                'status' => $completed ? 'completed' : 'ongoing',
                'priority' => 'normal',
                'scheduled_date' => $scheduled->toDateString(),
                'completed_date' => $completed ? ($post['start'] ?? $scheduled)->toDateString() : null,
                'cost' => GssCellParser::number($c['L'] ?? null),
                'notes' => $remarks,
                'location' => GssCellParser::location($c['B'] ?? null) ?? $vehicle->location,
                'requisitioner_office_id' => $this->office(GssCellParser::text($c['C'] ?? null)),
                'pre_inspection_date' => $pre['start'] ?? null,
                'post_inspection_date' => $post['start'] ?? null,
                'defect_nature' => $defect,
                'parts_replaced' => GssCellParser::text($c['K'] ?? null),
                'created_by_name' => 'GSS workbook import',
            ]);

            if ($statusText = GssCellParser::text($c['M'] ?? null)) {
                $status = $this->prStatus($statusText);
                if (! $status) {
                    $reasons[] = "PR status \"{$statusText}\" not recognised; recorded as PR on process.";
                }
                $this->backdate($record->procurements()->create([
                    'vehicle_id' => $vehicle->id, 'location' => $record->location, 'description' => Str::limit('Repair: '.$record->title, 255, ''),
                    'category' => 'Vehicle parts', 'status' => $status ?? 'PR on process', 'amount' => $record->cost, 'remarks' => $remarks,
                ]), $scheduled);
            }
            $this->backdate($record, $scheduled);
            $this->done($sheet, $r, $key, $record, 'created', $reasons, $c);
        }
    }

    /** "VEHICLE PRE & POST REPAIR CM VALUE": inspection and current market value, added to the matching repair */
    private function cmValues(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        $previous = null;
        foreach ($this->rows($ws, 3, 'O') as $r => $c) {
            $plateText = GssCellParser::text($c['C'] ?? null);
            $complaint = GssCellParser::text($c['H'] ?? null);
            // A line holding only more of the complaint belongs to the row above
            if (! $plateText && ! GssCellParser::text($c['A'] ?? null) && $complaint && $previous) {
                $previous->update(['defect_nature' => Str::limit($previous->defect_nature.'; '.$complaint, 2000, '')]);

                continue;
            }
            if (! $plateText) {
                continue;
            }
            $key = 'cm:'.sha1(json_encode([$c['A'] ?? null, $plateText, $complaint]));
            if ($this->seen($sheet, $key)) {
                continue;
            }
            $vehicle = $this->findVehicle($plateText);
            if (! $vehicle) {
                $this->skip($sheet, $r, $key, "Vehicle \"{$plateText}\" not found.", $c);
                $previous = null;

                continue;
            }
            $reasons = [];
            $requested = GssCellParser::date($c['A'] ?? null, $this->year);
            $pre = GssCellParser::date($c['G'] ?? null, $this->year);
            $post = GssCellParser::date($c['J'] ?? null, $this->year);
            $issued = GssCellParser::date($c['N'] ?? null, $this->year);
            $anchor = $pre['start'] ?? $requested['start'] ?? null;

            $record = $anchor ? VehicleMaintenance::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereBetween('scheduled_date', [$anchor->subDays(45)->toDateString(), $anchor->addDays(45)->toDateString()])
                ->whereNull('current_market_value')
                ->orderBy('scheduled_date')
                ->first() : null;
            $values = [
                'pre_inspection_date' => $pre['start'] ?? null,
                'defect_nature' => $complaint,
                'post_inspection_findings' => GssCellParser::text($c['I'] ?? null),
                'post_inspection_date' => $post['start'] ?? null,
                'current_market_value' => GssCellParser::number($c['M'] ?? null),
                'cmv_issued_at' => $issued['start'] ?? null,
                'cmv_issued_by' => GssCellParser::text($c['O'] ?? null),
                'requisitioner_office_id' => $this->office(GssCellParser::text($c['B'] ?? null)),
            ];
            if ($record) {
                foreach ($values as $field => $value) {
                    if ($value !== null && blank($record->{$field})) {
                        $record->{$field} = $value;
                    }
                }
                $record->save();
                $outcome = 'updated';
            } else {
                $scheduled = $anchor ?? CarbonImmutable::create($this->year, 1, 1);
                if (! $anchor) {
                    $reasons[] = 'No request or inspection date; set to January 1.';
                }
                $record = VehicleMaintenance::create($values + [
                    'vehicle_id' => $vehicle->id, 'plate_number' => $vehicle->plate_number, 'vehicle_label' => trim($vehicle->brand.' '.$vehicle->model),
                    'type' => 'Repair', 'title' => Str::limit($complaint ?? 'Pre/post repair inspection', 120), 'priority' => 'normal',
                    'status' => $post ? 'completed' : 'ongoing', 'scheduled_date' => $scheduled->toDateString(),
                    'completed_date' => $post ? $post['start']->toDateString() : null, 'location' => $vehicle->location,
                    'created_by_name' => 'GSS workbook import',
                ]);
                $this->backdate($record, $requested['start'] ?? $scheduled);
                $outcome = 'created';
            }
            // The sheet also carries the vehicle's acquisition, which belongs on its property record
            $acquired = GssCellParser::date($c['K'] ?? null);
            $vehicle->fill(array_filter([
                'acquisition_date' => $vehicle->acquisition_date ? null : ($acquired['start'] ?? null),
                'acquisition_cost' => $vehicle->acquisition_cost ? null : GssCellParser::number($c['L'] ?? null),
            ]))->save();
            $this->done($sheet, $r, $key, $record, $outcome, $reasons, $c);
            $previous = $record;
        }
    }

    /** "VEHICLE PMS": preventive maintenance requests */
    private function pms(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        foreach ($this->rows($ws, 3, 'M') as $r => $c) {
            if (! ($plateText = GssCellParser::text($c['F'] ?? null))) {
                continue;
            }
            $key = 'pms:'.sha1(json_encode([$c['A'] ?? null, $plateText, $c['B'] ?? null, $c['I'] ?? null]));
            if ($this->seen($sheet, $key)) {
                continue;
            }
            $vehicle = $this->findVehicle($plateText);
            if (! $vehicle) {
                $this->skip($sheet, $r, $key, "Vehicle \"{$plateText}\" not found.", $c);

                continue;
            }
            $reasons = [];
            $requested = GssCellParser::date($c['B'] ?? null, $this->year);
            $read = GssCellParser::date($c['I'] ?? null, $this->year);
            $done = GssCellParser::date($c['J'] ?? null, $this->year);
            $scheduled = $requested['start'] ?? $read['start'] ?? $done['start'] ?? null;
            if (! $scheduled) {
                $scheduled = CarbonImmutable::create($this->year, 1, 1);
                $reasons[] = 'No PMS date; set to January 1.';
            }
            $statusText = strtolower(trim((GssCellParser::text($c['J'] ?? null) ?? '').' '.(GssCellParser::text($c['L'] ?? null) ?? '')));
            $status = $done || str_contains($statusText, 'done') ? 'completed' : (str_contains($statusText, 'process') ? 'ongoing' : 'scheduled');
            $pmNo = GssCellParser::text($c['A'] ?? null);

            $record = VehicleMaintenance::create([
                'vehicle_id' => $vehicle->id, 'plate_number' => $vehicle->plate_number, 'vehicle_label' => trim($vehicle->brand.' '.$vehicle->model),
                'type' => 'Preventive', 'title' => 'PMS'.($pmNo ? ' #'.$pmNo : ''), 'priority' => 'normal', 'status' => $status,
                'scheduled_date' => $scheduled->toDateString(),
                'completed_date' => $status === 'completed' ? ($done['start'] ?? $scheduled)->toDateString() : null,
                'odometer' => ($odo = GssCellParser::number($c['G'] ?? null)) !== null ? (int) $odo : null,
                'cost' => GssCellParser::number($c['K'] ?? null),
                'notes' => collect([GssCellParser::text($c['L'] ?? null), GssCellParser::text($c['M'] ?? null)])->filter()->implode(' · ') ?: null,
                'location' => $vehicle->location, 'pm_request_no' => $pmNo ? Str::limit($pmNo, 30, '') : null,
                'requisitioner_office_id' => $this->office(GssCellParser::text($c['D'] ?? null)),
                'created_by_name' => 'GSS workbook import',
            ]);
            $this->backdate($record, $requested['start'] ?? $scheduled);
            $this->done($sheet, $r, $key, $record, 'created', $reasons, $c);
        }
    }

    /** "PR OF GOODS AND SERVICES": the vehicle rows only (aircon and other goods stay in Excel) */
    private function procurements(Worksheet $ws): void
    {
        $sheet = $ws->getTitle();
        foreach ($this->rows($ws, 3, 'K') as $r => $c) {
            if (! preg_match('/vehicle/i', (string) GssCellParser::text($c['C'] ?? null)) || ! ($description = GssCellParser::text($c['B'] ?? null))) {
                continue;
            }
            $prNumber = GssCellParser::text($c['D'] ?? null);
            $key = 'pr:'.($prNumber ?? sha1(json_encode([$description, $c['F'] ?? null])));
            if ($this->seen($sheet, $key)) {
                continue;
            }
            $reasons = [];
            $texts = array_filter([GssCellParser::text($c['I'] ?? null), GssCellParser::text($c['H'] ?? null), GssCellParser::text($c['G'] ?? null)]);
            $status = null;
            foreach ($texts as $text) {
                if ($status = $this->prStatus($text)) {
                    break;
                }
            }
            if (! $status) {
                $reasons[] = 'Status not clear from the remarks; recorded as PR on process.';
            }
            $prDate = GssCellParser::date($c['E'] ?? null, $this->year);
            $coa = GssCellParser::date($c['K'] ?? null, $this->year);
            $joPo = GssCellParser::text($c['J'] ?? null);
            $joPoDate = $joPo ? GssCellParser::date($joPo, $this->year) : null;
            foreach (['E' => $prDate, 'K' => $coa] as $col => $parsed) {
                if (GssCellParser::text($c[$col] ?? null) && (! $parsed || $parsed['guessed'])) {
                    $reasons[] = "Date \"{$c[$col]}\" could not be read for sure.";
                }
            }
            $plate = $this->plateIn($description);
            $vehicle = $this->findVehicle($plate);

            $pr = Procurement::create([
                'vehicle_id' => $vehicle?->id,
                'location' => $vehicle?->location,
                'description' => Str::limit($description, 255, ''),
                'category' => 'Vehicle',
                'pr_number' => $prNumber ? Str::limit($prNumber, 40, '') : null,
                'pr_date' => $prDate['start'] ?? null,
                'amount' => GssCellParser::number($c['F'] ?? null),
                'status' => $status ?? 'PR on process',
                'jo_po_number' => $joPoDate ? null : ($joPo ? Str::limit($joPo, 60, '') : null),
                'jo_po_date' => $joPoDate['start'] ?? null,
                'coa_date' => $coa['start'] ?? null,
                'remarks' => Str::limit(implode(' | ', array_reverse($texts)), 2000, '') ?: null,
            ]);
            $this->backdate($pr, $prDate['start'] ?? null);
            $this->done($sheet, $r, $key, $pr, 'created', $reasons, $c);
        }
    }

    /* ───────────────────────── helpers ───────────────────────── */

    /** @return \Generator<int, array<string, mixed>> row number => [column letter => raw value] */
    private function rows(Worksheet $ws, int $from, string $lastColumn): \Generator
    {
        $last = $ws->getHighestDataRow();
        for ($r = $from; $r <= $last; $r++) {
            $cells = [];
            foreach (range('A', $lastColumn) as $col) {
                $value = $ws->getCell($col.$r)->getValue();
                if ($value instanceof RichText) {
                    $value = $value->getPlainText();
                }
                $cells[$col] = $value;
            }
            if (array_filter($cells, fn ($v) => $v !== null && trim((string) $v) !== '')) {
                yield $r => $cells;
            }
        }
    }

    private function seen(string $sheet, string $key): bool
    {
        if (ImportRecord::where('source', $this->source)->where('key', $key)->exists()) {
            $this->counts[$sheet]['unchanged']++;

            return true;
        }

        return false;
    }

    private function done(string $sheet, int $row, string $key, Model $model, string $outcome, array $reasons, array $raw): void
    {
        ImportRecord::create([
            'source' => $this->source, 'sheet' => Str::limit($sheet, 60, ''), 'row' => $row, 'key' => $key,
            'record_type' => $model::class, 'record_id' => $model->getKey(), 'outcome' => $outcome,
            'needs_review' => $reasons !== [], 'reason' => $reasons ? implode(' ', $reasons) : null, 'raw' => $this->rawForStorage($raw),
        ]);
        $this->counts[$sheet][$outcome]++;
        if ($reasons) {
            $this->counts[$sheet]['review']++;
            $this->review[] = ['sheet' => $sheet, 'row' => $row, 'outcome' => $outcome === 'created' ? 'Imported, check it' : 'Updated, check it', 'reason' => implode(' ', $reasons), 'record' => class_basename($model).' #'.$model->getKey(), 'raw' => $raw];
        }
    }

    /**
     * The date with the workbook's year when only the year looks mistyped: it falls in another year, but with the
     * workbook's year it sits within two months of the reference date (the date recorded, or the trip's start).
     */
    private function sameYear(CarbonImmutable $date, ?CarbonImmutable $reference, string $label, array &$reasons): CarbonImmutable
    {
        if ((int) $date->format('Y') === $this->year || ! $reference) {
            return $date;
        }
        $day = min((int) $date->format('j'), CarbonImmutable::create($this->year, (int) $date->format('n'), 1)->daysInMonth);
        $fixed = CarbonImmutable::create($this->year, (int) $date->format('n'), $day);
        if (abs($fixed->diffInDays($reference, false)) > 62) {
            return $date;
        }
        $reasons[] = "{$label} {$date->format('M j, Y')} looked like a typing slip; the year was set to {$this->year}.";

        return $fixed;
    }

    /**
     * Dates an imported record by when it happened in the workbook (date recorded, requested, PR date) instead of
     * the day of the import, so charts and monthly counts put it in its own month.
     */
    private function backdate(Model $model, ?CarbonInterface $date): void
    {
        if ($date) {
            $model->forceFill(['created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()])->saveQuietly();
        }
    }

    private function skip(string $sheet, int $row, string $key, string $reason, array $raw): void
    {
        // Skipped rows are not recorded as imported, so they are tried again on the next run
        $this->counts[$sheet]['skipped']++;
        $this->review[] = ['sheet' => $sheet, 'row' => $row, 'outcome' => 'Not imported', 'reason' => $reason, 'record' => '', 'raw' => $raw];
    }

    private function rawForStorage(array $raw): array
    {
        return array_filter(array_map(fn ($v) => is_scalar($v) ? (is_string($v) ? Str::limit($v, 300, '') : $v) : null, $raw), fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Creates the vehicle, or fills only the empty fields of the one already in the system.
     *
     * @return array{0: Vehicle, 1: bool} the vehicle and whether it was created
     */
    private function upsertVehicle(array $values, ?Vehicle $existing = null): array
    {
        foreach (self::VEHICLE_LIMITS as $field => $limit) {
            if (is_string($values[$field] ?? null)) {
                $values[$field] = Str::limit($values[$field], $limit, '');
            }
        }
        $existing ??= $this->vehicleByPlates(['plate' => $values['plate_number'], 'old' => $values['old_plate_number'] ?? null]);
        if ($existing) {
            foreach ($values as $field => $value) {
                if ($value === null || $value === '' || in_array($field, ['plate_number', 'color', 'brand', 'model'], true) && filled($existing->{$field})) {
                    continue;
                }
                if (blank($existing->{$field})) {
                    $existing->{$field} = $value;
                }
            }
            $existing->save();
            $this->rememberPlates($existing);

            return [$existing, false];
        }

        $vehicle = Vehicle::create(array_filter($values, fn ($v) => $v !== null));
        $this->rememberPlates($vehicle);

        return [$vehicle, true];
    }

    private function rememberPlates(Vehicle $vehicle, array $aliases = []): void
    {
        foreach ([$vehicle->plate_number, $vehicle->old_plate_number, $vehicle->conduction_sticker, ...$aliases] as $plate) {
            if (! $plate) {
                continue;
            }
            // "135209-ZML209" is also found as "135209" or "ZML209"
            foreach ([$plate, ...(preg_split('/[\s\/\-]+/', $plate) ?: [])] as $part) {
                $key = GssCellParser::plateKey($part);
                if (strlen($key) >= 4 && preg_match('/\d/', $key)) {
                    $this->plates[$key] ??= $vehicle->id;
                }
            }
        }
    }

    /** @param array{plate: ?string, old: ?string} $plates */
    private function vehicleByPlates(array $plates): ?Vehicle
    {
        foreach ([$plates['plate'] ?? null, $plates['old'] ?? null] as $plate) {
            if ($plate && ($id = $this->plates[GssCellParser::plateKey($plate)] ?? null)) {
                return Vehicle::find($id);
            }
        }

        return null;
    }

    /** The vehicle a plate cell refers to: "SKP-906", "A8X654 (SNE 6227)", "Crosswind- 135209", "135209/ZML7096" */
    private function findVehicle(?string $text): ?Vehicle
    {
        if (! $text) {
            return null;
        }
        if ($vehicle = $this->vehicleByPlates(GssCellParser::plates($text))) {
            return $vehicle;
        }
        if (($plate = $this->plateIn($text)) && ($vehicle = $this->vehicleByPlates(['plate' => $plate, 'old' => null]))) {
            return $vehicle;
        }
        foreach (preg_split('/[\s\/\-()]+/', $text) ?: [] as $token) {
            $key = GssCellParser::plateKey($token);
            if (strlen($key) >= 4 && preg_match('/\d/', $key) && isset($this->plates[$key])) {
                return Vehicle::find($this->plates[$key]);
            }
        }

        return null;
    }

    /** The plate inside a label like "SND 1762 / NISSAN NAVARA" or "SNE - 6100/FOB-272 - Nissan" (S-series first) */
    private function plateIn(string $text): ?string
    {
        preg_match_all('/\b([A-Z]{3})\s*-?\s*(\d{3,4})\b/i', strtoupper($text), $m, PREG_SET_ORDER);
        $found = array_map(fn ($x) => $x[1].' '.$x[2], $m);
        foreach ($found as $plate) {
            if (str_starts_with($plate, 'S')) {
                return $plate;
            }
        }

        return $found[0] ?? (GssCellParser::plates($text)['plate'] ?? null);
    }

    /**
     * Brand, model and details from a description as GSS writes it: "TOYOTA HI-LUX 4 X 2 G DSL M/T",
     * "41. Wagon Mitsubishi Adventure Model 2004 Motor # 4D56APO389", "16. Nissan Navarra 4 x 2 Model: 2017 Chasis# MNT…".
     *
     * @return array{brand: string, model: string, year: ?int, color: ?string, engine: ?string, chassis: ?string}
     */
    private function describe(?string $description): array
    {
        $text = trim((string) preg_replace('/^\d+\.\s*/', '', (string) $description));
        $out = ['brand' => 'Unknown', 'model' => '—', 'year' => null, 'color' => null, 'engine' => null, 'chassis' => null];
        if ($text === '') {
            return $out;
        }
        if (preg_match('/\bmodel\s*:?\s*((?:19|20)\d{2})\b/i', $text, $m) || preg_match('/^((?:19|20)\d{2})\b/', $text, $m) || preg_match('/\b((?:19|20)\d{2})\s+(?:[a-z]+\s+){0,2}(?:isuzu|toyota|nissan|hyundai|mitsubishi)/i', $text, $m)) {
            $out['year'] = (int) $m[1];
        }
        if (preg_match('/(?:engine|motor)\s*(?:no\.?|number|#)?\s*[:#.\-]*\s*([A-Z0-9][A-Z0-9\-]{4,})/i', $text, $m)) {
            $out['engine'] = strtoupper($m[1]);
        }
        if (preg_match('/(?:chass?is|frame)\s*(?:no\.?|number|#)?\s*[:#.\-]*\s*([A-Z0-9][A-Z0-9\-]{4,})/i', $text, $m)) {
            $out['chassis'] = strtoupper($m[1]);
        }
        if (preg_match('/colou?r\s*:\s*([a-z]+(?:\s+(?!engine|motor|chass|frame|plate)[a-z]+)?)/i', $text, $m)) {
            $out['color'] = Str::title($m[1]);
        }

        $brands = ['toyota', 'nissan', 'isuzu', 'mitsubishi', 'misubishi', 'ford', 'hyundai', 'yamaha', 'honda', 'kama', 'foton', 'dongfeng', 'donfeng', 'kia', 'suzuki', 'hino', 'fuso', 'kubota', 'mahindra', 'chevrolet'];
        $words = preg_split('/\s+/', $text) ?: [];
        foreach ($words as $i => $word) {
            $w = strtolower((string) preg_replace('/[^a-z]/i', '', $word));
            if (in_array($w, $brands, true)) {
                $out['brand'] = ['misubishi' => 'Mitsubishi', 'donfeng' => 'Dongfeng'][$w] ?? Str::title($w);
                $rest = implode(' ', array_slice($words, $i + 1));
                break;
            }
        }
        if (! isset($rest)) {
            // No known brand: the first word is the brand ("TOYOTA HI-LUX…" style)
            $out['brand'] = Str::title((string) preg_replace('/[^a-z\-]/i', '', $words[0] ?? 'Unknown')) ?: 'Unknown';
            $rest = implode(' ', array_slice($words, 1));
        }
        $model = trim((string) preg_split('/\b(model|engine|motor|chass?is|frame|colou?r|plate|conduction|fuel type|body type|w\/)\b/i', $rest)[0], ' ,-:.');
        $out['model'] = Str::limit($model !== '' ? $model : '—', 60, '');

        return $out;
    }

    private function condition(?string $text): ?string
    {
        $t = strtolower((string) $text);

        return match (true) {
            $t === '' => null,
            str_contains($t, 'unservice') => 'Unserviceable',
            str_contains($t, 'repair') => 'For repair',
            str_contains($t, 'dispos') => 'For disposal',
            str_contains($t, 'service') => 'Serviceable',
            default => in_array($text, VehicleRegistryController::CONDITIONS, true) ? $text : null,
        };
    }

    private function orCr(?string $text): ?string
    {
        $t = strtolower((string) $text);

        return match (true) {
            $t === '' => null,
            str_contains($t, 'no') => 'No copy',
            str_contains($t, 'xerox') || str_contains($t, 'copy') => 'Copy only',
            in_array($t, ['yes', 'y', 'orig', 'original'], true) => 'Original on file',
            default => null,
        };
    }

    /** @return array{0: ?string, 1: ?CarbonImmutable, 2: ?CarbonImmutable} type, from and to of "TPL/COMPRE INS - JAN 27, 2025 TO JAN 27, 2026" */
    private function insurance(?string $text): array
    {
        if (! $text) {
            return [null, null, null];
        }
        preg_match_all('/([a-z]{3,9})\.?\s+(\d{1,2}),?\s*(\d{4})/i', $text, $m, PREG_SET_ORDER);
        $dates = array_values(array_filter(array_map(fn ($x) => GssCellParser::date("{$x[1]} {$x[2]}, {$x[3]}")['start'] ?? null, $m)));
        $type = trim((string) preg_split('/\s+-\s+|\s+ins\b|\d/i', $text)[0], ' -/,') ?: null;

        return [$type ? Str::limit($type, 60, '') : null, $dates[0] ?? null, $dates[1] ?? ($dates[0] ?? null)];
    }

    private function firstDate(?string $text): ?CarbonImmutable
    {
        if ($text && preg_match('/([a-z]{3,9})\.?\s+(\d{1,2}),?\s*(\d{4})/i', $text, $m)) {
            return GssCellParser::date("{$m[1]} {$m[2]}, {$m[3]}")['start'] ?? null;
        }

        return null;
    }

    /** The trip ticket number as typed; numbers stored as dates are dropped, and a repeat gets a "(2)" so both trips are kept */
    private function ticket(mixed $raw, string $location, array &$reasons): ?string
    {
        if (is_numeric($raw)) {
            $reasons[] = "Ticket number cell holds a date or number ({$raw}); the trip was imported without a ticket number.";

            return null;
        }
        $ticket = GssCellParser::text($raw);
        if (! $ticket) {
            $reasons[] = 'No ticket number.';

            return null;
        }
        $ticket = Str::limit($ticket, 240, '');
        $candidate = $ticket;
        for ($n = 2; VehicleRequest::where('location', $location)->where('trip_ticket_number', $candidate)->exists(); $n++) {
            $candidate = "{$ticket} ({$n})";
        }
        if ($candidate !== $ticket) {
            $reasons[] = "Ticket {$ticket} appears more than once in {$location}; this one was saved as \"{$candidate}\".";
        }

        return $candidate;
    }

    private function referenceCode(): string
    {
        do {
            $code = 'G'.Str::upper(Str::random(6));
        } while (VehicleRequest::where('reference_code', $code)->exists());

        return $code;
    }

    /** The office for a division as the workbook writes it ("AMAD/KADIWA", "DA-PRES Staff", "RTD RES. & REG.") */
    private function office(?string $label, bool $strict = false): ?int
    {
        if (! $label) {
            return null;
        }
        $upper = strtoupper(trim($label));
        if (isset(self::OFFICE_ALIASES[$upper])) {
            return $this->officeCodes[self::OFFICE_ALIASES[$upper]] ?? null;
        }
        if (isset($this->officeCodes[$upper])) {
            return $this->officeCodes[$upper];
        }
        // Strict (fleet card divisions): exact names only, so "BAR-PRES VEHICLE" is not read as PRES
        if ($strict) {
            return null;
        }
        foreach (preg_split('/[^A-Z0-9]+/', $upper) ?: [] as $token) {
            if (strlen($token) < 2) {
                continue;
            }
            if (isset($this->officeCodes[$token])) {
                return $this->officeCodes[$token];
            }
            foreach ($this->officeCodes as $code => $id) {
                if (str_ends_with($code, '-'.$token)) {
                    return $id;
                }
            }
        }

        return null;
    }

    private function repairType(?string $defect): string
    {
        $t = strtolower((string) $defect);

        return match (true) {
            str_contains($t, 'pms') => 'Preventive',
            str_contains($t, 'tire') => 'Tire',
            str_contains($t, 'battery') && ! str_contains($t, 'brake') => 'Battery',
            default => 'Repair',
        };
    }

    private function prStatus(string $text): ?string
    {
        $t = strtolower($text);
        foreach (self::PR_STATUS_WORDS as $word => $status) {
            if (str_contains($t, $word)) {
                return $status;
            }
        }

        return null;
    }

    /** The driver account for a name as written ("R NARIO", "NARIO, RUBEN", "Felix T. Marquez"), if exactly one matches */
    private function driver(?string $name, ?string $location): ?int
    {
        if (! $name || preg_match('/no driver/i', $name)) {
            return null;
        }
        $parts = $this->nameParts($name);
        if ($parts['surname'] === '') {
            return null;
        }
        $matches = array_filter($this->drivers, fn ($d) => $d['surname'] === $parts['surname']
            && ($parts['initial'] === null || $d['initial'] === null || $d['initial'] === $parts['initial'])
            && ($location === null || $d['location'] === null || $d['location'] === $location));

        return count($matches) === 1 ? array_values($matches)[0]['id'] : null;
    }

    /** @return array{surname: string, initial: ?string} */
    private function nameParts(string $name): array
    {
        $name = (string) preg_replace('/\b(engr|dr|atty|mr|ms|mrs|rtd|apco)\.?\s*/i', '', $name);
        if (str_contains($name, ',')) {
            [$surname, $first] = array_map('trim', explode(',', $name, 2));
        } else {
            $words = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: [], fn ($w) => ! preg_match('/^(jr|sr|ii|iii|iv)\.?$/i', $w)));
            $surname = (string) array_pop($words);
            $first = $words[0] ?? '';
        }

        return [
            'surname' => strtolower((string) preg_replace('/[^a-z]/i', '', $surname)),
            'initial' => ($i = strtolower(substr((string) preg_replace('/[^a-z]/i', '', $first), 0, 1))) !== '' ? $i : null,
        ];
    }

    private function writeReview(bool $committed): string
    {
        $dir = storage_path('app/imports');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.DIRECTORY_SEPARATOR.$this->source.'-review'.($committed ? '' : '-dry-run').'.xlsx';

        $book = new Spreadsheet;
        $ws = $book->getActiveSheet();
        $ws->setTitle('Review list');
        $ws->fromArray(['Sheet', 'Row', 'Outcome', 'Reason', 'Record', 'Row values'], null, 'A1');
        $ws->getStyle('A1:F1')->getFont()->setBold(true);
        $r = 2;
        foreach ($this->review as $item) {
            $values = collect($item['raw'])->filter(fn ($v) => $v !== null && trim((string) $v) !== '')
                ->map(fn ($v, $col) => $col.'='.Str::limit((string) $v, 80))->implode(' | ');
            foreach ([$item['sheet'], $item['row'], $item['outcome'], $item['reason'], $item['record'], $values] as $i => $value) {
                $ws->setCellValueExplicit([$i + 1, $r], (string) $value, DataType::TYPE_STRING);
            }
            $r++;
        }
        foreach (['A' => 28, 'B' => 7, 'C' => 18, 'D' => 70, 'E' => 22, 'F' => 120] as $col => $width) {
            $ws->getColumnDimension($col)->setWidth($width);
        }
        $ws->getStyle('D:D')->getAlignment()->setWrapText(true);
        $ws->freezePane('A2');
        $ws->setAutoFilter('A1:F'.max(1, $r - 1));
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return $path;
    }
}
