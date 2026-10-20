<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Http\Controllers\Concerns\ReturnsToList;
use App\Models\Office;
use App\Models\User;
use App\Services\TripTicketNumberer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\ApprovingOfficer\Models\ApprovingOfficer;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\TripTicketCompletion;
use Modules\VehicleRequest\Models\VehicleRequest;
use Modules\VehicleRequest\Requests\StoreVehicleRequest;
use Modules\VehicleRequest\Support\PersonName;

class VehicleRequestController
{
    use AuthorizesFleetAccess;
    use ReturnsToList;

    public function index(Request $request): View|RedirectResponse
    {
        // Admins are pinned to their own location; only super admins may choose one via ?location=
        $userLocation = $this->fleetLocationScope();
        $location = $userLocation ?? $request->input('location');
        $search = trim($request->string('search')->toString());
        $status = $request->string('status', 'pending')->toString();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $baseQuery = VehicleRequest::query()
            ->with(['vehicle', 'confirmedDriver', 'approvedBy', 'completion'])
            ->withExists('fleetCardLogs')
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

        $requests = $query->paginate($perPage)->withQueryString();
        if ($lastPage = $this->pageOutOfRange($requests)) {
            return $lastPage;
        }

        return view('vehicle-request::index', [
            'requests' => $requests,
            'drivers' => User::where('role', UserRole::DRIVER)->active()
                ->when($location, fn ($builder) => $builder->where('location', $location))
                ->orderBy('name')
                ->get(),
            'counts' => $counts,
            'selectedLocation' => $location,
            'currentStatus' => $status,
            'allVehicles' => Vehicle::query()
                ->with('assignedDriver')
                ->when($userLocation, fn ($builder) => $builder->where('location', $userLocation))
                ->orderBy('plate_number')
                ->get(),
            // Only what the manual-request driver picker needs; full user records carry personal data
            'allDrivers' => User::where('role', UserRole::DRIVER)->active()
                ->when($userLocation, fn ($builder) => $builder->where('location', $userLocation))
                ->orderBy('name')
                ->get(['id', 'name', 'location'])
                ->map(fn (User $driver) => ['id' => $driver->id, 'name' => $driver->name, 'location' => $driver->location])
                ->values(),
        ]);
    }

    /** Next trip ticket number in the location's own format. Must run inside a transaction. */
    private function nextTripTicketNumber(string $location): string
    {
        return app(TripTicketNumberer::class)->next($location);
    }

    /**
     * Server-side double-booking guard used when a trip is approved or created.
     * Returns a user-facing message for the first conflict found, or null.
     */
    private function findSchedulingConflict(?int $ignoreRequestId, int $vehicleId, ?int $driverId, string $start, string $end): ?string
    {
        $overlapping = function ($query) use ($start, $end): void {
            $query->whereDate('travel_date', '<=', $end)
                ->where(function ($q) use ($start): void {
                    $q->whereDate('travel_date_end', '>=', $start)
                        ->orWhere(fn ($q2) => $q2->whereNull('travel_date_end')->whereDate('travel_date', '>=', $start));
                });
        };

        $vehicleTrip = VehicleRequest::where('vehicle_id', $vehicleId)
            ->where('status', 'approved')
            ->when($ignoreRequestId, fn ($q) => $q->whereKeyNot($ignoreRequestId))
            ->where($overlapping)
            ->first();
        if ($vehicleTrip) {
            return "This vehicle is already booked for an approved trip ({$vehicleTrip->trip_ticket_number}) on "
                .$vehicleTrip->travel_date->format('M j, Y').'. Please choose another vehicle or decline this request.';
        }

        $maintenance = VehicleMaintenance::where('vehicle_id', $vehicleId)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->whereDate('scheduled_date', '<=', $end)
            ->where(function ($q) use ($start): void {
                $q->whereDate('scheduled_date_end', '>=', $start)
                    ->orWhere(fn ($q2) => $q2->whereNull('scheduled_date_end')->whereDate('scheduled_date', '>=', $start));
            })
            ->first();
        if ($maintenance) {
            return "This vehicle is scheduled for maintenance ({$maintenance->title}) on the selected date(s).";
        }

        if ($driverId) {
            $driverTrip = VehicleRequest::where('status', 'approved')
                ->when($ignoreRequestId, fn ($q) => $q->whereKeyNot($ignoreRequestId))
                ->where(function ($q) use ($driverId): void {
                    $q->where('confirmed_driver_id', $driverId)
                        ->orWhere(fn ($q2) => $q2->whereNull('confirmed_driver_id')
                            ->whereHas('vehicle', fn ($vq) => $vq->where('assigned_driver_id', $driverId)));
                })
                ->where($overlapping)
                ->first();
            if ($driverTrip) {
                return "The selected driver is already assigned to another approved trip ({$driverTrip->trip_ticket_number}) on "
                    .$driverTrip->travel_date->format('M j, Y').'. Please choose a different driver.';
            }
        }

        return null;
    }

