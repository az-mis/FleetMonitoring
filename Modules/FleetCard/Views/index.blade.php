@extends('layouts.app')

@php
    $isSuperAdmin = auth()->user()?->role?->value === 'super_admin';
    $locations = [
        'Oriental Mindoro',
        'Occidental Mindoro',
        'Marinduque',
        'Palawan',
        'Romblon',
        'Quezon City Satellite Office',
    ];
    $userLocation = auth()->user()?->location ?? 'Palawan';
@endphp

@section('content')
<div class="space-y-6">
    {{-- Header & Actions --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center rounded-lg bg-emerald-100 p-2 text-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="14" x="2" y="5" rx="2"/>
                        <line x1="2" x2="22" y1="10" y2="10"/>
                    </svg>
                </span>
                <div>
                    <h1 class="text-xl font-extrabold text-gray-900 tracking-tight sm:text-2xl">Fleet Cards & Fuel Budget</h1>
                    <p class="text-xs text-gray-500 sm:text-sm">Manage fuel card budget allocation, consumption rates, and auto-expense calculation.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="openAddCardModal()" class="inline-flex items-center gap-2 rounded-xl bg-[#0f5d33] px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#0b4726]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="M12 5v14"/>
                </svg>
                Register Fleet Card
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Allocated Budget</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    💳
                </span>
            </div>
            <p class="mt-3 text-2xl font-black text-gray-900">₱{{ number_format($totalInitialBudget, 2) }}</p>
            <p class="mt-1 text-[11px] font-semibold text-gray-400">Total budget across registered fleet cards</p>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Expense Consumed</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    ⛽
                </span>
            </div>
            <p class="mt-3 text-2xl font-black text-amber-700">₱{{ number_format($totalConsumed, 2) }}</p>
            <p class="mt-1 text-[11px] font-semibold text-gray-400">Computed trip fuel expenditure</p>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Remaining Balance</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    ✅
                </span>
            </div>
            <p class="mt-3 text-2xl font-black text-emerald-700">₱{{ number_format($totalCurrentBalance, 2) }}</p>
            <p class="mt-1 text-[11px] font-semibold text-gray-400">Available balance remaining</p>
        </div>
    </div>

    {{-- Cards Grid --}}
    <div class="space-y-3">
        <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
            <span>Active Fleet Cards</span>
            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">{{ $cards->count() }}</span>
        </h2>

        @if($cards->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-3">
                    💳
                </div>
                <h3 class="text-sm font-bold text-gray-800">No Fleet Cards Registered Yet</h3>
                <p class="mt-1 text-xs text-gray-500 max-w-sm mx-auto">Add a fleet card assigned to a vehicle (e.g. for Palawan) with budget & fuel consumption rate.</p>
                <button type="button" onclick="openAddCardModal()" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#0f5d33] px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-[#0b4726]">
                    + Add First Fleet Card
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($cards as $card)
                    @php
                        $pct = $card->balance_percentage;
                        $barColor = $pct > 50 ? 'bg-emerald-500' : ($pct > 20 ? 'bg-amber-500' : 'bg-red-500');
                        $badgeColor = $pct > 50 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($pct > 20 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-red-50 text-red-700 border-red-200');
                    @endphp
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:shadow-md space-y-4">
                        {{-- Top row --}}
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-gray-900 text-base">{{ $card->card_name }}</span>
                                    <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-mono font-bold text-gray-600 border border-gray-200">{{ $card->card_number }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Assigned: 
                                    @if($card->vehicle)
                                        <span class="font-bold text-emerald-800">{{ $card->vehicle->plate_number }}</span> ({{ $card->vehicle->brand }} {{ $card->vehicle->model }})
                                    @else
                                        <span class="text-gray-400 italic">Unassigned</span>
                                    @endif
                                    • <span class="font-semibold text-gray-600">📍 {{ $card->location }}</span>
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $badgeColor }}">
                                {{ $pct }}% Balance
                            </span>
                        </div>

                        {{-- Budget Progress --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-gray-700">Remaining: <span class="text-emerald-700 font-extrabold text-sm">₱{{ number_format($card->current_balance, 2) }}</span></span>
                                <span class="text-gray-400 font-medium">Budget: ₱{{ number_format($card->initial_budget, 2) }}</span>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ min(100, max(0, $pct)) }}%"></div>
                            </div>
                        </div>

                        {{-- Rate & Capacity Info Box --}}
                        <div class="grid grid-cols-2 gap-2 rounded-xl bg-gray-50 p-3 text-xs border border-gray-100">
                            <div>
                                <span class="text-gray-400 font-medium">Consumption Rate:</span>
                                <p class="font-extrabold text-gray-800">
                                    ₱{{ number_format($card->rate_peso, 0) }} per {{ number_format($card->rate_km, 0) }} km
                                    <span class="text-[11px] font-semibold text-emerald-700 block">(₱{{ number_format($card->cost_per_km, 2) }} / km)</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-400 font-medium">Est. Distance Capacity:</span>
                                <p class="font-extrabold text-gray-800">
                                    ~{{ number_format($card->remaining_km_capacity, 1) }} km
                                    <span class="text-[11px] font-semibold text-gray-500 block">Remaining range</span>
                                </p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="openExpenseModal({{ json_encode($card) }})" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition">
                                    ⛽ Compute & Log Expense
                                </button>
                                <button type="button" onclick="openRechargeModal({{ json_encode($card) }})" class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 transition border border-blue-200">
                                    💳 Top Up
                                </button>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="openEditCardModal({{ json_encode($card) }})" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition" title="Edit Parameters">
                                    ✏️
                                </button>
                                <form action="{{ route('fleet-cards.destroy', $card->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this fleet card?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition" title="Delete Card">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Expense & Top Up Logs --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs space-y-4">
        <h3 class="text-sm font-bold text-gray-800 flex items-center justify-between">
            <span>Transaction Logs & Computations</span>
            <span class="text-xs text-gray-400 font-normal">Last {{ $recentLogs->count() }} transactions</span>
        </h3>

        @if($recentLogs->isEmpty())
            <p class="text-xs text-gray-400 text-center py-6">No transaction logs recorded yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-2.5 rounded-l-lg">Date & Time</th>
                            <th class="px-3 py-2.5">Card / Vehicle</th>
                            <th class="px-3 py-2.5">Distance Source</th>
                            <th class="px-3 py-2.5">Distance (km)</th>
                            <th class="px-3 py-2.5">Rate (₱/km)</th>
                            <th class="px-3 py-2.5">Expense / Top-up</th>
                            <th class="px-3 py-2.5">New Balance</th>
                            <th class="px-3 py-2.5 rounded-r-lg">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        @foreach ($recentLogs as $log)
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="px-3 py-2.5 text-gray-400 whitespace-nowrap">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                                <td class="px-3 py-2.5 font-bold text-gray-900">
                                    {{ $log->fleetCard?->card_name ?? 'N/A' }}
                                    @if($log->vehicle)
                                        <span class="text-emerald-700 font-semibold block text-[11px]">({{ $log->vehicle->plate_number }})</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    @if($log->distance_source === 'odometer')
                                        <span class="rounded bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 border border-blue-100">Odometer</span>
                                        <span class="text-[10px] text-gray-400 block">{{ number_format($log->start_odometer) }} → {{ number_format($log->end_odometer) }}</span>
                                    @elseif($log->distance_source === 'destination_master')
                                        <span class="rounded bg-purple-50 px-2 py-0.5 text-[10px] font-bold text-purple-700 border border-purple-100">Destination Master</span>
                                    @elseif($log->distance_source === 'recharge')
                                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-100">Top-up</span>
                                    @else
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700 border border-gray-200">Manual Input</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 font-bold text-gray-800">{{ number_format($log->distance_km, 1) }} km</td>
                                <td class="px-3 py-2.5 text-gray-500">₱{{ number_format($log->rate_peso_per_km, 2) }}</td>
                                <td class="px-3 py-2.5 font-extrabold whitespace-nowrap {{ $log->consumed_amount < 0 ? 'text-emerald-600' : 'text-amber-700' }}">
                                    {{ $log->consumed_amount < 0 ? '+₱' . number_format(abs($log->consumed_amount), 2) : '-₱' . number_format($log->consumed_amount, 2) }}
                                </td>
                                <td class="px-3 py-2.5 font-bold text-gray-900">₱{{ number_format($log->balance_after, 2) }}</td>
                                <td class="px-3 py-2.5 text-gray-500 max-w-xs truncate">{{ $log->remarks ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Modal 1: Add Fleet Card --}}
<div id="add-card-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-base font-extrabold text-gray-900">Register New Fleet Card</h3>
            <button type="button" onclick="closeAddCardModal()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form action="{{ route('fleet-cards.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Fleet Card Name / Provider</label>
                <input type="text" name="card_name" placeholder="e.g. Petron Fleet Card Palawan #1" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Card Number</label>
                    <input type="text" name="card_number" placeholder="FC-10492" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Location</label>
                    @if($isSuperAdmin)
                        <select name="location" id="add-card-location" onchange="onAddLocationChange(this.value)" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                            <option value="">-- Select Location --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="location" value="{{ $userLocation }}" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500 cursor-not-allowed focus:outline-none">
                    @endif
                </div>
            </div>

            {{-- SEARCHABLE VEHICLE PICKER WITH PHOTO --}}
            <div class="relative" id="add-vehicle-picker">
                <label class="block text-xs font-bold text-gray-700 mb-1">Assigned Vehicle</label>
                <select name="vehicle_id" id="add-field-vehicle-id" tabindex="-1" aria-hidden="true" class="pointer-events-none absolute bottom-0 left-0 h-px w-full opacity-0">
                    <option value="">-- Unassigned --</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->plate_number }} – {{ $v->brand }} {{ $v->model }}</option>
                    @endforeach
                </select>

                <button type="button" id="add-vp-trigger" onclick="toggleAddVehiclePicker()" class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-white px-3 py-2 text-left text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <span id="add-vp-thumb" class="hidden h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-white"></span>
                    <span id="add-vp-label" class="min-w-0 flex-1 truncate text-gray-400">Select vehicle...</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div id="add-vp-panel" class="absolute left-0 right-0 z-50 mt-1 hidden rounded-xl border border-gray-200 bg-white p-2 shadow-xl">
                    <input type="text" id="add-vp-search" oninput="filterAddVehiclePicker()" placeholder="Search plate or model..." class="mb-2 w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <div class="max-h-60 space-y-0.5 overflow-y-auto">
                        <button type="button" onclick="pickAddVehicle('')" class="add-vp-opt flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left text-xs font-semibold text-gray-500 hover:bg-emerald-50">
                            -- Unassigned --
                        </button>
                        @foreach($vehicles as $v)
                            <button type="button" onclick="pickAddVehicle('{{ $v->id }}')"
                                class="add-vp-opt flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left transition hover:bg-emerald-50"
                                data-id="{{ $v->id }}"
                                data-plate="{{ $v->plate_number }}"
                                data-model="{{ trim($v->brand.' '.$v->model) }}"
                                data-odometer="{{ number_format($v->odometer) }}"
                                data-location="{{ $v->location }}"
                                data-photo="{{ $v->photo_url ?? '' }}"
                                data-search="{{ strtolower($v->plate_number.' '.$v->brand.' '.$v->model.' '.$v->location) }}">
                                <span class="flex h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-white">
                                    @if(!empty($v->photo_url))
                                        <img src="{{ $v->photo_url }}" alt="" class="h-full w-full object-contain">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold text-gray-900">{{ $v->plate_number }}</span>
                                    <span class="block truncate text-[11px] text-gray-500">{{ trim($v->brand.' '.$v->model) }} @if($v->location) &middot; {{ $v->location }} @endif</span>
                                </span>
                            </button>
                        @endforeach
                        <p id="add-vp-empty" class="hidden px-3 py-3 text-center text-xs text-gray-400">No vehicles found.</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Initial Budget Allocated (₱)</label>
                <input type="number" step="0.01" name="initial_budget" value="5000.00" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-bold text-emerald-800 focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="rounded-xl bg-amber-50/60 p-3 border border-amber-200/60 space-y-2">
                <label class="block text-xs font-bold text-amber-900">Fuel Consumption Rule</label>
                <p class="text-[11px] text-amber-700">Specify how much peso worth of fuel is consumed for every X distance.</p>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <span class="text-[10px] font-bold text-gray-500">Peso Worth (₱)</span>
                        <input type="number" step="0.01" name="rate_peso" value="100.00" required class="w-full rounded-lg border border-amber-300 px-2.5 py-1.5 text-xs font-bold focus:outline-none">
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-500">Every Distance (km)</span>
                        <input type="number" step="0.01" name="rate_km" value="5.00" required class="w-full rounded-lg border border-amber-300 px-2.5 py-1.5 text-xs font-bold focus:outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Notes / Remarks</label>
                <textarea name="notes" rows="2" placeholder="Additional details..." class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeAddCardModal()" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="rounded-xl bg-[#0f5d33] px-5 py-2 text-xs font-bold text-white hover:bg-[#0b4726]">Register Card</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Compute & Log Expense --}}
<div id="expense-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div>
                <h3 class="text-base font-extrabold text-gray-900" id="expense-card-title">Compute Trip Fuel Expense</h3>
                <p class="text-xs text-gray-500" id="expense-card-sub">Fleet Card: <span id="expense-card-name" class="font-bold text-emerald-800"></span></p>
            </div>
            <button type="button" onclick="closeExpenseModal()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="expense-form" method="POST" class="space-y-3">
            @csrf
            
            {{-- Distance Source Selection --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Select Distance Method</label>
                <select id="expense-source-select" name="distance_source" onchange="toggleExpenseSourceFields()" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold focus:border-emerald-500 focus:outline-none">
                    <option value="odometer">Option 1: Base on Odometer Reading (Start → End)</option>
                    <option value="destination_master">Option 2: Base on Destination Master Distance (Point Zero)</option>
                    <option value="manual">Option 3: Manual Distance Input (km)</option>
                </select>
            </div>

            {{-- Fields for Option 1: Odometer --}}
            <div id="field-group-odometer" class="grid grid-cols-2 gap-3 rounded-xl bg-blue-50/60 p-3 border border-blue-200/60">
                <div>
                    <label class="block text-[11px] font-bold text-blue-900 mb-1">Start Odometer (km)</label>
                    <input type="number" id="expense-start-odo" name="start_odometer" placeholder="e.g. 10000" oninput="updateLiveCalc()" class="w-full rounded-lg border border-blue-300 px-2.5 py-1.5 text-xs font-bold focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-blue-900 mb-1">End Odometer (km)</label>
                    <input type="number" id="expense-end-odo" name="end_odometer" placeholder="e.g. 10150" oninput="updateLiveCalc()" class="w-full rounded-lg border border-blue-300 px-2.5 py-1.5 text-xs font-bold focus:outline-none">
                </div>
            </div>

            {{-- Fields for Option 2: Destination Master --}}
            <div id="field-group-destination" class="hidden space-y-2 rounded-xl bg-purple-50/60 p-3 border border-purple-200/60">
                <label class="block text-xs font-bold text-purple-900">Select Travel Request / Destination</label>
                <select id="expense-dest-select" name="vehicle_request_id" onchange="onDestinationSelectChange()" class="w-full rounded-lg border border-purple-300 px-2.5 py-1.5 text-xs font-semibold focus:outline-none">
                    <option value="">-- Pick Travel Request / Destination --</option>
                    @foreach($pendingRequests as $r)
                        <option value="{{ $r->id }}" data-distance="{{ $r->distance_km ?: 0 }}">{{ $r->reference_code }} — {{ $r->destination }} ({{ $r->distance_km ?: 'Calculated' }} km)</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-purple-700 italic">Distance is measured from Point Zero (Office).</p>
            </div>

            {{-- Fields for Option 3: Manual Distance --}}
            <div id="field-group-manual" class="hidden space-y-1 rounded-xl bg-gray-50 p-3 border border-gray-200">
                <label class="block text-xs font-bold text-gray-700">Distance Travelled (km)</label>
                <input type="number" step="0.1" id="expense-manual-km" name="distance_km" placeholder="e.g. 75.5" oninput="updateLiveCalc()" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-bold focus:outline-none">
            </div>

            {{-- LIVE CALCULATION BOX --}}
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 space-y-2">
                <div class="flex items-center justify-between text-xs font-extrabold uppercase tracking-wider text-emerald-900 border-b border-emerald-200/80 pb-1.5">
                    <span>⚡ Live Computation Summary</span>
                    <span id="live-rate-badge" class="rounded bg-white px-2 py-0.5 text-[10px] text-emerald-800 shadow-2xs">₱0/km</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-emerald-700 font-medium">Computed Distance:</span>
                        <p class="font-extrabold text-emerald-950 text-base" id="live-distance">0.0 km</p>
                    </div>
                    <div>
                        <span class="text-emerald-700 font-medium">Fuel Expense Consumed:</span>
                        <p class="font-black text-amber-800 text-base" id="live-consumed">₱0.00</p>
                    </div>
                    <div>
                        <span class="text-emerald-700 font-medium">Current Balance:</span>
                        <p class="font-bold text-emerald-900" id="live-current-balance">₱0.00</p>
                    </div>
                    <div>
                        <span class="text-emerald-700 font-medium">Balance After Expense:</span>
                        <p class="font-extrabold text-emerald-900 text-sm" id="live-new-balance">₱0.00</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Remarks / Trip Details</label>
                <input type="text" name="remarks" placeholder="e.g. Palawan trip to Narra station" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeExpenseModal()" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm">Confirm & Deduct Expense</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 3: Edit Fleet Card --}}
<div id="edit-card-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-base font-extrabold text-gray-900">Edit Fleet Card Parameters</h3>
            <button type="button" onclick="closeEditCardModal()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="edit-card-form" method="POST" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Fleet Card Name</label>
                <input type="text" id="edit-card-name" name="card_name" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Card Number</label>
                    <input type="text" id="edit-card-number" name="card_number" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Location</label>
                    @if($isSuperAdmin)
                        <select name="location" id="edit-card-location" onchange="onEditLocationChange(this.value)" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                            <option value="">-- Select Location --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" id="edit-location" name="location" value="{{ $userLocation }}" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500 cursor-not-allowed focus:outline-none">
                    @endif
                </div>
            </div>

            {{-- SEARCHABLE VEHICLE PICKER WITH PHOTO FOR EDIT MODAL --}}
            <div class="relative" id="edit-vehicle-picker">
                <label class="block text-xs font-bold text-gray-700 mb-1">Assigned Vehicle</label>
                <select name="vehicle_id" id="edit-field-vehicle-id" tabindex="-1" aria-hidden="true" class="pointer-events-none absolute bottom-0 left-0 h-px w-full opacity-0">
                    <option value="">-- Unassigned --</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->plate_number }} – {{ $v->brand }} {{ $v->model }}</option>
                    @endforeach
                </select>

                <button type="button" id="edit-vp-trigger" onclick="toggleEditVehiclePicker()" class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-white px-3 py-2 text-left text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <span id="edit-vp-thumb" class="hidden h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-white"></span>
                    <span id="edit-vp-label" class="min-w-0 flex-1 truncate text-gray-400">Select vehicle...</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div id="edit-vp-panel" class="absolute left-0 right-0 z-50 mt-1 hidden rounded-xl border border-gray-200 bg-white p-2 shadow-xl">
                    <input type="text" id="edit-vp-search" oninput="filterEditVehiclePicker()" placeholder="Search plate or model..." class="mb-2 w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <div class="max-h-60 space-y-0.5 overflow-y-auto">
                        <button type="button" onclick="pickEditVehicle('')" class="edit-vp-opt flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left text-xs font-semibold text-gray-500 hover:bg-emerald-50">
                            -- Unassigned --
                        </button>
                        @foreach($vehicles as $v)
                            <button type="button" onclick="pickEditVehicle('{{ $v->id }}')"
                                class="edit-vp-opt flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left transition hover:bg-emerald-50"
                                data-id="{{ $v->id }}"
                                data-plate="{{ $v->plate_number }}"
                                data-model="{{ trim($v->brand.' '.$v->model) }}"
                                data-location="{{ $v->location }}"
                                data-photo="{{ $v->photo_url ?? '' }}"
                                data-search="{{ strtolower($v->plate_number.' '.$v->brand.' '.$v->model.' '.$v->location) }}">
                                <span class="flex h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-white">
                                    @if(!empty($v->photo_url))
                                        <img src="{{ $v->photo_url }}" alt="" class="h-full w-full object-contain">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold text-gray-900">{{ $v->plate_number }}</span>
                                    <span class="block truncate text-[11px] text-gray-500">{{ trim($v->brand.' '.$v->model) }} @if($v->location) &middot; {{ $v->location }} @endif</span>
                                </span>
                            </button>
                        @endforeach
                        <p id="edit-vp-empty" class="hidden px-3 py-3 text-center text-xs text-gray-400">No vehicles found.</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Initial Budget Allocated (₱)</label>
                <input type="number" step="0.01" id="edit-initial-budget" name="initial_budget" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-bold text-emerald-800 focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="rounded-xl bg-amber-50/60 p-3 border border-amber-200/60 space-y-2">
                <label class="block text-xs font-bold text-amber-900">Fuel Consumption Rule</label>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <span class="text-[10px] font-bold text-gray-500">Peso Worth (₱)</span>
                        <input type="number" step="0.01" id="edit-rate-peso" name="rate_peso" required class="w-full rounded-lg border border-amber-300 px-2.5 py-1.5 text-xs font-bold focus:outline-none">
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-500">Every Distance (km)</span>
                        <input type="number" step="0.01" id="edit-rate-km" name="rate_km" required class="w-full rounded-lg border border-amber-300 px-2.5 py-1.5 text-xs font-bold focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditCardModal()" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="rounded-xl bg-[#0f5d33] px-5 py-2 text-xs font-bold text-white hover:bg-[#0b4726]">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 4: Top Up Budget --}}
