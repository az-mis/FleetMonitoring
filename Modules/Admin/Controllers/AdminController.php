<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Admin\Requests\StoreAdminRequest;
use Modules\Admin\Requests\UpdateAdminRequest;
use Modules\Admin\Services\AdminService;
use Modules\ApprovingOfficer\Models\ApprovingOfficer;

class AdminController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin::index', [
            'admins' => $this->adminService->list(search: request('search'), location: request('location'), perPage: min(max((int) request('per_page', 10), 1), 100)),
            'approvingOfficers' => ApprovingOfficer::query()->get()->keyBy('location'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin::create');
    }

    public function store(StoreAdminRequest $request): RedirectResponse
    {
        $this->adminService->createAdmin($request->validated());

        return redirect()->route('admin.index')->with('success', 'Admin created successfully.');
    }

    public function edit(User $admin): View
    {
        $this->authorize('update', $admin);

        return view('admin::edit', compact('admin'));
    }

    public function update(UpdateAdminRequest $request, User $admin): RedirectResponse
    {
        $this->adminService->updateAdmin($admin, $request->validated());

        return redirect()->route('admin.index')->with('success', 'Admin updated successfully.');
    }

    public function destroy(User $admin): RedirectResponse
    {
        $this->authorize('delete', $admin);

        $this->adminService->deleteAdmin($admin);

        return redirect()->route('admin.index')->with('success', 'Admin deleted.');
    }
}
