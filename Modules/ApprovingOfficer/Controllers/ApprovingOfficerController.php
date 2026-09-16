<?php

declare(strict_types=1);

namespace Modules\ApprovingOfficer\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\ApprovingOfficer\Models\ApprovingOfficer;

class ApprovingOfficerController
{
    public function index(Request $request): View
    {
        abort_unless(in_array($request->user()?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true), 403);
        $query = ApprovingOfficer::query()->orderBy('location');
        if ($request->user()->role === UserRole::ADMIN && $request->user()->location) {
            $query->where('location', $request->user()->location);
        }

        return view('approving-officer::index', ['officers' => $query->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true), 403);
        $data = $request->validate([
            'location' => ['required', 'string', 'max:120'],
            'signee1_name' => ['nullable', 'string', 'max:150'],
            'signee1_title' => ['nullable', 'string', 'max:150'],
            'signee2_name' => ['nullable', 'string', 'max:150'],
            'signee2_title' => ['nullable', 'string', 'max:150'],
        ]);
        $data['updated_by'] = $request->user()->id;
        ApprovingOfficer::updateOrCreate(['location' => $data['location']], $data);
        return back()->with('success', 'Approving officers updated.');
    }

    public function destroy(Request $request, ApprovingOfficer $approvingOfficer): RedirectResponse
    {
        abort_unless($request->user()?->role === UserRole::SUPER_ADMIN, 403);
        $approvingOfficer->delete();
        return back()->with('success', 'Approving officer record removed.');
    }
}
