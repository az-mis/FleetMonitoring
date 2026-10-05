<?php

declare(strict_types=1);

namespace Modules\ApprovingOfficer\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Illuminate\Validation\ValidationException;
use Modules\ApprovingOfficer\Models\ApprovingOfficer;

class ApprovingOfficerController
{
    public function index(Request $request): View
    {
        abort_unless(in_array($request->user()?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true), 403);
        
        $this->ensureDefaultOfficersExist();

        $search = trim($request->string('search')->toString());

        $query = ApprovingOfficer::query()->with(['updatedBy', 'signatories'])->orderBy('location');
        if ($request->user()->role === UserRole::ADMIN && $request->user()->location) {
            $query->where('location', $request->user()->location);
        }

        if ($search) {
            $query->where(function ($builder) use ($search) {
                $builder->where('location', 'like', "%{$search}%")
                    ->orWhereHas('signatories', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%");
                    });
            });
        }

        return view('approving-officer::index', ['officers' => $query->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true), 403);
        $data = $request->validate([
            'location' => ['required', 'string', 'max:120'],
            'signatories' => ['required', 'array', 'min:1', 'max:' . ApprovingOfficer::MAX_SIGNATORIES],
            'signatories.*.name' => ['required', 'string', 'max:150'],
            'signatories.*.title' => ['nullable', 'string', 'max:150'],
            'signatories.*.show_on_ticket' => ['nullable', 'boolean'],
        ], [
            'signatories.max' => 'You can add a maximum of ' . ApprovingOfficer::MAX_SIGNATORIES . ' signatories per office.',
            'signatories.required' => 'Add at least one signatory.',
            'signatories.*.name.required' => 'Every signatory needs a name.',
        ]);

        $rows = collect($data['signatories'])->map(fn ($r) => [
            'name' => trim($r['name']),
            'title' => isset($r['title']) ? trim($r['title']) : null,
            'show_on_ticket' => !empty($r['show_on_ticket']),
        ])->values();

        $selected = $rows->where('show_on_ticket', true)->count();
        if ($selected < 1 || $selected > ApprovingOfficer::MAX_ON_TICKET) {
            throw ValidationException::withMessages([
                'signatories' => 'Select at least 1 and at most ' . ApprovingOfficer::MAX_ON_TICKET . ' signatories to appear on the Trip Ticket.',
            ]);
        }

        $this->authorizeLocation($request, $data['location']);

        $officer = ApprovingOfficer::firstOrCreate(['location' => $data['location']]);
        $officer->syncSignatories($rows->all(), $request->user()->id);

        return back()->with('success', 'Approving officers updated.');
    }

    public function updateTicketSelection(Request $request, ApprovingOfficer $approvingOfficer): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true), 403);
        $this->authorizeLocation($request, $approvingOfficer->location);

        $ids = collect($request->input('ticket', []))->map(fn ($id) => (int) $id)->unique()->values();
        // intersect() keeps the order the user chose (1st, 2nd); a plain whereIn()->pluck() would not.
        $validIds = $ids->intersect($approvingOfficer->signatories()->pluck('id'))->values();

        if ($validIds->count() < 1 || $validIds->count() > ApprovingOfficer::MAX_ON_TICKET) {
            throw ValidationException::withMessages([
                'ticket' => 'Select at least 1 and at most ' . ApprovingOfficer::MAX_ON_TICKET . ' signatories for the Trip Ticket.',
            ]);
        }

        $approvingOfficer->setTicketSelection($validIds->all(), $request->user()->id);

        return back()->with('success', 'Trip Ticket signatories updated for ' . $approvingOfficer->location . '.');
    }

    private function authorizeLocation(Request $request, string $location): void
    {
        $user = $request->user();
        if ($user->role === UserRole::ADMIN && $user->location) {
            abort_unless($user->location === $location, 403);
        }
    }

    public function destroy(Request $request, ApprovingOfficer $approvingOfficer): RedirectResponse
    {
        abort_unless($request->user()?->role === UserRole::SUPER_ADMIN, 403);
        $approvingOfficer->delete();
        return back()->with('success', 'Approving officer record removed.');
    }

    private function ensureDefaultOfficersExist(): void
    {
        $defaults = [
            [
                'location' => 'Regional Office (Default)',
                'signee1_name' => 'ARJAY D. BURGOS',
                'signee1_title' => 'OIC - APCO-Oriental Mindoro',
                'signee2_name' => 'EDGARDO F. LEIDO, Jr.',
                'signee2_title' => 'GSS Regional Office Calapan City',
            ],
            [
                'location' => 'Oriental Mindoro',
                'signee1_name' => 'ARJAY D. BURGOS',
                'signee1_title' => 'OIC - APCO-Oriental Mindoro',
                'signee2_name' => 'EDGARDO F. LEIDO, Jr.',
                'signee2_title' => 'GSS Regional Office Calapan City',
            ],
            [
                'location' => 'Occidental Mindoro',
                'signee1_name' => 'ARJAY D. BURGOS',
                'signee1_title' => 'OIC - APCO-Occidental Mindoro',
                'signee2_name' => 'EDGARDO F. LEIDO, Jr.',
                'signee2_title' => 'GSS Regional Office Calapan City',
            ],
            [
                'location' => 'Marinduque',
                'signee1_name' => 'ARJAY D. BURGOS',
                'signee1_title' => 'OIC - APCO-Marinduque',
                'signee2_name' => 'EDGARDO F. LEIDO, Jr.',
                'signee2_title' => 'GSS Regional Office Calapan City',
            ],
            [
                'location' => 'Palawan',
                'signee1_name' => 'ARJAY D. BURGOS',
                'signee1_title' => 'OIC - APCO-Palawan',
                'signee2_name' => 'EDGARDO F. LEIDO, Jr.',
                'signee2_title' => 'GSS Regional Office Calapan City',
            ],
            [
                'location' => 'Romblon',
                'signee1_name' => 'ARJAY D. BURGOS',
                'signee1_title' => 'OIC - APCO-Romblon',
                'signee2_name' => 'EDGARDO F. LEIDO, Jr.',
                'signee2_title' => 'GSS Regional Office Calapan City',
            ],
            [
                'location' => 'Quezon City Satellite Office',
                'signee1_name' => 'Alvin Zoleta',
                'signee1_title' => 'Information System Analyst I',
                'signee2_name' => 'Lloyd Ramirez',
                'signee2_title' => 'Computer Programmer I',
            ],
        ];

        foreach ($defaults as $data) {
            $officer = ApprovingOfficer::where('location', $data['location'])->first();
            if (!$officer) {
                $officer = ApprovingOfficer::create($data);
            } elseif ($officer->signatories()->exists() || filled($officer->signee1_name) || filled($officer->signee2_name)) {
                continue;
            } else {
                $officer->update($data);
            }

            if (!$officer->signatories()->exists()) {
                $officer->syncFromPair($data['signee1_name'], $data['signee1_title'], $data['signee2_name'], $data['signee2_title']);
            }
        }
    }
}
