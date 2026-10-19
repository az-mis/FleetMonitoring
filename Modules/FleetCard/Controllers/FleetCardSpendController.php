<?php

declare(strict_types=1);

namespace Modules\FleetCard\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Support\FleetLocations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\FleetCard\Models\FleetCard;
use Modules\FleetCard\Models\FleetCardMonthlySpend;
use Modules\FleetCard\Services\FleetCardSpendService;

/** Monthly fleet card spend per division: the GSS "Index for Fleetcard" sheet */
class FleetCardSpendController extends Controller
{
    use AuthorizesFleetAccess;

    public function __construct(private readonly FleetCardSpendService $spend) {}

    public function index(Request $request): View
    {
        $this->authorizeFleetAdmin();
        $location = $this->fleetLocationScope() ?? $request->input('location');
        $year = (int) $request->input('year', now()->format('Y'));
        $year = $year >= 2000 && $year <= 2100 ? $year : (int) now()->format('Y');
        $provider = in_array($request->input('provider'), FleetCard::PROVIDERS, true) ? $request->input('provider') : null;

        return view('fleetcard::monthly-spend', [
            'year' => $year,
            'provider' => $provider,
            'location' => $location,
            'canPickLocation' => $this->fleetLocationScope() === null,
            'grid' => $this->spend->grid($year, $location, $provider),
            'statements' => $this->spend->statements($year, $location, $provider),
            'offices' => Office::query()->active()->get(['id', 'name']),
            'providers' => FleetCard::PROVIDERS,
            'locations' => FleetLocations::ALL,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeFleetAdmin();
        if ($adminLocation = $this->fleetLocationScope()) {
            $request->merge(['location' => $adminLocation]);
        }

        $data = $request->validate([
            'provider' => ['required', Rule::in(FleetCard::PROVIDERS)],
            'office_id' => ['required', 'integer', Rule::exists('offices', 'id')],
            'location' => ['nullable', Rule::in(FleetLocations::ALL)],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'excluded_from_total' => ['nullable', 'boolean'],
        ]);

        $label = Office::whereKey($data['office_id'])->value('name');
        $existing = FleetCardMonthlySpend::query()
            ->where(['provider' => $data['provider'], 'division_label' => $label, 'year' => $data['year'], 'month' => $data['month']])
            ->first();
        // An admin may only replace an amount recorded for their own area (not a region-wide one)
        if ($existing) {
            $this->authorizeFleetLocation($existing->location);
        }

        FleetCardMonthlySpend::updateOrCreate(
            ['provider' => $data['provider'], 'division_label' => $label, 'year' => $data['year'], 'month' => $data['month']],
            [
                'office_id' => $data['office_id'],
                'location' => $data['location'] ?? null,
                'amount' => $data['amount'],
                'source' => 'statement',
                'excluded_from_total' => (bool) ($data['excluded_from_total'] ?? false),
                'recorded_by' => $request->user()->id,
            ],
        );

        $monthName = now()->setDate((int) $data['year'], (int) $data['month'], 1)->format('F Y');

        return redirect()->route('fleet-cards.monthly-spend', ['year' => $data['year']])
            ->with('success', "{$data['provider']} spend for {$label}, {$monthName} saved.");
    }

    public function destroy(FleetCardMonthlySpend $spend): RedirectResponse
    {
        $this->authorizeFleetLocation($spend->location);

        $spend->delete();

        return redirect()->route('fleet-cards.monthly-spend', ['year' => $spend->year])
            ->with('success', 'Statement amount removed. That month now shows the expenses logged on the cards.');
    }
}
