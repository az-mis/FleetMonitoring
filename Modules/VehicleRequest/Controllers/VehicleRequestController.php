<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
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
            'allVehicles' => Vehicle::query()->with('assignedDriver')->orderBy('plate_number')->get(),
            'allDrivers' => User::where('role', UserRole::DRIVER)->orderBy('name')->get(),
        ]);
    }

    /**
     * Last Palawan trip number already used before this system took over, keyed by
     * 2-digit year. The next approval continues from this number + 1.
     * e.g. '26' => 867 means the first number issued in 2026 is 26-MM-868.
     */
    private const PALAWAN_SEQUENCE_START = [
        '26' => 867,
    ];

    /**
     * Next Palawan trip sequence for the year: the highest sequence already stored
     * (so manually edited numbers are respected), or the legacy starting point,
     * whichever is higher, plus one.
     */
    private function nextPalawanSequence(string $yearSuffix): int
    {
        $highest = self::PALAWAN_SEQUENCE_START[$yearSuffix] ?? 0;

        VehicleRequest::where('location', 'Palawan')
            ->where('trip_ticket_number', 'like', $yearSuffix.'-%')
            ->pluck('trip_ticket_number')
            ->each(function (string $number) use (&$highest, $yearSuffix): void {
                if (preg_match('/^'.$yearSuffix.'-\d{2}-(\d+)$/', $number, $m)) {
                    $highest = max($highest, (int) $m[1]);
                }
            });

        return $highest + 1;
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

            // Never approve a trip nobody will drive: if the vehicle has no default driver,
            // the reviewer has to pick one for this trip.
            if (empty($data['confirmed_driver_id']) && ! $vehicleRequest->vehicle?->assigned_driver_id) {
                return back()->with('error', 'Please select a driver for this trip. This vehicle has no default driver assigned.');
            }

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

                $year  = now()->format('Y');
                $month = now()->format('m');

                if ($locationKey === 'Palawan') {
                    // Palawan uses its own numbering scheme, per client request:
                    // {2-digit year}-{2-digit month}-{3-digit sequence}, e.g. 26-09-868.
                    // The sequence runs for the whole calendar year (not per month) and
                    // resets to 001 every January. It continues from the highest number
                    // already issued this year, and never starts below the last number
                    // Palawan used before this system (see PALAWAN_SEQUENCE_START).
                    $yearSuffix = now()->format('y');
                    $sequence = str_pad((string) $this->nextPalawanSequence($yearSuffix), 3, '0', STR_PAD_LEFT);

                    $updates['trip_ticket_number'] = "{$yearSuffix}-{$month}-{$sequence}";
                } else {
                    $abbr = $locationAbbreviations[$locationKey] ?? strtoupper(Str::limit(Str::slug($locationKey, ''), 6, ''));

                    // Count approved requests for the same location in the same year-month
                    $count = VehicleRequest::where('status', 'approved')
                        ->where('location', $locationKey)
                        ->whereYear('approved_at', $year)
                        ->whereMonth('approved_at', $month)
                        ->count();

                    $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

                    $updates['trip_ticket_number'] = "FMS-{$abbr}-{$year}-{$month}-{$sequence}";
                }
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

    public function adminStore(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role?->value, ['admin', 'super_admin'], true), 403);

        $isSuperAdmin = $request->user()?->role?->value === 'super_admin';
        $adminLocation = $request->user()->location;

        $data = $request->validate([
            'requester_name'        => ['required', 'string', 'max:120'],
            'requester_office'      => ['nullable', 'string', 'max:150'],
            'requester_contact'     => ['required', 'string', 'regex:/^09\d{9}$/'],
            'requester_is_passenger'=> ['nullable', 'boolean'],
            'location'              => ['required', 'string', 'max:120'],
            'vehicle_id'            => ['required', 'integer', 'exists:vehicles,id'],
            'confirmed_driver_id'   => ['nullable', 'integer', 'exists:users,id'],
            'destination'           => ['required', 'string', 'max:250'],
            'travel_date'           => ['required', 'date'],
            'travel_date_end'       => ['required', 'date', 'after_or_equal:travel_date'],
            'purpose'               => ['required', 'string', 'max:500'],
            'passengers'            => ['nullable', 'array', 'max:20'],
            'passengers.*'          => ['string', 'max:120'],
            'override_conflict'     => ['nullable', 'boolean'],
        ], [
            'requester_contact.regex' => 'Contact number must start with 09 and be exactly 11 digits (e.g. 09171234567).',
        ]);

        // Location guard for regular admins
        if (!$isSuperAdmin && $data['location'] !== $adminLocation) {
            return back()->withErrors(['location' => 'You can only create requests for your assigned location.'])->withInput();
        }

        // Vehicle conflict check (booking overlap with existing approved/pending requests)
        $overrideConflict = $isSuperAdmin && !empty($data['override_conflict']);
        if (!$overrideConflict) {
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
                            . $conflictingRequest->travel_date->format('M j')
                            . ($conflictingRequest->travel_date_end ? ' – ' . $conflictingRequest->travel_date_end->format('M j, Y') : ', ' . $conflictingRequest->travel_date->format('Y'))
                            . ". " . ($isSuperAdmin ? 'Check the override box to proceed anyway.' : 'Please choose a different vehicle or dates.'),
                    ])
                    ->withInput();
            }
        }

        // Require a driver
        $driverId = $data['confirmed_driver_id'] ?? null;
        if (!$driverId) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            $driverId = $vehicle?->assigned_driver_id;
        }
        if (!$driverId) {
            return back()->withErrors(['confirmed_driver_id' => 'Please select a driver. This vehicle has no default driver assigned.'])->withInput();
        }

        // Generate unique reference code
        do {
            $code = Str::upper(Str::random(7));
        } while (VehicleRequest::where('reference_code', $code)->exists());

        // Generate trip ticket number
        $locationAbbreviations = [
            'Oriental Mindoro'            => 'OR-MIN',
            'Occidental Mindoro'          => 'OC-MIN',
            'Marinduque'                  => 'MAR',
            'Palawan'                     => 'PAL',
            'Romblon'                     => 'ROM',
            'Quezon City Satellite Office' => 'QC',
        ];

        $locationKey = $data['location'];
        $year  = now()->format('Y');
        $month = now()->format('m');

        if ($locationKey === 'Palawan') {
            $yearSuffix = now()->format('y');
            $sequence   = str_pad((string) $this->nextPalawanSequence($yearSuffix), 3, '0', STR_PAD_LEFT);
            $ticketNumber = "{$yearSuffix}-{$month}-{$sequence}";
        } else {
            $abbr  = $locationAbbreviations[$locationKey] ?? strtoupper(Str::limit(Str::slug($locationKey, ''), 6, ''));
            $count = VehicleRequest::where('status', 'approved')
                ->where('location', $locationKey)
                ->whereYear('approved_at', $year)
                ->whereMonth('approved_at', $month)
                ->count();
            $sequence     = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $ticketNumber = "FMS-{$abbr}-{$year}-{$month}-{$sequence}";
        }

        // Normalize passenger names to Title Case
        $passengers = array_filter(array_map(
            fn (string $p) => \Modules\VehicleRequest\Support\PersonName::titleCase($p),
            (array) ($data['passengers'] ?? [])
        ));

        $vehicleRequest = VehicleRequest::create([
            'reference_code'         => $code,
            'requester_name'         => \Modules\VehicleRequest\Support\PersonName::titleCase($data['requester_name']),
            'location'               => $data['location'],
            'requester_office'       => $data['requester_office'] ?? null,
            'requester_contact'      => $data['requester_contact'],
            'requester_is_passenger' => (bool) ($data['requester_is_passenger'] ?? false),
            'vehicle_id'             => $data['vehicle_id'],
            'confirmed_driver_id'    => $driverId,
            'destination'            => $data['destination'],
            'travel_date'            => $data['travel_date'],
            'travel_date_end'        => $data['travel_date_end'],
            'purpose'                => $data['purpose'],
            'passengers'             => array_values($passengers) ?: null,
            'status'                 => 'approved',
            'trip_ticket_number'     => $ticketNumber,
            'approved_by'            => $request->user()->id,
            'approved_at'            => now(),
        ]);

        return redirect()
            ->route('vehicle-requests.index', array_filter(['status' => 'approved', 'location' => request('location')]))
            ->with('manual_request_created', [
                'requester'   => $vehicleRequest->requester_name,
                'ticket'      => $ticketNumber,
                'ticket_url'  => route('trip-ticket', $vehicleRequest),
            ]);
    }

    public function tripTicket(VehicleRequest $vehicleRequest): View
    {
        abort_unless($vehicleRequest->status === 'approved', 404);
        $vehicleRequest->load(['vehicle', 'confirmedDriver', 'approvedBy']);

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
            'vehicles' => Vehicle::query()->with('assignedDriver')->orderBy('plate_number')->get(),
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
            ->with('assignedDriver')
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
                    'driver' => $v->assignedDriver->name ?? null,
                    'driver_photo' => $v->assignedDriver->avatar_url ?? null,
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