<div id="recharge-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-base font-extrabold text-gray-900">Top Up Fleet Card</h3>
            <button type="button" onclick="closeRechargeModal()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="recharge-form" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Top-up Amount (₱)</label>
                <input type="number" step="0.01" name="amount" placeholder="e.g. 2000.00" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-extrabold text-emerald-800 focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Remarks</label>
                <input type="text" name="remarks" placeholder="e.g. Monthly fuel reload" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeRechargeModal()" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 text-xs font-bold text-white hover:bg-blue-700">Confirm Top-Up</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentActiveCard = null;
const vehiclesData = @json($vehicles);

// ── ADD VEHICLE PICKER LOGIC ──────────────────────────────────────
function toggleAddVehiclePicker() {
    const panel = document.getElementById('add-vp-panel');
    const isHidden = panel.classList.contains('hidden');
    panel.classList.toggle('hidden', !isHidden);
    if (isHidden) {
        document.getElementById('add-vp-search').value = '';
        filterAddVehiclePicker();
        setTimeout(() => document.getElementById('add-vp-search').focus(), 50);
    }
}

function filterAddVehiclePicker() {
    const q = (document.getElementById('add-vp-search').value || '').toLowerCase().trim();
    const locSelect = document.getElementById('add-card-location');
    const selectedLoc = locSelect ? locSelect.value.toLowerCase().trim() : '';
    let count = 0;
    document.querySelectorAll('.add-vp-opt').forEach(btn => {
        const text = btn.getAttribute('data-search') || '';
        const btnLoc = (btn.getAttribute('data-location') || '').toLowerCase().trim();
        const isUnassigned = !btn.getAttribute('data-id');
        const matchesSearch = !q || text.includes(q) || isUnassigned;
        const matchesLocation = !selectedLoc || isUnassigned || btnLoc === selectedLoc;
        if (matchesSearch && matchesLocation) {
            btn.classList.remove('hidden');
            count++;
        } else {
            btn.classList.add('hidden');
        }
    });
    document.getElementById('add-vp-empty').classList.toggle('hidden', count > 0);
}

