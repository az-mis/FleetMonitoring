<?php

declare(strict_types=1);

namespace Modules\FleetCard\Services;

use App\Models\Office;
use Illuminate\Support\Collection;
use Modules\FleetCard\Models\FleetCard;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\FleetCard\Models\FleetCardMonthlySpend;

/**
 * Fleet card spend per division and month, laid out like the GSS "Index for Fleetcard" sheet.
 *
 * A month shows the amount GSS recorded from the provider's statement (or imported from the workbook)
 * when there is one; otherwise it is the sum of the expenses logged on that division's cards.
 */
class FleetCardSpendService
{
    public const NO_DIVISION = 'No division set';

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array<int, float>, grand: float}
     */
    public function grid(int $year, ?string $location = null, ?string $provider = null): array
    {
        $rows = [];
        $row = function (string $provider, ?int $officeId, string $label) use (&$rows): string {
            $key = $provider.'|'.($officeId ?: $label);
            $rows[$key] ??= [
                'provider' => $provider, 'office_id' => $officeId, 'label' => $label, 'excluded' => false,
                'months' => array_fill(1, 12, null), 'allowance' => 0.0,
            ];

            return $key;
        };

        $offices = Office::query()->pluck('name', 'id');
        $cards = FleetCard::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->get(['id', 'provider', 'office_id', 'monthly_allowance']);
        $cardRow = [];
        foreach ($cards as $card) {
            $key = $row($card->provider ?: 'Unspecified', $card->office_id, $offices[$card->office_id] ?? self::NO_DIVISION);
            $cardRow[$card->id] = $key;
            $rows[$key]['allowance'] += (float) $card->monthly_allowance;
        }

        // Expenses logged on the cards (top-ups are stored as negative amounts and are not spend)
        FleetCardLog::query()
            ->whereIn('fleet_card_id', array_keys($cardRow))
            ->where('consumed_amount', '>', 0)
            ->whereBetween('created_at', [now()->setDate($year, 1, 1)->startOfDay(), now()->setDate($year, 12, 31)->endOfDay()])
            ->get(['fleet_card_id', 'consumed_amount', 'created_at'])
            ->each(function (FleetCardLog $log) use (&$rows, $cardRow): void {
                $cell = &$rows[$cardRow[$log->fleet_card_id]]['months'][(int) $log->created_at->format('n')];
                $cell = ['amount' => (float) ($cell['amount'] ?? 0) + (float) $log->consumed_amount, 'source' => 'logs'];
            });

        // Statement amounts replace the logged sum for that month
        $this->statements($year, $location, $provider)->each(function (FleetCardMonthlySpend $spend) use (&$rows, $row): void {
            $key = $row($spend->provider, $spend->office_id, $spend->office?->name ?? $spend->division_label);
            $rows[$key]['months'][$spend->month] = ['amount' => (float) $spend->amount, 'source' => $spend->source, 'id' => $spend->id];
            $rows[$key]['excluded'] = $rows[$key]['excluded'] || $spend->excluded_from_total;
        });

        $rows = collect($rows)
            ->map(function (array $r): array {
                $r['total'] = array_sum(array_map(fn ($cell) => $cell['amount'] ?? 0, $r['months']));
                $r['allowance'] = $r['allowance'] > 0 ? $r['allowance'] : null;

                return $r;
            })
            ->filter(fn (array $r) => $r['total'] > 0 || $r['allowance'] !== null)
            ->sortBy([['excluded', 'asc'], ['provider', 'asc'], ['label', 'asc']])
            ->values();

        $totals = [];
        foreach (range(1, 12) as $month) {
            $totals[$month] = (float) $rows->where('excluded', false)->sum(fn ($r) => $r['months'][$month]['amount'] ?? 0);
        }

        return ['rows' => $rows, 'totals' => $totals, 'grand' => array_sum($totals)];
    }

    /**
     * Divisions whose spend this month is over the monthly allowance set on their cards.
     *
     * @return Collection<int, array{label: string, provider: string, spent: float, allowance: float}>
     */
    public function overAllowance(?string $location = null): Collection
    {
        $month = (int) now()->format('n');

        return $this->grid((int) now()->format('Y'), $location)['rows']
            ->filter(fn (array $r) => $r['allowance'] !== null && ($r['months'][$month]['amount'] ?? 0) > $r['allowance'])
            ->map(fn (array $r) => ['label' => $r['label'], 'provider' => $r['provider'], 'spent' => $r['months'][$month]['amount'], 'allowance' => $r['allowance']])
            ->values();
    }

    /** @return Collection<int, FleetCardMonthlySpend> */
    public function statements(int $year, ?string $location = null, ?string $provider = null): Collection
    {
        return FleetCardMonthlySpend::query()
            ->with('office')
            ->where('year', $year)
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->orderBy('month')->orderBy('division_label')
            ->get();
    }
}
