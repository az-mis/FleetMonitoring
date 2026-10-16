<?php

declare(strict_types=1);

namespace Modules\Driver\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Driver\Requests\StoreDriverRequest;
use Modules\Driver\Requests\UpdateDriverRequest;
use Modules\Driver\Services\DriverService;
use Modules\VehicleReport\Models\VehicleIssueReport;
use Modules\VehicleRequest\Models\VehicleRequest;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {}

    public function index(): View
    {
        Gate::authorize('manage-drivers');

        $perPage = min(max((int) request('per_page', 10), 1), 100);
        $search = request('search');
        $location = auth()->user()?->role === UserRole::ADMIN ? auth()->user()->location : request('location');

        return view('driver::index', [
            'drivers' => $this->driverService->list(
                search: $search,
                location: $location,
                perPage: $perPage,
            ),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-drivers');

        return view('driver::create');
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        Gate::authorize('manage-drivers');

        $data = $request->validated();
        if (auth()->user()?->role === UserRole::ADMIN) {
            $data['location'] = auth()->user()->location;
        }
        $this->driverService->createDriver($data);

        return redirect()->route('driver.index')->with('success', 'Driver created successfully.');
    }

    public function edit(User $driver): View
    {
        Gate::authorize('manage-drivers');
        $this->authorizeLocation($driver);

        return view('driver::edit', compact('driver'));
    }

    public function update(UpdateDriverRequest $request, User $driver): RedirectResponse
    {
        Gate::authorize('manage-drivers');
        $this->authorizeLocation($driver);

        $data = $request->validated();
        if (auth()->user()?->role === UserRole::ADMIN) {
            $data['location'] = auth()->user()->location;
        }
        $this->driverService->updateDriver($driver, $data);

        return redirect()->route('driver.index')
            ->with('success', "{$driver->name} updated.")
            ->with('driver_name', $driver->name);
    }

    public function destroy(User $driver): RedirectResponse
    {
        Gate::authorize('manage-drivers');
        $this->authorizeLocation($driver);

        // Deleting would erase the driver from trip tickets and cascade-delete their vehicle reports
        $hasRecords = VehicleRequest::where('confirmed_driver_id', $driver->id)->exists()
            || VehicleIssueReport::where('driver_id', $driver->id)->exists();

        if ($hasRecords) {
            return redirect()->route('driver.index')
                ->with('error', "{$driver->name} has trips or vehicle reports on record and cannot be deleted. These records must keep the driver's name.");
        }

        $this->driverService->deleteDriver($driver);

        return redirect()->route('driver.index')->with('success', 'Driver deleted.');
    }

    private function authorizeLocation(User $driver): void
    {
        abort_unless($driver->role === UserRole::DRIVER, 404);
        abort_if(auth()->user()?->role === UserRole::ADMIN && blank(auth()->user()->location), 403);
        abort_unless(auth()->user()?->role !== UserRole::ADMIN || $driver->location === auth()->user()->location, 403);
    }
}