function onAddLocationChange(loc) {
    // Reset vehicle selection
    pickAddVehicle('');
    // Re-filter vehicle list
    filterAddVehiclePicker();
}

function pickAddVehicle(id) {
    document.getElementById('add-field-vehicle-id').value = id;
    document.getElementById('add-vp-panel').classList.add('hidden');

    const thumb = document.getElementById('add-vp-thumb');
    const label = document.getElementById('add-vp-label');

    if (!id) {
        thumb.classList.add('hidden');
        label.className = 'min-w-0 flex-1 truncate text-gray-400';
        label.innerText = '-- Unassigned --';
        return;
    }

    const vehicle = vehiclesData.find(v => v.id == id);
    if (vehicle) {
        if (vehicle.photo_url) {
            thumb.innerHTML = `<img src="${vehicle.photo_url}" class="h-full w-full object-contain">`;
            thumb.classList.remove('hidden');
        } else {
            thumb.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>`;
            thumb.classList.remove('hidden');
        }
        label.className = 'min-w-0 flex-1 text-gray-900';
        label.innerHTML = `<span class="font-bold">${vehicle.plate_number}</span> <span class="text-xs text-gray-500">(${vehicle.brand} ${vehicle.model})</span>`;
    }
}

// ── EDIT VEHICLE PICKER LOGIC ─────────────────────────────────────
function toggleEditVehiclePicker() {
    const panel = document.getElementById('edit-vp-panel');
    const isHidden = panel.classList.contains('hidden');
    panel.classList.toggle('hidden', !isHidden);
    if (isHidden) {
        document.getElementById('edit-vp-search').value = '';
        filterEditVehiclePicker();
        setTimeout(() => document.getElementById('edit-vp-search').focus(), 50);
    }
}

function filterEditVehiclePicker() {
    const q = (document.getElementById('edit-vp-search').value || '').toLowerCase().trim();
    const locSelect = document.getElementById('edit-card-location');
    const selectedLoc = locSelect ? locSelect.value.toLowerCase().trim() : '';
    let count = 0;
    document.querySelectorAll('.edit-vp-opt').forEach(btn => {
        const text = btn.getAttribute('data-search') || '';
        const btnLoc = (btn.getAttribute('data-location') || '').toLowerCase().trim();
        const isUnassigned = !btn.getAttribute('data-id');
        const matchesSearch = !q || text.includes(q) || isUnassigned;
        const matchesLocation = !selectedLoc || isUnassigned || btnLoc === selectedLoc;
        if (matchesSearch && matchesLocation) {
            btn.classList.remove('hidden');
            count++;
        } else {
            btn.classList.add('hidden');
        }
    });
    document.getElementById('edit-vp-empty').classList.toggle('hidden', count > 0);
}

function onEditLocationChange(loc) {
    // Reset vehicle selection
    pickEditVehicle('');
    // Re-filter vehicle list
    filterEditVehiclePicker();
}

function pickEditVehicle(id) {
    document.getElementById('edit-field-vehicle-id').value = id;
    document.getElementById('edit-vp-panel').classList.add('hidden');

    const thumb = document.getElementById('edit-vp-thumb');
    const label = document.getElementById('edit-vp-label');

    if (!id) {
        thumb.classList.add('hidden');
        label.className = 'min-w-0 flex-1 truncate text-gray-400';
        label.innerText = '-- Unassigned --';
        return;
    }

    const vehicle = vehiclesData.find(v => v.id == id);
    if (vehicle) {
        if (vehicle.photo_url) {
            thumb.innerHTML = `<img src="${vehicle.photo_url}" class="h-full w-full object-contain">`;
            thumb.classList.remove('hidden');
        } else {
            thumb.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>`;
            thumb.classList.remove('hidden');
        }
        label.className = 'min-w-0 flex-1 text-gray-900';
        label.innerHTML = `<span class="font-bold">${vehicle.plate_number}</span> <span class="text-xs text-gray-500">(${vehicle.brand} ${vehicle.model})</span>`;
    }
}

