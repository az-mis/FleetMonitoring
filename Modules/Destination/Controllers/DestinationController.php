<?php

namespace Modules\Destination\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Destination\Models\Destination;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->role?->value === 'super_admin';

        $query = Destination::query();

        // Location filtering
        $selectedLocation = $request->input('location');
        if (!$isSuperAdmin) {
            $selectedLocation = $user->location ?: 'Palawan';
            $query->where('location', $selectedLocation);
        } elseif ($selectedLocation) {
            $query->where('location', $selectedLocation);
        }

        // Search filtering
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);

        $destinations = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        return view('destination::index', compact('destinations', 'selectedLocation', 'isSuperAdmin', 'perPage'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->role?->value === 'super_admin';

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'distance_km' => 'nullable|integer|min:0',
            'notes'       => 'nullable|string|max:1000',
        ]);

        if (!$isSuperAdmin) {
            $validated['location'] = $user->location ?: 'Palawan';
        }

        $validated['created_by'] = $user->id;

        $destination = Destination::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Destination added successfully.',
                'destination' => $destination,
            ]);
        }

        return redirect()->route('destinations.index')
            ->with('success', "Destination '{$destination->name}' added successfully.");
    }

    public function update(Request $request, Destination $destination)
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->role?->value === 'super_admin';

        if (!$isSuperAdmin && $destination->location !== $user->location) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'distance_km' => 'nullable|integer|min:0',
            'notes'       => 'nullable|string|max:1000',
        ]);

        if (!$isSuperAdmin) {
            $validated['location'] = $user->location;
        }

        $destination->update($validated);

        return redirect()->route('destinations.index')
            ->with('success', "Destination '{$destination->name}' updated successfully.");
    }

    public function toggle(Destination $destination)
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->role?->value === 'super_admin';

        if (!$isSuperAdmin && $destination->location !== $user->location) {
            abort(403, 'Unauthorized action.');
        }

        $destination->is_active = !$destination->is_active;
        $destination->save();

        $statusStr = $destination->is_active ? 'activated' : 'deactivated';
        return redirect()->route('destinations.index')
            ->with('success', "Destination '{$destination->name}' {$statusStr}.");
    }

    public function destroy(Destination $destination)
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->role?->value === 'super_admin';

        if (!$isSuperAdmin && $destination->location !== $user->location) {
            abort(403, 'Unauthorized action.');
        }

        $name = $destination->name;
        $destination->delete();

        return redirect()->route('destinations.index')
            ->with('success', "Destination '{$name}' deleted successfully.");
    }

    public function listByLocation(Request $request)
    {
        $location = $request->input('location', 'Palawan');

        $destinations = Destination::where('location', $location)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'distance_km', 'location']);

        return response()->json([
            'destinations' => $destinations,
        ]);
    }
}
