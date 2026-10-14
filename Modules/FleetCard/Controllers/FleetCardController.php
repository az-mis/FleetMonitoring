<?php

namespace Modules\FleetCard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Destination\Models\Destination;
use Modules\FleetCard\Models\FleetCard;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\FleetCard\Services\FleetCardService;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;

class FleetCardController extends Controller
{
    public function __construct(
        protected FleetCardService $fleetCardService
    ) {}

    public function index(Request $request)
    {
        $userLoc = auth()->user()?->role === \Modules\Admin\Enums\UserRole::ADMIN ? auth()->user()->location : null;

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
        $validated = $request->validate([
            'card_number' => 'required|string|max:100|unique:fleet_cards,card_number',
            'card_name' => 'required|string|max:150',
            'vehicle_id' => 'nullable|exists:vehicles,id',
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
        $validated = $request->validate([
            'card_number' => 'required|string|max:100|unique:fleet_cards,card_number,' . $fleetCard->id,
            'card_name' => 'required|string|max:150',
            'vehicle_id' => 'nullable|exists:vehicles,id',
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
        $validated = $request->validate([
            'category' => 'nullable|string|in:fuel,service,parking,toll,other',
            'distance_source' => 'nullable|string|in:odometer,destination_master,manual',
            'start_odometer' => 'nullable|integer|min:0',
            'end_odometer' => 'nullable|integer|gt:start_odometer',
            'distance_km' => 'nullable|numeric|min:0',
            'vehicle_request_id' => 'nullable|exists:vehicle_requests,id',
            'remarks' => 'nullable|string|max:255',
        ]);

        $this->fleetCardService->recordExpense($fleetCard, $validated);

        return redirect()->route('fleet-cards.index')->with('success', 'Trip expense recorded & budget updated!');
    }

    public function recharge(Request $request, FleetCard $fleetCard): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'remarks' => 'nullable|string|max:255',
        ]);

        $this->fleetCardService->rechargeCard($fleetCard, (float) $validated['amount'], $validated['remarks'] ?? null);

        return redirect()->route('fleet-cards.index')->with('success', 'Fleet Card budget topped up successfully!');
    }

    public function destroyRecharge(Request $request, FleetCard $fleetCard, FleetCardLog $log): JsonResponse|RedirectResponse
    {
        $this->fleetCardService->deleteRecharge($fleetCard, $log);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Top-up entry deleted successfully!']);
        }

        return redirect()->route('fleet-cards.index')->with('success', 'Top-up entry removed and balance updated!');
    }

    public function destroy(FleetCard $fleetCard): RedirectResponse
    {
        $fleetCard->delete();
        return redirect()->route('fleet-cards.index')->with('success', 'Fleet Card deleted successfully!');
    }

    public function previewCalc(Request $request, FleetCard $fleetCard): JsonResponse
    {
        $distanceKm = $request->filled('distance_km') ? (float) $request->distance_km : null;
        $startOdo = $request->filled('start_odometer') ? (int) $request->start_odometer : null;
        $endOdo = $request->filled('end_odometer') ? (int) $request->end_odometer : null;

        $calc = $this->fleetCardService->previewCalculation($fleetCard, $distanceKm, $startOdo, $endOdo);

        return response()->json($calc);
    }
}