// Close panels when clicking outside
document.addEventListener('click', function(e) {
    const addWrap = document.getElementById('add-vehicle-picker');
    if (addWrap && !addWrap.contains(e.target)) {
        document.getElementById('add-vp-panel')?.classList.add('hidden');
    }
    const editWrap = document.getElementById('edit-vehicle-picker');
    if (editWrap && !editWrap.contains(e.target)) {
        document.getElementById('edit-vp-panel')?.classList.add('hidden');
    }
});

function openAddCardModal() {
    pickAddVehicle('');
    // Reset location dropdown for Super Admin
    const addLocSelect = document.getElementById('add-card-location');
    if (addLocSelect) {
        addLocSelect.value = '';
        filterAddVehiclePicker();
    }
    document.getElementById('add-card-modal').classList.remove('hidden');
    document.getElementById('add-card-modal').classList.add('flex');
}
function closeAddCardModal() {
    document.getElementById('add-card-modal').classList.add('hidden');
    document.getElementById('add-card-modal').classList.remove('flex');
}

function openEditCardModal(card) {
    document.getElementById('edit-card-form').action = '/fleet-cards/' + card.id;
    document.getElementById('edit-card-name').value = card.card_name;
    document.getElementById('edit-card-number').value = card.card_number;
    document.getElementById('edit-initial-budget').value = card.initial_budget;
    document.getElementById('edit-rate-peso').value = card.rate_peso;
    document.getElementById('edit-rate-km').value = card.rate_km;

    // Set location — dropdown for Super Admin, plain input for province admin
    const editLocSelect = document.getElementById('edit-card-location');
    const editLocInput = document.getElementById('edit-location');
    if (editLocSelect) {
        editLocSelect.value = card.location || '';
        filterEditVehiclePicker();
    } else if (editLocInput) {
        editLocInput.value = card.location || '';
    }

    pickEditVehicle(card.vehicle_id || '');

    document.getElementById('edit-card-modal').classList.remove('hidden');
    document.getElementById('edit-card-modal').classList.add('flex');
}

