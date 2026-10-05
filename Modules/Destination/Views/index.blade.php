@extends('layouts.app')

@section('title', 'Destinations & Distance')
@section('page-title', 'Destinations & Distance')

@section('content')
    @php
        $locationOptions = [
            'Oriental Mindoro',
            'Occidental Mindoro',
            'Marinduque',
            'Palawan',
            'Romblon',
            'Quezon City Satellite Office',
        ];
        $locationButtonLabel = $selectedLocation ?: 'All Locations';
    @endphp

    {{-- Page Header --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Destinations & Distance</h1>
            <p class="text-sm text-slate-500">Manage standard travel destinations and one-way kilometer distances</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            {{-- Search Bar --}}
            <form method="GET" action="{{ route('destinations.index') }}" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-80">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search destination..."
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                </div>
                @if(request('location'))
                    <input type="hidden" name="location" value="{{ request('location') }}">
                @endif
                @if(request('search'))
                    <a href="{{ route('destinations.index', array_filter(['location' => request('location')])) }}" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-slate-700">Clear</a>
                @endif
            </form>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Location filter (Super Admin only) --}}
                @if($isSuperAdmin)
                    <div class="relative" id="dest-location-dropdown">
                        <button
                            type="button"
                            onclick="toggleDestLocationMenu()"
                            class="inline-flex h-10 items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 cursor-pointer"
                        >
                            <span class="flex items-center gap-1.5 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span class="max-w-[9rem] truncate">{{ $locationButtonLabel }}</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div id="dest-location-menu" class="absolute left-0 z-20 mt-2 hidden w-64 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5 sm:left-auto sm:right-0">
                            <a href="{{ route('destinations.index', array_filter(['search' => request('search')])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 {{ empty($selectedLocation) ? 'bg-emerald-50 text-emerald-800' : '' }}">
                                <span>All Locations</span>
                            </a>
                            @foreach ($locationOptions as $loc)
                                <a href="{{ route('destinations.index', array_filter(['location' => $loc, 'search' => request('search')])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 {{ $selectedLocation === $loc ? 'bg-emerald-50 text-emerald-800' : '' }}">
                                    <span class="truncate">{{ $loc }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Add Destination Button --}}
                <button
                    type="button"
                    onclick="openAddDestinationModal()"
                    class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm hover:bg-[#00432a] transition cursor-pointer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Add Destination</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="mb-4 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- Table Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">Place Name</th>
                        <th class="py-3 px-4">Location</th>
                        <th class="py-3 px-4 text-center">One-Way (KMs)</th>
                        <th class="py-3 px-4 text-center">Round Trip (KMs)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right pr-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($destinations as $index => $dest)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 text-center font-bold text-slate-400">
                                {{ $destinations->firstItem() + $index }}
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-900">
                                {{ $dest->name }}
                                @if($dest->notes)
                                    <p class="text-[11px] font-normal text-slate-400 mt-0.5">{{ $dest->notes }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    {{ $dest->location }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 rounded-lg px-2.5 py-1 text-xs">
                                    {{ $dest->distance_km }} kms
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center gap-1 font-semibold text-slate-600 bg-slate-100 rounded-lg px-2.5 py-1 text-xs">
                                    {{ $dest->distance_km * 2 }} kms
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($dest->is_active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right pr-6">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Edit --}}
                                    <button
                                        type="button"
                                        onclick="openEditDestinationModal({{ json_encode($dest) }})"
                                        class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-emerald-700 transition cursor-pointer"
                                        title="Edit"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>

                                    {{-- Toggle Active --}}
                                    <form method="POST" action="{{ route('destinations.toggle', $dest->id) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 {{ $dest->is_active ? 'hover:text-amber-600' : 'hover:text-emerald-600' }} transition cursor-pointer"
                                            title="{{ $dest->is_active ? 'Deactivate' : 'Activate' }}"
                                        >
                                            @if($dest->is_active)
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 9h6v6H9z"/>
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('destinations.destroy', $dest->id) }}" onsubmit="return confirm('Are you sure you want to delete this destination?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 transition cursor-pointer"
                                            title="Delete"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <p class="text-xs font-semibold">No destinations found</p>
                                    <p class="text-[11px] text-slate-400">Click "+ Add Destination" to add one for your station.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($destinations->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $destinations->links() }}
            </div>
        @endif
    </div>

    {{-- Add Destination Modal --}}
    <div id="add-destination-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                    </span>
                    <h3 class="text-base font-black text-slate-900">Add Destination</h3>
                </div>
                <button type="button" onclick="closeAddDestinationModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('destinations.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Place Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Aborlan, Palawan" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Location / Station <span class="text-red-500">*</span></label>
                    @if($isSuperAdmin)
                        <select name="location" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @foreach($locationOptions as $loc)
                                <option value="{{ $loc }}" @selected(($selectedLocation ?: 'Palawan') === $loc)>{{ $loc }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" value="{{ auth()->user()->location ?: 'Palawan' }}" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 font-semibold cursor-not-allowed">
                        <input type="hidden" name="location" value="{{ auth()->user()->location ?: 'Palawan' }}">
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">One-Way Distance (KMs) <span class="text-red-500">*</span></label>
                    <input type="number" name="distance_km" min="0" required placeholder="e.g. 69" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <p class="text-[11px] text-slate-400 mt-1">Round-trip will automatically be calculated as 2x of this value.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Notes / Route Remarks</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes, highway info, etc." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddDestinationModal()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-xl bg-[#005a36] px-5 py-2 text-xs font-bold text-white hover:bg-[#00432a] transition cursor-pointer">
                        Save Destination
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Destination Modal --}}
    <div id="edit-destination-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </span>
                    <h3 class="text-base font-black text-slate-900">Edit Destination</h3>
                </div>
                <button type="button" onclick="closeEditDestinationModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <form id="edit-destination-form" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Place Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="edit-dest-name" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Location / Station <span class="text-red-500">*</span></label>
                    @if($isSuperAdmin)
                        <select name="location" id="edit-dest-location" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @foreach($locationOptions as $loc)
                                <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" id="edit-dest-location-display" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 font-semibold cursor-not-allowed">
                        <input type="hidden" name="location" id="edit-dest-location-hidden">
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">One-Way Distance (KMs) <span class="text-red-500">*</span></label>
                    <input type="number" name="distance_km" id="edit-dest-distance" min="0" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Notes / Route Remarks</label>
                    <textarea name="notes" id="edit-dest-notes" rows="2" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditDestinationModal()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-xl bg-[#005a36] px-5 py-2 text-xs font-bold text-white hover:bg-[#00432a] transition cursor-pointer">
                        Update Destination
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDestLocationMenu() {
            const menu = document.getElementById('dest-location-menu');
            if (menu) menu.classList.toggle('hidden');
        }

        function openAddDestinationModal() {
            document.getElementById('add-destination-modal').classList.remove('hidden');
            document.getElementById('add-destination-modal').classList.add('flex');
        }

        function closeAddDestinationModal() {
            document.getElementById('add-destination-modal').classList.add('hidden');
            document.getElementById('add-destination-modal').classList.remove('flex');
        }

        function openEditDestinationModal(dest) {
            const form = document.getElementById('edit-destination-form');
            form.action = `/destinations/${dest.id}`;
            document.getElementById('edit-dest-name').value = dest.name;
            document.getElementById('edit-dest-distance').value = dest.distance_km;
            document.getElementById('edit-dest-notes').value = dest.notes || '';

            const superAdminSel = document.getElementById('edit-dest-location');
            if (superAdminSel) {
                superAdminSel.value = dest.location;
            } else {
                document.getElementById('edit-dest-location-display').value = dest.location;
                document.getElementById('edit-dest-location-hidden').value = dest.location;
            }

            document.getElementById('edit-destination-modal').classList.remove('hidden');
            document.getElementById('edit-destination-modal').classList.add('flex');
        }

        function closeEditDestinationModal() {
            document.getElementById('edit-destination-modal').classList.add('hidden');
            document.getElementById('edit-destination-modal').classList.remove('flex');
        }
    </script>
@endsection
