<?php

namespace Modules\FleetCard\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Destination\Models\Destination;
use Modules\FleetCard\Models\FleetCard;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\FleetCard\Services\FleetCardService;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;

class FleetCardController extends Controller
{
    use AuthorizesFleetAccess;

    public function __construct(
        protected FleetCardService $fleetCardService
    ) {}

    public function index(Request $request)
    {
        $userLoc = $this->fleetLocationScope();

        $query = FleetCard::with(['vehicle', 'creator', 'logs.logger', 'logs.vehicleRequest'])
            ->when($userLoc, fn ($q) => $q->where('location', $userLoc));

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        $cards = $query->latest()->get();

        $vehicles = Vehicle::query()
            ->when($userLoc, fn ($q) => $q->where('location', $userLoc))
            ->orderBy('plate_number')
            ->get();

        $destinations = Destination::query()
            ->when($userLoc, fn ($q) => $q->where('location', $userLoc))
            ->orderBy('name')
            ->get();

        $pendingRequests = VehicleRequest::query()
            ->with(['completion', 'vehicle', 'confirmedDriver'])
            ->when($userLoc, fn ($q) => $q->where('location', $userLoc))
            ->whereIn('status', ['approved', 'completed', 'pending'])
            ->whereNotIn('id', FleetCardLog::whereNotNull('vehicle_request_id')->pluck('vehicle_request_id'))
            ->latest()
            ->take(50)
            ->get();

        // Calculate summary cards metrics
        $totalInitialBudget = $cards->sum('initial_budget');
        $totalCurrentBalance = $cards->sum('current_balance');
        $totalConsumed = max(0, $totalInitialBudget - $totalCurrentBalance);

        $recentLogs = FleetCardLog::with(['fleetCard.vehicle', 'logger', 'vehicleRequest'])
            ->whereHas('fleetCard', function ($q) use ($userLoc, $request) {
                if ($userLoc) {
                    $q->where('location', $userLoc);
                }
                if ($request->filled('location')) {
                    $q->where('location', $request->location);
                }
            })
            ->latest()
            ->take(20)
            ->get();

        return view('fleetcard::index', compact(
            'cards',
            'vehicles',
            'destinations',
            'pendingRequests',
            'recentLogs',
            'totalInitialBudget',
            'totalCurrentBalance',
            'totalConsumed'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $adminLocation = $this->fleetLocationScope();
        if ($adminLocation) {
            // Admins can only register cards for their own location
            $request->merge(['location' => $adminLocation]);
        }

        $validated = $request->validate([
            'card_number' => 'required|string|max:100|unique:fleet_cards,card_number',
            'card_name' => 'required|string|max:150',
            'vehicle_id' => ['nullable', Rule::exists('vehicles', 'id')->where('location', (string) $request->input('location'))],
            'location' => 'required|string|max:100',
            'initial_budget' => 'required|numeric|min:0',
            'rate_peso' => 'required|numeric|min:0.01',
            'rate_km' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $validated['current_balance'] = $validated['initial_budget'];
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        FleetCard::create($validated);

        return redirect()->route('fleet-cards.index')->with('success', 'Fleet Card registered successfully!');
    }

    public function update(Request $request, FleetCard $fleetCard): RedirectResponse
    {
        $adminLocation = $this->fleetLocationScope();
        $this->authorizeFleetLocation($fleetCard->location);
        if ($adminLocation) {
            $request->merge(['location' => $adminLocation]);
        }

        $validated = $request->validate([
            'card_number' => 'required|string|max:100|unique:fleet_cards,card_number,' . $fleetCard->id,
            'card_name' => 'required|string|max:150',
            'vehicle_id' => ['nullable', Rule::exists('vehicles', 'id')->where('location', (string) $request->input('location'))],
            'location' => 'required|string|max:100',
            'initial_budget' => 'required|numeric|min:0',
            'rate_peso' => 'required|numeric|min:0.01',
            'rate_km' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $newInitialBudget = (float) $validated['initial_budget'];
        $oldInitialBudget = (float) $fleetCard->initial_budget;

        // If the initial budget was changed, adjust current balance by the budget difference
        if (abs($newInitialBudget - $oldInitialBudget) > 0.001) {
            $diff = $newInitialBudget - $oldInitialBudget;
            $validated['current_balance'] = max(0, (float) $fleetCard->current_balance + $diff);
        }

        $validated['updated_by'] = auth()->id();
        $fleetCard->update($validated);

        return redirect()->route('fleet-cards.index')->with('success', 'Fleet Card updated successfully!');
    }

    public function logExpense(Request $request, FleetCard $fleetCard): RedirectResponse
    {
        $this->authorizeFleetLocation($fleetCard->location);

        $validated = $request->validate([
            'category' => 'nullable|string|in:fuel,service,parking,toll,other',
            'distance_source' => 'nullable|string|in:odometer,destination_master,manual',
            'start_odometer' => 'nullable|integer|min:0',
            'end_odometer' => 'nullable|integer|gt:start_odometer',
            'distance_km' => 'nullable|numeric|min:0',
            'fuel_price_per_liter' => 'nullable|numeric|min:0',
            'vehicle_request_id' => [
                'nullable',
                Rule::exists('vehicle_requests', 'id')->where('location', $fleetCard->location),
                // A trip ticket can only be charged to a fleet card once
                Rule::unique('fleet_card_logs', 'vehicle_request_id'),
            ],
            'remarks' => 'nullable|string|max:255',
        ], [
            'vehicle_request_id.unique' => 'This trip ticket has already been charged to a fleet card.',
            'vehicle_request_id.exists' => 'The selected trip ticket does not belong to this card\'s location.',
        ]);

        $this->fleetCardService->recordExpense($fleetCard, $validated);

        return redirect()->route('fleet-cards.index')->with('success', 'Trip expense recorded & budget updated!');
    }

    public function recharge(Request $request, FleetCard $fleetCard): RedirectResponse
    {
        $this->authorizeFleetLocation($fleetCard->location);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'remarks' => 'nullable|string|max:255',
        ]);

        $this->fleetCardService->rechargeCard($fleetCard, (float) $validated['amount'], $validated['remarks'] ?? null);

        return redirect()->route('fleet-cards.index')->with('success', 'Fleet Card budget topped up successfully!');
    }

    public function destroyRecharge(Request $request, FleetCard $fleetCard, FleetCardLog $log): JsonResponse|RedirectResponse
    {
        $this->authorizeFleetLocation($fleetCard->location);

        $this->fleetCardService->deleteRecharge($fleetCard, $log);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Top-up entry deleted successfully!']);
        }

        return redirect()->route('fleet-cards.index')->with('success', 'Top-up entry removed and balance updated!');
    }

    public function destroy(FleetCard $fleetCard): RedirectResponse
    {
        $this->authorizeFleetLocation($fleetCard->location);

        $fleetCard->delete();
        return redirect()->route('fleet-cards.index')->with('success', 'Fleet Card deleted successfully!');
    }

    /**
     * Delete an expense log entry and revert card balance
     */
    public function destroyLog(Request $request, FleetCard $fleetCard, FleetCardLog $log): JsonResponse|RedirectResponse
    {
        $this->authorizeFleetLocation($fleetCard->location);
        abort_unless($log->fleet_card_id === $fleetCard->id, 403);
        abort_if($log->distance_source === 'recharge', 400, 'Use destroy-recharge for top-up entries.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($fleetCard, $log) {
            $fleetCard = FleetCard::whereKey($fleetCard->id)->lockForUpdate()->firstOrFail();

            // Refund only this expense. Resetting to the log's balance_before would also wipe out
            // every transaction recorded after it.
            $restoredBalance = (float) $fleetCard->current_balance + (float) $log->consumed_amount;

            // If initial_budget is somehow less than balance_before (data corruption),
            // restore initial_budget as well so the card reflects the correct ceiling
            if ($restoredBalance > (float) $fleetCard->initial_budget) {
                $fleetCard->initial_budget = $restoredBalance;
            }

            $fleetCard->current_balance = $restoredBalance;
            $fleetCard->save();
            $log->delete();
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Expense log deleted and balance restored.']);
        }
        return redirect()->route('fleet-cards.index')->with('success', 'Expense log deleted and balance restored!');
    }

    /**
     * Update remarks on an expense log entry
     */
    public function updateLog(Request $request, FleetCard $fleetCard, FleetCardLog $log): RedirectResponse
    {
        $this->authorizeFleetLocation($fleetCard->location);
        abort_unless($log->fleet_card_id === $fleetCard->id, 403);

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $log->update(['remarks' => $validated['remarks'] ?? $log->remarks]);

        return redirect()->route('fleet-cards.index')->with('success', 'Log remarks updated!');
    }

    public function previewCalc(Request $request, FleetCard $fleetCard): JsonResponse
    {
        $this->authorizeFleetLocation($fleetCard->location);

        $distanceKm = $request->filled('distance_km') ? (float) $request->distance_km : null;
        $startOdo = $request->filled('start_odometer') ? (int) $request->start_odometer : null;
        $endOdo = $request->filled('end_odometer') ? (int) $request->end_odometer : null;

        $calc = $this->fleetCardService->previewCalculation($fleetCard, $distanceKm, $startOdo, $endOdo);

        return response()->json($calc);
    }
}