function closeEditCardModal() {
    document.getElementById('edit-card-modal').classList.add('hidden');
    document.getElementById('edit-card-modal').classList.remove('flex');
}

function openRechargeModal(card) {
    document.getElementById('recharge-form').action = '/fleet-cards/' + card.id + '/recharge';
    document.getElementById('recharge-modal').classList.remove('hidden');
    document.getElementById('recharge-modal').classList.add('flex');
}
function closeRechargeModal() {
    document.getElementById('recharge-modal').classList.add('hidden');
    document.getElementById('recharge-modal').classList.remove('flex');
}

function openExpenseModal(card) {
    currentActiveCard = card;
    document.getElementById('expense-form').action = '/fleet-cards/' + card.id + '/log-expense';
    document.getElementById('expense-card-name').innerText = card.card_name + ' (' + (card.vehicle ? card.vehicle.plate_number : 'Unassigned') + ')';
    
    // Reset fields
    document.getElementById('expense-start-odo').value = card.vehicle ? card.vehicle.odometer : '';
    document.getElementById('expense-end-odo').value = '';
    document.getElementById('expense-manual-km').value = '';
    document.getElementById('expense-dest-select').value = '';

    toggleExpenseSourceFields();
    updateLiveCalc();

    document.getElementById('expense-modal').classList.remove('hidden');
    document.getElementById('expense-modal').classList.add('flex');
}

