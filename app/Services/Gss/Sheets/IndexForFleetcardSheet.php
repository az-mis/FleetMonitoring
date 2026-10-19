<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use Modules\FleetCard\Models\FleetCard;
use Modules\FleetCard\Services\FleetCardSpendService;

/** "INDEX FOR FLEETCARD": spend per division and month, a block per provider, with the excluded vehicles below the total */
final class IndexForFleetcardSheet extends GssSheet
{
    public const HEADERS = ['NO.', 'DIVISION/PROGRAM', 'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPT', 'OCT', 'NOVEMBER', 'DECEMBER'];

    public function __construct(private readonly FleetCardSpendService $spend) {}

    public function key(): string
    {
        return 'index-for-fleetcard';
    }

    public function title(): string
    {
        return 'INDEX FOR FLEETCARD';
    }

    public function heading(GssScope $scope): string
    {
        return 'INDEX FOR FLEET CARD '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return [self::HEADERS];
    }

    public function moneyColumns(): array
    {
        return range(2, 13);
    }

    public function rows(GssScope $scope): iterable
    {
        foreach (FleetCard::PROVIDERS as $provider) {
            $grid = $this->spend->grid($scope->year, $scope->location, $provider);
            if ($grid['rows']->isEmpty()) {
                continue;
            }

            yield ['group' => strtoupper($provider).' FLEET CARD'];
            $amounts = fn (array $row) => array_map(fn ($cell) => $cell ? round((float) $cell['amount'], 2) : null, array_values($row['months']));

            $no = 0;
            foreach ($grid['rows']->where('excluded', false) as $row) {
                yield ['cells' => [++$no, $row['label'], ...$amounts($row)]];
            }
            $total = array_map(fn ($amount) => $amount > 0 ? round($amount, 2) : null, array_values($grid['totals']));
            yield ['cells' => ['TOTAL:', '', ...$total], 'bold' => true];

            $excluded = $grid['rows']->where('excluded', true);
            if ($excluded->isNotEmpty()) {
                yield ['group' => 'EXCLUDED VEHICLE'];
                $overall = $grid['totals'];
                foreach ($excluded as $row) {
                    yield ['cells' => [++$no, $row['label'], ...$amounts($row)]];
                    foreach ($row['months'] as $month => $cell) {
                        $overall[$month] += (float) ($cell['amount'] ?? 0);
                    }
                }
                yield ['cells' => ['OVER ALL TOTAL:', '', ...array_map(fn ($amount) => $amount > 0 ? round($amount, 2) : null, array_values($overall))], 'bold' => true];
            }
        }
    }
}