    /**
     * Runs a ticket-issuing transaction, retrying if another approval took the same number first.
     */
    private function withTicketRetry(callable $callback): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    public function updateStatus(Request $request, VehicleRequest $vehicleRequest): RedirectResponse
    {
        $this->authorizeFleetLocation($vehicleRequest->location);
        $data = $request->validate([
            'status' => ['required', 'in:approved,declined'],
            // The requester sees this on Check Status, so declining or cancelling always needs a reason
            'decline_reason' => ['nullable', 'required_if:status,declined', 'string', 'max:500'],
            'confirmed_driver_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::DRIVER->value)],
        ], [
            'decline_reason.required_if' => 'Please give a reason. The requester will see it when checking their request.',
        ]);

        // Status workflow: pending -> approved/declined; an approved trip may change driver or be
        // declined (cancelled) until it has completion/fleet-card records; declined is final.
        if ($vehicleRequest->status === 'declined') {
            return back()->with('error', 'This request has already been declined and can no longer be changed.');
        }

        if ($data['status'] === 'declined') {
            $hasTripRecords = $vehicleRequest->completion()->exists()
                || FleetCardLog::where('vehicle_request_id', $vehicleRequest->id)->exists();
            if ($vehicleRequest->status === 'approved' && $hasTripRecords) {
                return back()->with('error', 'This trip already has a completion or fleet card record, so it can no longer be declined.');
            }
            // A trip can be called off up to its start date; once that day has passed it has taken place
            if ($vehicleRequest->status === 'approved' && $vehicleRequest->travel_date->lt(today())) {
                return back()->with('error', 'This trip started on '.$vehicleRequest->travel_date->format('M j, Y').', so it can no longer be cancelled.');
            }

            $wasApproved = $vehicleRequest->status === 'approved';
            $vehicleRequest->update([
                'status' => 'declined',
                'decline_reason' => $data['decline_reason'],
            ]);

            return back()->with('success', $wasApproved
                ? "Trip {$vehicleRequest->trip_ticket_number} cancelled. The vehicle and driver are free again for those dates."
                : "Request from {$vehicleRequest->requester_name} declined.");
        }

        // Never approve a trip nobody will drive: use the chosen driver, else the vehicle's default driver,
        // and store it so the trip ticket always names the driver.
        $driverId = ($data['confirmed_driver_id'] ?? null) ?: $vehicleRequest->vehicle?->assigned_driver_id;
        if (! $driverId) {
            return back()->with('error', 'Please select a driver for this trip. This vehicle has no default driver assigned.');
        }
        $driver = User::find($driverId);
        if (! $driver || $driver->role !== UserRole::DRIVER || (filled($driver->location) && $driver->location !== $vehicleRequest->location)) {
            return back()->with('error', 'The selected driver is not a driver assigned to '.$vehicleRequest->location.'.');
        }
        if ($problem = $driver->drivingProblem($vehicleRequest->travel_date_end ?? $vehicleRequest->travel_date)) {
            return back()->with('error', $problem);
        }

        $error = $this->withTicketRetry(function () use ($request, $vehicleRequest, $driverId): ?string {
            // Serialise approvals for the same vehicle so two admins cannot double-book it
            Vehicle::whereKey($vehicleRequest->vehicle_id)->lockForUpdate()->first();
            $fresh = VehicleRequest::whereKey($vehicleRequest->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status === 'declined') {
                return 'This request has already been declined and can no longer be changed.';
            }

            $start = $fresh->travel_date->toDateString();
            $end = ($fresh->travel_date_end ?? $fresh->travel_date)->toDateString();
            if ($conflict = $this->findSchedulingConflict($fresh->id, (int) $fresh->vehicle_id, (int) $driverId, $start, $end)) {
                return $conflict;
            }

            $updates = [
                'status' => 'approved',
                'decline_reason' => null,
                'confirmed_driver_id' => $driverId,
            ];
            // Changing the driver of an already approved trip keeps the original approver and approval date
            if ($fresh->status !== 'approved') {
                $updates['approved_by'] = $request->user()->id;
                $updates['approved_at'] = now();
            }
            if (! $fresh->trip_ticket_number) {
                $updates['trip_ticket_number'] = $this->nextTripTicketNumber((string) $fresh->location);
            }
            $fresh->update($updates);

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('success', "Request from {$vehicleRequest->requester_name} approved.");
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $this->authorizeFleetAdmin();

        $isSuperAdmin = $request->user()?->role?->value === 'super_admin';
        $adminLocation = $request->user()->location;

        $data = $request->validate([
            'requester_name' => ['required', 'string', 'max:120'],
            'requester_office' => ['nullable', 'string', 'max:150'],
            'requester_contact' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'requester_is_passenger' => ['nullable', 'boolean'],
            'location' => ['required', 'string', 'max:120'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'confirmed_driver_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::DRIVER->value)],
            'destination' => ['required', 'string', 'max:250'],
            'distance_km' => ['nullable', 'integer', 'min:0'],
            'travel_date' => ['required', 'date'],
            'travel_date_end' => ['required', 'date', 'after_or_equal:travel_date'],
            'purpose' => ['required', 'string', 'max:500'],
            'passengers' => ['nullable', 'array', 'max:20'],
            'passengers.*' => ['nullable', 'string', 'max:120'],
            'override_conflict' => ['nullable', 'boolean'],
        ], [
            'requester_contact.regex' => 'Contact number must start with 09 and be exactly 11 digits (e.g. 09171234567).',
        ]);

        // Location guard for regular admins
        if (! $isSuperAdmin && $data['location'] !== $adminLocation) {
            return back()->withErrors(['location' => 'You can only create requests for your assigned location.'])->withInput();
        }

        // The vehicle must be stationed at the request's location
        if (! Vehicle::whereKey($data['vehicle_id'])->where('location', $data['location'])->exists()) {
            return back()->withErrors(['vehicle_id' => 'The selected vehicle is not stationed at '.$data['location'].'.'])->withInput();
        }

        // Vehicle conflict check (booking overlap with existing approved/pending requests)
        $overrideConflict = $isSuperAdmin && ! empty($data['override_conflict']);
        if (! $overrideConflict) {
            $conflictingRequest = VehicleRequest::where('vehicle_id', $data['vehicle_id'])
                ->whereIn('status', ['pending', 'approved'])
                ->whereDate('travel_date', '<=', $data['travel_date_end'])
                ->where(function ($q) use ($data) {
                    $q->whereDate('travel_date_end', '>=', $data['travel_date'])
                        ->orWhere(function ($q2) use ($data) {
                            $q2->whereNull('travel_date_end')
                                ->whereDate('travel_date', '>=', $data['travel_date']);
                        });
                })->first();

            if ($conflictingRequest) {
                return back()
                    ->withErrors([
                        'vehicle_id' => "This vehicle already has a {$conflictingRequest->status} trip from "
                            .$conflictingRequest->travel_date->format('M j')
                            .($conflictingRequest->travel_date_end ? ' – '.$conflictingRequest->travel_date_end->format('M j, Y') : ', '.$conflictingRequest->travel_date->format('Y'))
                            .'. '.($isSuperAdmin ? 'Check the override box to proceed anyway.' : 'Please choose a different vehicle or dates.'),
                    ])
                    ->withInput();
            }
        }

        // Require a driver
        $driverId = $data['confirmed_driver_id'] ?? null;
        if (! $driverId) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            $driverId = $vehicle?->assigned_driver_id;
        }
        if (! $driverId) {
            return back()->withErrors(['confirmed_driver_id' => 'Please select a driver. This vehicle has no default driver assigned.'])->withInput();
        }
        $driverLocation = User::whereKey($driverId)->value('location');
        if (filled($driverLocation) && $driverLocation !== $data['location']) {
            return back()->withErrors(['confirmed_driver_id' => 'The selected driver is not assigned to '.$data['location'].'.'])->withInput();
        }
        if ($problem = User::find($driverId)?->drivingProblem($data['travel_date_end'])) {
            return back()->withErrors(['confirmed_driver_id' => $problem])->withInput();
        }

        // Generate unique reference code
        do {
            $code = Str::upper(Str::random(7));
        } while (VehicleRequest::where('reference_code', $code)->exists());

        // Normalize passenger names to Title Case
        $passengers = array_filter(array_map(
            fn (?string $p) => PersonName::titleCase((string) $p),
            (array) ($data['passengers'] ?? [])
        ));

        try {
            $result = $this->withTicketRetry(function () use ($data, $driverId, $code, $passengers, $overrideConflict, $request) {
                Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->first();

                // Re-check inside the lock: approved trips, maintenance and the driver's other trips
                if (! $overrideConflict) {
                    $conflict = $this->findSchedulingConflict(null, (int) $data['vehicle_id'], (int) $driverId, $data['travel_date'], $data['travel_date_end']);
                    if ($conflict) {
                        return ['error' => $conflict];
                    }
                }

                $ticketNumber = $this->nextTripTicketNumber($data['location']);

                return ['request' => VehicleRequest::create([
                    'reference_code' => $code,
                    'requester_name' => PersonName::titleCase($data['requester_name']),
                    'location' => $data['location'],
                    'requester_office' => $data['requester_office'] ?? null,
                    'office_id' => Office::idForName($data['requester_office'] ?? null),
                    'requester_contact' => $data['requester_contact'],
                    'requester_is_passenger' => (bool) ($data['requester_is_passenger'] ?? false),
                    'vehicle_id' => $data['vehicle_id'],
                    'confirmed_driver_id' => $driverId,
                    'destination' => $data['destination'],
                    'distance_km' => $data['distance_km'] ?? null,
                    'travel_date' => $data['travel_date'],
                    'travel_date_end' => $data['travel_date_end'],
                    'purpose' => $data['purpose'],
                    'passengers' => array_values($passengers) ?: null,
                    'status' => 'approved',
                    'trip_ticket_number' => $ticketNumber,
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ])];
            });

            if (isset($result['error'])) {
                return back()->withErrors(['vehicle_id' => $result['error']])->withInput();
            }

            $vehicleRequest = $result['request'];

            return redirect()
                ->route('vehicle-requests.index', array_filter(['status' => 'approved', 'location' => request('location')]))
                ->with('manual_request_created', [
                    'requester' => $vehicleRequest->requester_name,
                    'ticket' => $vehicleRequest->trip_ticket_number,
                    'ticket_url' => route('trip-ticket', $vehicleRequest),
                ]);

        } catch (\Throwable $e) {
            // Log the details for administrators; never show database errors to the user
            Log::error('Manual trip ticket creation failed', ['exception' => $e]);

            return redirect()
                ->route('vehicle-requests.index', array_filter(['location' => request('location')]))
                ->with('manual_request_error', 'An unexpected error occurred while creating the trip ticket. Please try again.');
        }
    }

    public function tripTicket(Request $request, VehicleRequest $vehicleRequest): View
    {
        // Trip tickets list staff movements, so the sequential ID alone is not enough: guests need the
        // signed link shown on the Check Status page, and staff can only open tickets they manage or drive.
        $user = $request->user();
        $canView = match (true) {
            $user === null => false,
            $user->role === UserRole::SUPER_ADMIN => true,
            $user->role === UserRole::ADMIN => filled($user->location) && $user->location === $vehicleRequest->location,
            $user->role === UserRole::DRIVER => (int) $vehicleRequest->confirmed_driver_id === (int) $user->id
                || ($vehicleRequest->confirmed_driver_id === null && (int) $vehicleRequest->vehicle?->assigned_driver_id === (int) $user->id),
            default => false,
        };
        abort_unless($canView || $request->hasValidSignature(), 403);
        abort_unless($vehicleRequest->status === 'approved', 404);
        $vehicleRequest->load(['vehicle', 'confirmedDriver', 'approvedBy', 'completion']);

        // Resolve signatories (max 2, the ones ticked on the Approving Officers page):
        // request location -> approver's location -> Regional Office default
        $candidates = array_filter([
            $vehicleRequest->location,
            $vehicleRequest->approvedBy?->location,
            'Regional Office (Default)',
        ]);

        $officer = null;
        $signatories = collect();
        foreach (array_unique($candidates) as $location) {
            $found = ApprovingOfficer::with('signatories')->where('location', $location)->first();
            if ($found && $found->ticketSignatories()->isNotEmpty()) {
                $officer = $found;
                $signatories = $found->ticketSignatories();
                break;
            }
        }

        return view('public.trip-ticket', [
            'requestRecord' => $vehicleRequest,
            'officer' => $officer,
            'signatories' => $signatories,
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

    public function create(): Response
    {
        // no-store keeps the browser from showing a cached (already submitted)
        // copy of the form when the user presses Back after submitting.
        return response()->view('public.request-vehicle', [
            'vehicles' => Vehicle::query()->orderBy('plate_number')->get(),
            // One-time token: each rendered form gets its own, and store() accepts it once.
            'formToken' => (string) Str::uuid(),
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
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

    /**
     * Every vehicle stationed at one location, with its pending/approved
     * requests and active maintenance. Powers the station-wide calendar and
     * the vehicle cards on the public request form.
     */
    public function stationAvailability(Request $request): JsonResponse
    {
        $location = (string) $request->query('location', '');

        $vehicles = Vehicle::query()
            ->where('location', $location)
            ->orderBy('plate_number')
            ->get();

        $requests = VehicleRequest::query()
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->whereIn('status', ['pending', 'approved'])
            ->get(['vehicle_id', 'status', 'travel_date', 'travel_date_end'])
            ->groupBy('vehicle_id');

        $maintenance = VehicleMaintenance::query()
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->get(['vehicle_id', 'title', 'scheduled_date', 'scheduled_date_end'])
            ->groupBy('vehicle_id');

        return response()->json([
            'vehicles' => $vehicles->map(function (Vehicle $v) use ($requests, $maintenance) {
                $bookings = collect($requests->get($v->id, []))->map(fn (VehicleRequest $r) => [
                    'status' => $r->status,
                    'start' => $r->travel_date->toDateString(),
                    'end' => ($r->travel_date_end ?? $r->travel_date)->toDateString(),
                ])->concat(collect($maintenance->get($v->id, []))->map(fn (VehicleMaintenance $m) => [
                    'status' => 'maintenance',
                    'title' => $m->title,
                    'start' => $m->scheduled_date->toDateString(),
                    'end' => ($m->scheduled_date_end ?? $m->scheduled_date)->toDateString(),
                ]));

                return [
                    'id' => $v->id,
                    'plate' => $v->plate_number,
                    'name' => trim(($v->brand ?? '').' '.($v->model ?? '')),
                    'photo' => $v->photo_url,
                    // Public page: say whether a driver is assigned, never who (no name, ID or photo; decision D11)
                    'has_driver' => (bool) $v->assigned_driver_id,
                    'bookings' => $bookings->values(),
                ];
            })->values(),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        // Claim the one-time form token. Cache::add() is atomic, so a second
        // submit of the same form (Back button + resubmit, double click,
        // refresh) fails here instead of creating a duplicate request.
        $token = (string) $request->input('form_token', '');
        if (! Str::isUuid($token)
            || ! Cache::add('vehicle-request-form:'.$token, true, now()->addHours(12))) {
            return redirect()->route('request-vehicle')->withErrors([
                'form' => 'This form was already submitted (or has expired). If you still need another trip, please fill in a fresh form. You can check your earlier request using its reference code.',
            ]);
        }

        do {
            $code = Str::upper(Str::random(7));
        } while (VehicleRequest::where('reference_code', $code)->exists());

        $data = $request->validated();
        $vehicleRequest = VehicleRequest::create([
            'reference_code' => $code,
            'requester_name' => $data['requesterName'],
            'location' => $data['location'],
            'requester_office' => $data['requesterOffice'] ?? null,
            'office_id' => Office::idForName($data['requesterOffice'] ?? null),
            'requester_contact' => $data['requesterContact'] ?? null,
            'requester_is_passenger' => (bool) ($data['requesterIsPassenger'] ?? false),
            'vehicle_id' => $data['vehicleId'],
            'destination' => $data['destination'],
            'distance_km' => $data['distance_km'] ?? null,
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

    public function lookupTripByTicketNumber(Request $request): JsonResponse
    {
        $this->authorizeFleetAdmin();

        $queryStr = trim($request->string('ticket_number')->toString());
        $user = $request->user();
        $isSuperAdmin = $user->role === UserRole::SUPER_ADMIN;

        // If 'suggest' parameter is passed or general autocomplete
        if ($request->boolean('suggest') || $request->has('q')) {
            $search = trim($request->string('q', $queryStr)->toString());
            $results = VehicleRequest::query()
                ->with(['vehicle', 'confirmedDriver', 'completion'])
                ->where('status', 'approved')
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('trip_ticket_number', 'like', "%{$search}%")
                            ->orWhere('reference_code', 'like', "%{$search}%")
                            ->orWhere('requester_name', 'like', "%{$search}%")
                            ->orWhereHas('vehicle', fn ($vq) => $vq->where('plate_number', 'like', "%{$search}%"));
                    });
                })
                ->when(! $isSuperAdmin, fn ($q) => $q->where('location', $user->location))
                ->latest()
                ->limit(8)
                ->get()
                ->map(function ($trip) {
                    $v = $trip->vehicle;
                    $d = $trip->confirmedDriver ?: $v?->assignedDriver;
                    $isFuture = $trip->travel_date->isFuture() && ! $trip->travel_date->isToday();

                    return [
                        'id' => $trip->id,
                        'trip_ticket_number' => $trip->trip_ticket_number,
                        'reference_code' => $trip->reference_code,
                        'requester_name' => $trip->requester_name,
                        'location' => $trip->location,
                        'destination' => $trip->destination,
                        'travel_date' => $trip->travel_date->format('M d, Y').($trip->travel_date_end ? (' – '.$trip->travel_date_end->format('M d, Y')) : ''),
                        'travel_date_raw' => $trip->travel_date->format('Y-m-d'),
                        'is_future' => $isFuture,
                        'purpose' => $trip->purpose,
                        'vehicle_id' => $v?->id,
                        'vehicle_plate' => $v?->plate_number,
                        'vehicle_name' => $v ? "{$v->brand} {$v->model}" : 'Unassigned',
                        'vehicle_odometer' => (float) ($v?->odometer ?? 0),
                        'driver_id' => $d?->id,
                        'driver_name' => $d?->name ?? 'Unassigned',
                        'has_completion' => (bool) $trip->completion,
                        'completion' => $trip->completion,
                    ];
                });

            return response()->json(['suggestions' => $results]);
        }

        if (! $queryStr) {
            return response()->json(['error' => 'Please provide a Trip Ticket Number.'], 422);
        }

        $approvedTrips = fn () => VehicleRequest::query()
            ->with(['vehicle', 'confirmedDriver', 'completion'])
            ->where('status', 'approved')
            ->when(! $isSuperAdmin, fn ($q) => $q->where('location', $user->location));

        // Exact ticket/reference matches win; a partial match is only used when it is unambiguous
        // Ticket numbers are unique per area, so the same number may exist in two areas
        $exactMatches = $approvedTrips()
            ->where(fn ($q) => $q->where('trip_ticket_number', $queryStr)->orWhere('reference_code', $queryStr))
            ->limit(6)
            ->get();
        if ($exactMatches->count() > 1) {
            return response()->json(['error' => "Trip Ticket '{$queryStr}' exists in more than one area ({$exactMatches->pluck('location')->unique()->implode(', ')}). Please pick it from the suggestions or enter its reference code."], 409);
        }
        $trip = $exactMatches->first();
        if (! $trip) {
            $partialMatches = $approvedTrips()
                ->where('trip_ticket_number', 'like', '%'.addcslashes($queryStr, '\\%_').'%')
                ->limit(2)
                ->get();
            if ($partialMatches->count() > 1) {
                return response()->json(['error' => "More than one trip matches '{$queryStr}'. Please enter the full Trip Ticket Number."], 409);
            }
            $trip = $partialMatches->first();
        }

        if (! $trip) {
            return response()->json(['error' => "No approved trip found matching '{$queryStr}'.".(! $isSuperAdmin ? " (Filtered by your location: {$user->location})" : '')], 404);
        }

        $vehicle = $trip->vehicle;
        $driver = $trip->confirmedDriver ?: $vehicle?->assignedDriver;
        $isFuture = $trip->travel_date->isFuture() && ! $trip->travel_date->isToday();

        return response()->json([
            'trip' => [
                'id' => $trip->id,
                'trip_ticket_number' => $trip->trip_ticket_number,
                'reference_code' => $trip->reference_code,
                'requester_name' => $trip->requester_name,
                'location' => $trip->location,
                'destination' => $trip->destination,
                'travel_date' => $trip->travel_date->format('M d, Y').($trip->travel_date_end ? (' – '.$trip->travel_date_end->format('M d, Y')) : ''),
                'travel_date_raw' => $trip->travel_date->format('Y-m-d'),
                'is_future' => $isFuture,
                'purpose' => $trip->purpose,
                'vehicle_id' => $vehicle?->id,
                'vehicle_plate' => $vehicle?->plate_number,
                'vehicle_name' => $vehicle ? "{$vehicle->brand} {$vehicle->model}" : 'Unassigned',
                'vehicle_odometer' => (float) ($vehicle?->odometer ?? 0),
                'driver_id' => $driver?->id,
                'driver_name' => $driver?->name ?? 'Unassigned',
                'has_completion' => (bool) $trip->completion,
                'completion' => $trip->completion,
            ],
        ]);
    }

    public function storeTripCompletion(Request $request, VehicleRequest $vehicleRequest): JsonResponse|RedirectResponse
    {
        $this->authorizeFleetLocation($vehicleRequest->location);

        if ($vehicleRequest->status !== 'approved') {
            $msg = 'Only approved trips can be logged as completed.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        if ($vehicleRequest->travel_date->isFuture() && ! $vehicleRequest->travel_date->isToday()) {
            $msg = "Cannot log completion for an upcoming trip scheduled on {$vehicleRequest->travel_date->format('M d, Y')}. The trip has not taken place yet.";
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $data = $request->validate([
            'departure_garage_time' => ['nullable', 'string', 'max:50'],
            'arrival_destination_time' => ['nullable', 'string', 'max:50'],
            'departure_destination_time' => ['nullable', 'string', 'max:50'],
            'arrival_garage_time' => ['nullable', 'string', 'max:50'],

            'odo_beginning' => ['required', 'numeric', 'min:0'],
            'odo_ending' => ['required', 'numeric', 'gte:odo_beginning'],
            'distance_travelled' => ['nullable', 'numeric', 'min:0'],

            'fuel_divisor' => ['nullable', 'numeric', 'gt:0'],
            'fuel_used_liters' => ['nullable', 'numeric', 'min:0'],

            'fuel_balance_in_tank' => ['nullable', 'numeric', 'min:0'],
            'fuel_issued_office' => ['nullable', 'numeric', 'min:0'],
            'fuel_purchased_trip' => ['nullable', 'numeric', 'min:0'],
            'fuel_total_liters' => ['nullable', 'numeric', 'min:0'],
            'fuel_ending_balance' => ['nullable', 'numeric'],

            'gear_oil_issued' => ['nullable', 'numeric', 'min:0'],
            'lub_oil_issued' => ['nullable', 'numeric', 'min:0'],
            'grease_issued' => ['nullable', 'numeric', 'min:0'],

            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'odo_ending.gte' => 'The odometer at the end of the trip cannot be lower than at the beginning (:value km).',
        ], [
            // Names as they appear on the Part B form, so messages make sense to the person filling it in
            'odo_beginning' => 'odometer at beginning of trip',
            'odo_ending' => 'odometer at end of trip',
            'fuel_divisor' => 'fuel divisor',
            'fuel_balance_in_tank' => 'balance in tank',
            'fuel_issued_office' => 'fuel issued from stock',
            'fuel_purchased_trip' => 'fuel purchased during trip',
            'gear_oil_issued' => 'gear oil',
            'lub_oil_issued' => 'lub oil',
            'grease_issued' => 'grease',
        ]);

        $odoBeg = (int) round((float) $data['odo_beginning']);
        $odoEnd = (int) round((float) $data['odo_ending']);
        $distance = max(0, $odoEnd - $odoBeg);

        $divisor = ! empty($data['fuel_divisor']) ? (int) round((float) $data['fuel_divisor']) : null;
        $fuelUsed = ($divisor && $divisor > 0) ? round($distance / $divisor, 2) : (float) ($data['fuel_used_liters'] ?? 0);

        $balTank = (float) ($data['fuel_balance_in_tank'] ?? 0);
        $issuedOffice = (float) ($data['fuel_issued_office'] ?? 0);
        $purchasedTrip = (float) ($data['fuel_purchased_trip'] ?? 0);
        $totalLiters = $balTank + $issuedOffice + $purchasedTrip;
        $endingBal = round($totalLiters - $fuelUsed, 2);

        $completion = DB::transaction(function () use ($vehicleRequest, $data, $request, $odoBeg, $odoEnd, $distance, $divisor, $fuelUsed, $balTank, $issuedOffice, $purchasedTrip, $totalLiters, $endingBal) {
            // Lock the request so a double submit updates the same completion instead of creating two
            VehicleRequest::whereKey($vehicleRequest->id)->lockForUpdate()->first();

            $completion = TripTicketCompletion::updateOrCreate(
                ['vehicle_request_id' => $vehicleRequest->id],
                [
                    'trip_ticket_number' => $vehicleRequest->trip_ticket_number ?? ('TRIP-'.$vehicleRequest->id),
                    'vehicle_id' => $vehicleRequest->vehicle_id,
                    'driver_id' => $vehicleRequest->confirmed_driver_id ?: $vehicleRequest->vehicle?->assigned_driver_id,
                    'departure_garage_time' => $data['departure_garage_time'] ?? null,
                    'arrival_destination_time' => $data['arrival_destination_time'] ?? null,
                    'departure_destination_time' => $data['departure_destination_time'] ?? null,
                    'arrival_garage_time' => $data['arrival_garage_time'] ?? null,
                    'odo_beginning' => $odoBeg,
                    'odo_ending' => $odoEnd,
                    'distance_travelled' => $distance,
                    'fuel_divisor' => $divisor,
                    'fuel_used_liters' => $fuelUsed,
                    'fuel_balance_in_tank' => $balTank,
                    'fuel_issued_office' => $issuedOffice,
                    'fuel_purchased_trip' => $purchasedTrip,
                    'fuel_total_liters' => $totalLiters,
                    'fuel_ending_balance' => $endingBal,
                    'gear_oil_issued' => $data['gear_oil_issued'] ?? null,
                    'lub_oil_issued' => $data['lub_oil_issued'] ?? null,
                    'grease_issued' => $data['grease_issued'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'completed_by' => $request->user()->id,
                ]
            );

            // Auto-update the vehicle's current odometer if the trip end odometer is higher
            $vehicle = Vehicle::whereKey($vehicleRequest->vehicle_id)->lockForUpdate()->first();
            if ($vehicle && $odoEnd > $vehicle->odometer) {
                $vehicle->update([
                    'odometer' => (int) round($odoEnd),
                ]);
            }

            return $completion;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Trip Ticket {$vehicleRequest->trip_ticket_number} completion successfully recorded!",
                'completion' => $completion,
            ]);
        }

        return back()->with('success', "Trip Ticket {$vehicleRequest->trip_ticket_number} completion logged successfully!");
    }
}
