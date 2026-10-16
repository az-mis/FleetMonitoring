<?php

declare(strict_types=1);

namespace Modules\Driver\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Modules\Driver\Requests\StoreDriverRequest;
use Modules\Driver\Requests\UpdateDriverRequest;
use Modules\Driver\Services\DriverService;
use Modules\Admin\Enums\UserRole;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {
    }

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
