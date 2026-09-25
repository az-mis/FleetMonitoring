<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;
use Modules\VehicleRequest\Requests\StoreVehicleRequest;
use Modules\Maintenance\Models\VehicleMaintenance;
use App\Models\User;
use Modules\Admin\Enums\UserRole;
use Modules\ApprovingOfficer\Models\ApprovingOfficer;

class VehicleRequestController
{
    public function index(Request $request): View
    {
        abort_unless(in_array($request->user()?->role?->value, ['admin', 'super_admin'], true), 403);

        $user = $request->user();
        $userLocation = $user?->role === UserRole::ADMIN ? $user->location : null;
        $location = $request->input('location', $userLocation);
        $search = trim($request->string('search')->toString());
        $status = $request->string('status', 'pending')->toString();
        $perPage = (int) $request->input('per_page', 10);

        $baseQuery = VehicleRequest::query()
            ->with(['vehicle', 'confirmedDriver', 'approvedBy'])
            ->when($location, fn ($builder) => $builder->where('location', $location));

        $counts = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'declined' => (clone $baseQuery)->where('status', 'declined')->count(),
        ];

        $query = (clone $baseQuery)->latest();

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'declined'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference_code', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', fn ($vq) => $vq->where('plate_number', 'like', "%{$search}%"));
            });
        }

        return view('vehicle-request::index', [
            'requests' => $query->paginate($perPage)->withQueryString(),
            'drivers' => User::where('role', UserRole::DRIVER)
                ->when($location, fn ($builder) => $builder->where('location', $location))
                ->orderBy('name')
                ->get(),
            'counts' => $counts,
            'selectedLocation' => $location,
            'currentStatus' => $status,
        ]);
    }

    public function updateStatus(Request $request, VehicleRequest $vehicleRequest): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role?->value, ['admin', 'super_admin'], true), 403);
        abort_unless($request->user()?->role !== UserRole::ADMIN || $vehicleRequest->location === $request->user()->location, 403);
        $data = $request->validate([
            'status' => ['required', 'in:approved,declined'],
            'decline_reason' => ['nullable', 'string', 'max:500'],
            'confirmed_driver_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $updates = [
            'status' => $data['status'],
            'decline_reason' => $data['status'] === 'declined' ? ($data['decline_reason'] ?? null) : null,
        ];
        if ($data['status'] === 'approved') {
            abort_unless(!$data['confirmed_driver_id'] || User::whereKey($data['confirmed_driver_id'])->where('role', UserRole::DRIVER)->exists(), 422);

            if (!$vehicleRequest->trip_ticket_number) {
                // Location abbreviation map
                $locationAbbreviations = [
                    'Oriental Mindoro'           => 'OR-MIN',
                    'Occidental Mindoro'          => 'OC-MIN',
                    'Marinduque'                  => 'MAR',
                    'Palawan'                     => 'PAL',
                    'Romblon'                     => 'ROM',
                    'Quezon City Satellite Office' => 'QC',
                ];

                $locationKey = $vehicleRequest->location ?? 'N/A';
                $abbr = $locationAbbreviations[$locationKey] ?? strtoupper(Str::limit(Str::slug($locationKey, ''), 6, ''));

                $year  = now()->format('Y');
                $month = now()->format('m');

                // Count approved requests for the same location in the same year-month
                $count = VehicleRequest::where('status', 'approved')
                    ->where('location', $locationKey)
                    ->whereYear('approved_at', $year)
                    ->whereMonth('approved_at', $month)
                    ->count();

                $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

                $updates['trip_ticket_number'] = "FMS-{$abbr}-{$year}-{$month}-{$sequence}";
            } else {
                $updates['trip_ticket_number'] = $vehicleRequest->trip_ticket_number;
            }

            $updates['approved_by'] = $request->user()->id;
            $updates['approved_at'] = now();
            $updates['confirmed_driver_id'] = $data['confirmed_driver_id'] ?? null;
        }
        $vehicleRequest->update($updates);

        $actionText = $data['status'] === 'approved' ? 'approved.' : 'declined.';
        return back()->with('success', "Request from {$vehicleRequest->requester_name} {$actionText}");
    }

    public function tripTicket(VehicleRequest $vehicleRequest): View
    {
        abort_unless($vehicleRequest->status === 'approved', 404);
        return view('public.trip-ticket', [
            'requestRecord' => $vehicleRequest->load(['vehicle', 'confirmedDriver', 'approvedBy']),
            'officer' => ApprovingOfficer::where('location', $vehicleRequest->location)->first()
                ?? ApprovingOfficer::where('location', 'Regional Office (Default)')->first(),
        ]);
    }

    public function travelHistory(Request $request): View
    {
        abort_unless($request->user()?->role?->value === 'driver', 403);
        $trips = VehicleRequest::with(['vehicle', 'approvedBy'])
            ->where('status', 'approved')
            ->where(function ($query) use ($request): void {
                $query->where('confirmed_driver_id', $request->user()->id)
                    ->orWhere(function ($fallback) use ($request): void {
                        $fallback->whereNull('confirmed_driver_id')
                            ->whereHas('vehicle', fn ($vehicle) => $vehicle->where('assigned_driver_id', $request->user()->id));
                    });
            })
            ->latest('travel_date')
            ->get();
        return view('vehicle-request::travel-history', ['trips' => $trips]);
    }

    public function create(): View
    {
        return view('public.request-vehicle', [
            'vehicles' => Vehicle::query()->with('assignedDriver')->orderBy('plate_number')->get(),
        ]);
    }

    /**
     * Bookings + active maintenance for one vehicle, for the public request
     * form's calendar. Mirrors the React app's combined vehicleAvailability
     * (pending/approved requests) + active vehicleMaintenance feed.
     */
    public function availability(Vehicle $vehicle): JsonResponse
    {
        $bookings = VehicleRequest::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['pending', 'approved'])
            ->get(['status', 'travel_date', 'travel_date_end'])
            ->map(fn (VehicleRequest $r) => [
                'status' => $r->status,
                'start' => $r->travel_date->toDateString(),
                'end' => ($r->travel_date_end ?? $r->travel_date)->toDateString(),
            ]);

        $maintenance = VehicleMaintenance::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->get(['title', 'scheduled_date', 'scheduled_date_end'])
            ->map(fn (VehicleMaintenance $m) => [
                'status' => 'maintenance',
                'title' => $m->title,
                'start' => $m->scheduled_date->toDateString(),
                'end' => ($m->scheduled_date_end ?? $m->scheduled_date)->toDateString(),
            ]);

        return response()->json(['bookings' => $bookings->concat($maintenance)->values()]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        do {
            $code = Str::upper(Str::random(7));
        } while (VehicleRequest::where('reference_code', $code)->exists());

        $data = $request->validated();
        $vehicleRequest = VehicleRequest::create([
            'reference_code' => $code,
            'requester_name' => $data['requesterName'],
            'location' => $data['location'],
            'requester_office' => $data['requesterOffice'] ?? null,
            'requester_contact' => $data['requesterContact'] ?? null,
            'requester_is_passenger' => (bool) ($data['requesterIsPassenger'] ?? false),
            'vehicle_id' => $data['vehicleId'],
            'destination' => $data['destination'],
            'travel_date' => $data['travelDate'],
            'travel_date_end' => $data['travelDateEnd'] ?? null,
            'purpose' => $data['purpose'],
            'passengers' => $data['passengers'] ?? null,
            'previous_trip_ticket_date' => $data['previousTripTicketDate'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('check-status', ['code' => $vehicleRequest->reference_code])
            ->with('request_submitted', $vehicleRequest->reference_code);
    }

    public function status(Request $request): View
    {
        $record = null;
        if ($code = $request->string('code')->trim()->upper()->toString()) {
            $record = VehicleRequest::query()
                ->with('vehicle')
                ->where('reference_code', $code)
                ->first();
        }

        return view('public.check-status', ['requestRecord' => $record]);
    }
}