function closeExpenseModal() {
    document.getElementById('expense-modal').classList.add('hidden');
    document.getElementById('expense-modal').classList.remove('flex');
}

function toggleExpenseSourceFields() {
    const source = document.getElementById('expense-source-select').value;
    document.getElementById('field-group-odometer').classList.toggle('hidden', source !== 'odometer');
    document.getElementById('field-group-destination').classList.toggle('hidden', source !== 'destination_master');
    document.getElementById('field-group-manual').classList.toggle('hidden', source !== 'manual');
    updateLiveCalc();
}

function onDestinationSelectChange() {
    const select = document.getElementById('expense-dest-select');
    const selectedOpt = select.options[select.selectedIndex];
    const dist = selectedOpt ? selectedOpt.getAttribute('data-distance') : 0;
    document.getElementById('expense-manual-km').value = dist || 0;
    updateLiveCalc();
}

function updateLiveCalc() {
    if (!currentActiveCard) return;

    const source = document.getElementById('expense-source-select').value;
    let distance = 0.0;

    if (source === 'odometer') {
        const start = parseFloat(document.getElementById('expense-start-odo').value) || 0;
        const end = parseFloat(document.getElementById('expense-end-odo').value) || 0;
        if (end > start) {
            distance = end - start;
        }
    } else if (source === 'destination_master') {
        const select = document.getElementById('expense-dest-select');
        const selectedOpt = select.options[select.selectedIndex];
        distance = parseFloat(selectedOpt ? selectedOpt.getAttribute('data-distance') : 0) || 0;
    } else {
        distance = parseFloat(document.getElementById('expense-manual-km').value) || 0;
    }

    const ratePeso = parseFloat(currentActiveCard.rate_peso) || 0;
    const rateKm = parseFloat(currentActiveCard.rate_km) || 1;
    const costPerKm = rateKm > 0 ? (ratePeso / rateKm) : 0;

    const consumed = distance * costPerKm;
    const currentBal = parseFloat(currentActiveCard.current_balance) || 0;
    const newBal = Math.max(0, currentBal - consumed);

    document.getElementById('live-rate-badge').innerText = '₱' + costPerKm.toFixed(2) + ' / km';
    document.getElementById('live-distance').innerText = distance.toFixed(1) + ' km';
    document.getElementById('live-consumed').innerText = '₱' + consumed.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('live-current-balance').innerText = '₱' + currentBal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('live-new-balance').innerText = '₱' + newBal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
@endsection
