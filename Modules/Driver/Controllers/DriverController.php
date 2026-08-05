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

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {
    }

    public function index(): View
    {
        Gate::authorize('manage-drivers');

        return view('driver::index', [
            'drivers' => $this->driverService->list(search: request('search')),
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

        $this->driverService->createDriver($request->validated());

        return redirect()->route('driver.index')->with('success', 'Driver created successfully.');
    }

    public function edit(User $driver): View
    {
        Gate::authorize('manage-drivers');

        return view('driver::edit', compact('driver'));
    }

    public function update(UpdateDriverRequest $request, User $driver): RedirectResponse
    {
        Gate::authorize('manage-drivers');

        $this->driverService->updateDriver($driver, $request->validated());

        return redirect()->route('driver.index')->with('success', 'Driver updated successfully.');
    }

    public function destroy(User $driver): RedirectResponse
    {
        Gate::authorize('manage-drivers');

        $this->driverService->deleteDriver($driver);

        return redirect()->route('driver.index')->with('success', 'Driver deleted.');
    }
}
