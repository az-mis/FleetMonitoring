<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Admin\Requests\StoreAdminRequest;
use Modules\Admin\Requests\UpdateAdminRequest;
use Modules\Admin\Services\AdminService;
use Modules\ApprovingOfficer\Models\ApprovingOfficer;

class AdminController extends Controller
{
    use \App\Http\Controllers\Concerns\ReturnsToList;

    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $admins = $this->adminService->list(search: request('search'), location: request('location'), perPage: min(max((int) request('per_page', 10), 1), 100));
        if ($lastPage = $this->pageOutOfRange($admins)) {
            return $lastPage;
        }

        return view('admin::index', [
            'admins' => $admins,
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

        return $this->backToList('admin.index')->with('success', 'Admin created successfully.');
    }

    public function edit(User $admin): View
    {
        $this->authorize('update', $admin);

        return view('admin::edit', compact('admin'));
    }

    public function update(UpdateAdminRequest $request, User $admin): RedirectResponse
    {
        $data = $request->validated();

        // Demoting yourself could leave the system without a Super Admin
        if ($admin->is($request->user()) && ($data['role'] ?? $admin->role->value) !== UserRole::SUPER_ADMIN->value) {
            return $this->backToList('admin.index')->with('error', 'You cannot change your own role. Ask another Super Admin to do it.');
        }

        $this->adminService->updateAdmin($admin, $data);

        return $this->backToList('admin.index')->with('success', 'Admin updated successfully.');
    }

    public function destroy(User $admin): RedirectResponse
    {
        $this->authorize('delete', $admin);

        // Only a Super Admin can delete accounts, so blocking self-deletion also keeps at least one Super Admin
        if ($admin->is(auth()->user())) {
            return $this->backToList('admin.index')->with('error', 'You cannot delete your own account. Ask another Super Admin to remove it.');
        }

        $this->adminService->deleteAdmin($admin);

        return $this->backToList('admin.index')->with('success', 'Admin deleted.');
    }
}
