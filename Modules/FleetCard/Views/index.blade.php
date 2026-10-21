@extends('layouts.app')
@section('title', 'Fleet Cards')

@php
    $isSuperAdmin = auth()->user()?->role?->value === 'super_admin';
    $locations = \App\Support\FleetLocations::ALL;
    $userLocation = auth()->user()?->location ?? 'Palawan';
@endphp

@section('content')
<div class="space-y-4">
    {{-- Header & Actions (Compact) --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center justify-center rounded-xl bg-emerald-100 p-2 text-emerald-700 shadow-2xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="14" x="2" y="5" rx="2"/>
                    <line x1="2" x2="22" y1="10" y2="10"/>
                </svg>
            </span>
            <div>
                <h1 class="text-lg font-black text-gray-900 tracking-tight sm:text-xl">Fleet Cards &amp; Fuel Budget</h1>
                <p class="text-[0.6875rem] text-gray-500">Track fuel cards, consumption rates, and trip deductions.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($isSuperAdmin)
                {{-- Location filter (Super Admin - Compact) --}}
                @php
                    $currentLocation = request('location');
                @endphp
                <div class="relative shrink-0">
                    <button type="button" onclick="toggleFleetCardLocationMenu()" class="inline-flex h-8 items-center justify-between gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 shadow-2xs cursor-pointer">
                        <span class="flex items-center gap-1.5 truncate">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span class="max-w-[12rem] truncate">{{ $currentLocation ?: 'All Locations' }}</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 text-slate-400 transition" id="fleetcard-location-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <div id="fleetcard-location-menu" class="absolute left-0 sm:left-auto sm:right-0 z-30 mt-1 hidden w-56 overflow-hidden rounded-xl bg-white p-1 shadow-xl ring-1 ring-black/5">
                        <a href="{{ route('fleet-cards.index') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ empty($currentLocation) ? 'bg-emerald-50 text-emerald-800 font-bold' : '' }}">
                            <span>All Locations</span>
                            @if(empty($currentLocation))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </a>
                        @foreach ($locations as $loc)
                            @php $isCurrent = $loc === $currentLocation; @endphp
                            <a href="{{ route('fleet-cards.index', ['location' => $loc]) }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ $isCurrent ? 'bg-emerald-50 text-emerald-800 font-bold' : '' }}">
                                <span class="truncate">{{ $loc }}</span>
                                @if($isCurrent)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <script>
                    function toggleFleetCardLocationMenu() {
                        const menu = document.getElementById('fleetcard-location-menu');
                        const chevron = document.getElementById('fleetcard-location-chevron');
                        const willOpen = menu.classList.contains('hidden');
                        menu.classList.toggle('hidden', !willOpen);
                        chevron.classList.toggle('rotate-180', willOpen);
                    }

                    document.addEventListener('click', function (e) {
                        const menu = document.getElementById('fleetcard-location-menu');
                        if (menu && !menu.classList.contains('hidden') && !menu.contains(e.target) && !e.target.closest('button[onclick="toggleFleetCardLocationMenu()"]')) {
                            menu.classList.add('hidden');
                            document.getElementById('fleetcard-location-chevron')?.classList.remove('rotate-180');
                        }
                    });
                </script>
            @endif

            <a href="{{ route('fleet-cards.monthly-spend', array_filter(['location' => request('location')])) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
                Monthly Spend
            </a>
            <button type="button" onclick="openAddCardModal()" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-[#0f5d33] px-3 text-xs font-bold text-white shadow-2xs transition hover:bg-[#0b4726] cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="M12 5v14"/>
                </svg>
                Register Fleet Card
            </button>
        </div>
    </div>

    <datalist id="fleet-card-products">
        @foreach (\Modules\FleetCard\Models\FleetCard::PRODUCTS as $product)
            <option value="{{ $product }}">
        @endforeach
    </datalist>

    @if($overAllowance->isNotEmpty())
        <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            <p class="font-bold">Over the monthly allowance in {{ now()->format('F') }}</p>
            <ul class="mt-1 space-y-0.5 text-xs">
                @foreach($overAllowance as $over)
                    <li>{{ $over['label'] }} ({{ $over['provider'] }}): ₱{{ number_format($over['spent'], 2) }} spent of ₱{{ number_format($over['allowance'], 2) }}</li>
                @endforeach
            </ul>
            <a href="{{ route('fleet-cards.monthly-spend', array_filter(['location' => request('location')])) }}" class="mt-1 inline-block text-xs font-bold text-amber-900 underline">See monthly spend</a>
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Summary Cards (Compact) --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200/80 bg-white p-3.5 shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[0.6875rem] font-bold uppercase tracking-wider text-gray-500">Total Allocated Budget</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 text-xs shadow-2xs">
                    <x-icon name="card" class="inline-block h-3.5 w-3.5 align-[-0.2em]" />                 </span>
            </div>
            <p class="mt-1.5 text-lg font-black text-gray-900">₱{{ number_format($totalInitialBudget, 2) }}</p>
            <p class="text-[0.625rem] text-gray-400">Total registered cards budget</p>
        </div>

        <div class="rounded-xl border border-gray-200/80 bg-white p-3.5 shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[0.6875rem] font-bold uppercase tracking-wider text-gray-500">Total Expense Consumed</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600 text-xs shadow-2xs">
                    <x-icon name="fuel" class="inline-block h-3.5 w-3.5 align-[-0.2em]" />                 </span>
            </div>
            <p class="mt-1.5 text-lg font-black text-amber-700">₱{{ number_format($totalConsumed, 2) }}</p>
            <p class="text-[0.625rem] text-gray-400">Total fuel &amp; service deductions</p>
        </div>

        <div class="rounded-xl border border-gray-200/80 bg-white p-3.5 shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[0.6875rem] font-bold uppercase tracking-wider text-gray-500">Total Remaining Balance</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 text-xs shadow-2xs">
                    <x-icon name="check-circle" class="inline-block h-3.5 w-3.5 align-[-0.2em]" />                 </span>
            </div>
            <p class="mt-1.5 text-lg font-black text-emerald-700">₱{{ number_format($totalCurrentBalance, 2) }}</p>
            <p class="text-[0.625rem] text-gray-400">Total active funds available</p>
        </div>
    </div>

    {{-- Cards Grid (Compact) --}}
    <div class="space-y-2.5">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                <span>Active Fleet Cards</span>
                <span class="rounded-full bg-emerald-100 px-2 py-px text-[0.625rem] font-bold text-emerald-800">{{ $cards->count() }}</span>
            </h2>
        </div>

        @if($cards->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-2">
                    <x-icon name="card" class="inline-block h-3.5 w-3.5 align-[-0.2em]" />                 </div>
                <h3 class="text-xs font-bold text-gray-800">No Fleet Cards Registered Yet</h3>
                <p class="mt-0.5 text-[0.6875rem] text-gray-500 max-w-sm mx-auto">Add a fleet card assigned to a vehicle with budget and price per liter.</p>
                <button type="button" onclick="openAddCardModal()" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-[#0f5d33] px-3 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-[#0b4726]">
                    + Add First Fleet Card
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($cards as $card)
                    @php
                        $pct = $card->balance_percentage;
                        $barColor = $pct > 50 ? 'bg-emerald-500' : ($pct > 20 ? 'bg-amber-500' : 'bg-red-500');
                        $badgeColor = $pct > 50 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($pct > 20 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-red-50 text-red-700 border-red-200');
                    @endphp
                    <div class="rounded-xl border border-gray-200/90 bg-white p-3.5 shadow-2xs transition hover:shadow-xs space-y-3">
                        {{-- Top row --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="font-extrabold text-gray-900 text-sm truncate">{{ $card->card_name }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[0.5625rem] font-mono font-bold text-slate-600 border border-slate-200 shrink-0">{{ $card->card_number }}</span>
                                </div>
                                <p class="text-[0.6875rem] text-gray-500 mt-0.5 truncate">
                                    Assigned: 
                                    @if($card->vehicle)
                                        <span class="font-bold text-emerald-800">{{ $card->vehicle->plate_number }}</span> <span class="text-gray-400">({{ $card->vehicle->brand }} {{ $card->vehicle->model }})</span>
                                    @else
                                        <span class="text-gray-400 italic">Unassigned</span>
                                    @endif
                                    • <span class="font-semibold text-gray-600"><x-icon name="pin" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> {{ $card->location }}</span>
                                </p>
                                @if($card->provider || $card->office)
                                    <p class="text-[0.6875rem] text-gray-500 truncate">
                                        {{ collect([$card->provider, $card->office?->name, $card->allowed_products])->filter()->implode(' · ') }}
                                    </p>
                                @endif
                                @if($card->monthly_allowance)
                                    @php
                                        $spentThisMonth = (float) $card->logs
                                            ->filter(fn ($log) => (float) $log->consumed_amount > 0 && $log->created_at?->isSameMonth(now()))
                                            ->sum('consumed_amount');
                                    @endphp
                                    <p class="text-[0.6875rem] {{ $spentThisMonth > (float) $card->monthly_allowance ? 'font-bold text-amber-700' : 'text-gray-500' }}">
                                        {{ now()->format('F') }}: ₱{{ number_format($spentThisMonth, 2) }} of ₱{{ number_format((float) $card->monthly_allowance, 2) }} allowance
                                    </p>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-full border px-2 py-0.5 text-[0.625rem] font-bold {{ $badgeColor }}">
                                {{ $pct }}% Balance
                            </span>
                        </div>

                        {{-- Budget Progress --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[0.6875rem]">
                                <span class="font-bold text-gray-700">Remaining: <span class="text-emerald-700 font-extrabold text-xs">₱{{ number_format($card->current_balance, 2) }}</span></span>
                                <span class="text-gray-400 font-medium">Budget: ₱{{ number_format($card->initial_budget, 2) }}</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ min(100, max(0, $pct)) }}%"></div>
                            </div>
                        </div>

                        {{-- Rate & Capacity Info Box (Compact Grid) --}}
                        <div class="grid grid-cols-2 gap-2 rounded-lg bg-gray-50/80 p-2 text-[0.6875rem] border border-gray-100">
                            <div>
                                <span class="text-gray-400 text-[0.625rem] block">Fuel Price:</span>
                                <p class="font-extrabold text-gray-800 text-xs">
                                    ₱{{ number_format($card->rate_peso, 2) }}
                                    <span class="text-[0.625rem] font-semibold text-emerald-700 font-normal">/ Liter</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-[0.625rem] block">Est. Fuel Capacity:</span>
                                <p class="font-extrabold text-gray-800 text-xs">
                                    ~{{ $card->rate_peso > 0 ? number_format((float)$card->current_balance / (float)$card->rate_peso, 1) : 0 }} L
                                    <span class="text-[0.625rem] text-gray-400 font-normal block truncate">Available capacity</span>
                                </p>
                            </div>
                        </div>

                        {{-- Actions (Compact) --}}
                        <div class="flex flex-wrap items-center justify-between gap-1.5 pt-2 border-t border-gray-100">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button type="button" onclick="openExpenseModal({{ json_encode($card) }})" class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2 py-1 text-[0.6875rem] font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-2xs">
                                    <x-icon name="fuel" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> Compute
                                </button>
                                <button type="button" onclick="openRechargeModal({{ json_encode($card) }})" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-[0.6875rem] font-bold text-blue-700 hover:bg-blue-100 transition border border-blue-200 cursor-pointer">
                                    <x-icon name="card" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> Top Up
                                </button>
                                <button type="button" onclick="filterHistoryByCard('{{ $card->id }}', @js($card->card_name))" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[0.6875rem] font-bold text-slate-700 hover:bg-slate-200 transition border border-slate-200 cursor-pointer" title="View transaction history for this card">
                                    <x-icon name="history" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> History
                                </button>
                            </div>
                            <div class="flex items-center gap-0.5">
                                <button type="button" onclick="openEditCardModal({{ json_encode($card) }})" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition cursor-pointer" title="Edit Parameters" aria-label="Edit fleet card">
                                    <x-icon name="pencil" class="inline-block h-3.5 w-3.5 align-[-0.2em]" />                                 </button>
                                <form action="{{ route('fleet-cards.destroy', $card->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this fleet card?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 transition cursor-pointer" title="Delete Card" aria-label="Delete fleet card">
                                        <x-icon name="trash" class="inline-block h-3.5 w-3.5 align-[-0.2em]" />                                     </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- CarMind-style Expense & Transaction History with Monthly Grouping & Category Filters --}}
    @php
        $groupedLogs = $recentLogs->groupBy(fn ($log) => $log->created_at->format('F Y'));
    @endphp

    <div class="rounded-xl border border-gray-200/90 bg-white p-3.5 shadow-2xs space-y-3" x-data="{ activeCategory: 'all' }" id="history-section">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-2.5">
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 flex items-center gap-1.5">
                    <span>Expenses &amp; Transaction History</span>
                    <span class="rounded-full bg-slate-100 px-2 py-px text-[0.625rem] font-bold text-slate-600">{{ $recentLogs->count() }} entries</span>
                </h3>
                <p class="text-[0.625rem] text-gray-400">Track fuel and service deductions grouped by month.</p>
                {{-- Active card filter badge --}}
                <div id="history-card-filter-badge" class="hidden mt-1 flex items-center gap-1.5">
                    <span class="text-[0.625rem] text-emerald-700 font-bold" id="history-card-filter-label"></span>
                    <button type="button" onclick="clearHistoryCardFilter()" class="text-[0.5625rem] text-red-500 hover:text-red-700 font-bold underline cursor-pointer">✕ Clear filter</button>
                </div>
            </div>

            {{-- Category Filter Pills (Compact) --}}
            <div class="flex flex-wrap items-center gap-1">
                <button type="button" @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-[#005a36] text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="rounded-full px-2.5 py-0.5 text-[0.6875rem] font-bold transition cursor-pointer">
                    All
                </button>
                <button type="button" @click="activeCategory = 'fuel'" :class="activeCategory === 'fuel' ? 'bg-[#005a36] text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="rounded-full px-2.5 py-0.5 text-[0.6875rem] font-bold transition cursor-pointer">
                    <x-icon name="fuel" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> Fuel
                </button>
                <button type="button" @click="activeCategory = 'service'" :class="activeCategory === 'service' ? 'bg-[#005a36] text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="rounded-full px-2.5 py-0.5 text-[0.6875rem] font-bold transition cursor-pointer">
                    <x-icon name="wrench" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> Service
                </button>
                <button type="button" @click="activeCategory = 'parking'" :class="activeCategory === 'parking' ? 'bg-[#005a36] text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="rounded-full px-2.5 py-0.5 text-[0.6875rem] font-bold transition cursor-pointer">
                    🅿️ Parking
                </button>
                <button type="button" @click="activeCategory = 'toll'" :class="activeCategory === 'toll' ? 'bg-[#005a36] text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="rounded-full px-2.5 py-0.5 text-[0.6875rem] font-bold transition cursor-pointer">
                    <x-icon name="road" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> Toll
                </button>
                <button type="button" @click="activeCategory = 'topup'" :class="activeCategory === 'topup' ? 'bg-[#005a36] text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="rounded-full px-2.5 py-0.5 text-[0.6875rem] font-bold transition cursor-pointer">
                    <x-icon name="card" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> Top-ups
                </button>
            </div>
        </div>

        @if($recentLogs->isEmpty())
            <p class="text-xs text-gray-400 text-center py-6">No transaction logs recorded yet.</p>
        @else
            <div class="space-y-4">
                @foreach ($groupedLogs as $monthYear => $monthLogs)
                    @php
                        $monthExpenses = $monthLogs->where('consumed_amount', '>', 0)->sum('consumed_amount');
                    @endphp
                    <div class="space-y-1.5">
                        {{-- Monthly Header & Running Total --}}
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5 border border-slate-200/80">
                            <span class="text-[0.6875rem] font-black uppercase tracking-wider text-slate-700">{{ $monthYear }}</span>
                            <span class="text-[0.6875rem] font-extrabold text-slate-900">
                                Total: <strong class="text-amber-800">₱{{ number_format($monthExpenses, 2) }}</strong>
                            </span>
                        </div>

                        {{-- Month Transactions Table --}}
                        <div class="overflow-x-auto rounded-lg border border-gray-100">
                            <table class="w-full text-left text-[0.6875rem] text-gray-600">
                                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider text-[0.5625rem]">
                                    <tr>
                                        <th class="px-2.5 py-1.5">Date</th>
                                        <th class="px-2.5 py-1.5">Category</th>
                                        <th class="px-2.5 py-1.5">Card / Vehicle</th>
                                        <th class="px-2.5 py-1.5">Method</th>
                                        <th class="px-2.5 py-1.5">Distance</th>
                                        <th class="px-2.5 py-1.5">Liters Used</th>
                                        <th class="px-2.5 py-1.5">Price/Liter</th>
                                        <th class="px-2.5 py-1.5">Amount</th>
                                        <th class="px-2.5 py-1.5">New Balance</th>
                                        <th class="px-2.5 py-1.5">Remarks</th>
                                        <th class="px-2.5 py-1.5 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 font-medium">
                                    @foreach ($monthLogs as $log)
                                        @php
                                            $cat = $log->category ?: ($log->distance_source === 'recharge' ? 'topup' : 'fuel');
                                            $catBadge = match($cat) {
                                                'fuel'    => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                'service' => 'bg-blue-50 text-blue-800 border-blue-200',
                                                'parking' => 'bg-purple-50 text-purple-800 border-purple-200',
                                                'toll'    => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                                'topup'   => 'bg-sky-50 text-sky-800 border-sky-200',
                                                default   => 'bg-slate-50 text-slate-700 border-slate-200',
                                            };
                                            $catLabel = match($cat) {
                                                'fuel'    => 'Fuel',
                                                'service' => 'Service',
                                                'parking' => '🅿️ Parking',
                                                'toll'    => 'Toll',
                                                'topup'   => 'Top-up',
                                                default   => 'Other',
                                            };
                                            $isExpense = $log->distance_source !== 'recharge' && $log->consumed_amount > 0;
                                        @endphp
                                        <tr class="hover:bg-gray-50/60 transition history-log-row"
                                            data-card-id="{{ $log->fleet_card_id }}"
                                            x-show="activeCategory === 'all' || activeCategory === '{{ $cat }}'">
                                            <td class="px-2.5 py-1.5 text-gray-400 whitespace-nowrap">{{ $log->created_at->format('M d, g:i A') }}</td>
                                            <td class="px-2.5 py-1.5 whitespace-nowrap">
                                                <span class="rounded-full px-2 py-px text-[0.5625rem] font-bold border {{ $catBadge }}">
                                                    {{ $catLabel }}
                                                </span>
                                            </td>
                                            <td class="px-2.5 py-1.5 font-bold text-gray-900">
                                                {{ $log->fleetCard?->card_name ?? 'N/A' }}
                                                @if($log->vehicle)
                                                    <span class="text-emerald-700 font-semibold block text-[0.625rem]">({{ $log->vehicle->plate_number }})</span>
                                                @endif
                                            </td>
                                            <td class="px-2.5 py-1.5">
                                                @if($log->distance_source === 'odometer')
                                                    <span class="rounded bg-blue-50 px-1 py-px text-[0.5625rem] font-bold text-blue-700 border border-blue-100">Odometer</span>
                                                @elseif($log->distance_source === 'destination_master')
                                                    <span class="rounded bg-purple-50 px-1 py-px text-[0.5625rem] font-bold text-purple-700 border border-purple-100">Trip Ticket</span>
                                                @elseif($log->distance_source === 'recharge')
                                                    <span class="rounded bg-emerald-50 px-1 py-px text-[0.5625rem] font-bold text-emerald-700 border border-emerald-100">Top-up</span>
                                                @else
                                                    <span class="rounded bg-gray-100 px-1 py-px text-[0.5625rem] font-bold text-gray-700 border border-gray-200">Manual</span>
                                                @endif
                                            </td>
                                            {{-- Distance column --}}
                                            <td class="px-2.5 py-1.5 font-bold text-gray-800 whitespace-nowrap">
                                                @if($log->distance_source !== 'recharge' && $log->distance_km > 0)
                                                    {{ number_format($log->distance_km, 1) }} km
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </td>
                                            {{-- Liters Used column --}}
                                            <td class="px-2.5 py-1.5 whitespace-nowrap">
                                                @if($log->liters_used)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-1.5 py-px text-[0.5625rem] font-black text-amber-800">
                                                        <x-icon name="fuel" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> {{ number_format($log->liters_used, 2) }} L
                                                    </span>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </td>
                                            {{-- Price/Liter column --}}
                                            <td class="px-2.5 py-1.5 whitespace-nowrap">
                                                @if($log->fuel_price_per_liter)
                                                    <span class="text-slate-600 font-bold">₱{{ number_format($log->fuel_price_per_liter, 2) }}</span>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </td>
                                            <td class="px-2.5 py-1.5 font-black whitespace-nowrap {{ $log->consumed_amount < 0 ? 'text-emerald-600' : 'text-amber-800' }}">
                                                {{ $log->consumed_amount < 0 ? '+₱' . number_format(abs($log->consumed_amount), 2) : '-₱' . number_format($log->consumed_amount, 2) }}
                                            </td>
                                            <td class="px-2.5 py-1.5 font-bold text-gray-900">₱{{ number_format($log->balance_after, 2) }}</td>
                                            <td class="px-2.5 py-1.5 text-gray-500 max-w-[150px] truncate" title="{{ $log->remarks }}">{{ $log->remarks ?: '—' }}</td>
                                            {{-- Actions column --}}
                                            <td class="px-2.5 py-1.5 whitespace-nowrap">
                                                <div class="flex items-center justify-center gap-1">
                                                    {{-- View --}}
                                                    <button type="button"
                                                        onclick="viewLogDetail({{ json_encode($log) }})"
                                                        class="rounded p-1 text-blue-500 hover:bg-blue-50 hover:text-blue-700 transition cursor-pointer"
                                                        title="View details" aria-label="View details">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    </button>
                                                    {{-- Edit Remarks --}}
                                                    <button type="button"
                                                        onclick="editLogRemarks({{ $log->id }}, '{{ $log->fleetCard?->id }}', @js($log->remarks ?? ''))"
                                                        class="rounded p-1 text-amber-500 hover:bg-amber-50 hover:text-amber-700 transition cursor-pointer"
                                                        title="Edit remarks" aria-label="Edit remarks">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    </button>
                                                    {{-- Delete (expense only) --}}
                                                    @if($isExpense)
                                                        <button type="button"
                                                            onclick="deleteExpenseLog({{ $log->fleetCard?->id }}, {{ $log->id }}, {{ $log->consumed_amount }})"
                                                            class="rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-700 transition cursor-pointer"
                                                            title="Delete & restore balance" aria-label="Delete expense and restore balance">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Log Detail Modal --}}
<div id="log-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="log-detail-title" class="w-full max-w-md max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 id="log-detail-title" class="text-base font-extrabold text-gray-900">Log Entry Details</h3>
            <button type="button" onclick="closeLogDetailModal()" aria-label="Close" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div id="log-detail-body" class="space-y-2 text-xs text-gray-700"></div>
    </div>
</div>

{{-- Edit Log Remarks Modal --}}
<div id="edit-log-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="edit-log-title" class="w-full max-w-sm max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 id="edit-log-title" class="text-base font-extrabold text-gray-900">Edit Log Remarks</h3>
            <button type="button" onclick="closeEditLogModal()" aria-label="Close" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form id="edit-log-form" method="POST" class="space-y-3" onsubmit="this.querySelector('[type=submit]').disabled = true">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Remarks / Notes</label>
                <textarea id="edit-log-remarks-input" name="remarks" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none" placeholder="Enter remarks..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="closeEditLogModal()" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-white hover:bg-amber-600">Save Remarks</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 1: Add Fleet Card --}}
<div id="add-card-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="add-card-title" class="w-full max-w-md max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 id="add-card-title" class="text-base font-extrabold text-gray-900">Register New Fleet Card</h3>
            <button type="button" onclick="closeAddCardModal()" aria-label="Close" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form action="{{ route('fleet-cards.store') }}" method="POST" class="space-y-3" onsubmit="this.querySelector('[type=submit]').disabled = true">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Fleet Card Name / Provider</label>
                <input type="text" name="card_name" placeholder="e.g. Petron Fleet Card Palawan #1" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                                    <span class="block truncate text-[0.6875rem] text-gray-500">{{ trim($v->brand.' '.$v->model) }} @if($v->location) &middot; {{ $v->location }} @endif</span>
                                </span>
                            </button>
                        @endforeach
                        <p id="add-vp-empty" class="hidden px-3 py-3 text-center text-xs text-gray-400">No vehicles found.</p>
                    </div>
                </div>
            </div>

            @include('fleetcard::partials.card-gss-fields', ['prefix' => 'add'])

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Initial Budget Allocated (₱)</label>
                <input type="number" step="0.01" name="initial_budget" value="5000.00" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-bold text-emerald-800 focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="rounded-xl bg-amber-50/60 p-3 border border-amber-200/60 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-amber-900">Fuel Price Rule</label>
                    <span class="text-[0.625rem] text-amber-700 font-semibold">(Default fuel price per liter)</span>
                </div>
                <p class="text-[0.6875rem] text-amber-700">Specify the default price per liter for this fleet card (used to auto-calculate trip fuel costs).</p>
                <div>
                    <span class="text-[0.625rem] font-bold text-gray-500 block mb-1">Price per Liter</span>
                    <div class="flex items-center rounded-full border border-emerald-500 bg-white overflow-hidden shadow-2xs focus-within:ring-2 focus-within:ring-emerald-500/20 max-w-sm">
                        <span class="border-r border-slate-200 bg-slate-50/80 px-3 py-1.5 text-xs font-bold text-slate-600 select-none shrink-0">
                            ₱
                        </span>
                        <input
                            type="number"
                            step="0.01"
                            name="rate_peso"
                            value="68.50"
                            required
                            class="w-full bg-transparent px-3 py-1.5 text-xs font-black text-slate-900 placeholder:text-slate-400 focus:outline-none"
                        >
                        <span class="border-l border-slate-200 bg-slate-50/80 px-3 py-1.5 text-[0.6875rem] font-bold text-slate-500 select-none shrink-0">
                            / Liter
                        </span>
                    </div>
                    {{-- Hidden rate_km defaulting to 1 so rate_peso is rate per unit --}}
                    <input type="hidden" name="rate_km" value="1.00">
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
    <div role="dialog" aria-modal="true" aria-labelledby="expense-card-title" class="w-full max-w-lg max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div>
                <h3 class="text-base font-extrabold text-gray-900" id="expense-card-title">Compute Trip Fuel Expense</h3>
                <p class="text-xs text-gray-500" id="expense-card-sub">Fleet Card: <span id="expense-card-name" class="font-bold text-emerald-800"></span></p>
            </div>
            <button type="button" onclick="closeExpenseModal()" aria-label="Close" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="expense-form" method="POST" class="space-y-3" onsubmit="this.querySelector('[type=submit]').disabled = true">
            @csrf
            
            {{-- Expense Category Selection (CarMind style) --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Expense Category</label>
                <select name="category" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold focus:border-emerald-500 focus:outline-none">
                    <option value="fuel">Fuel (Gasoline / Diesel)</option>
                    <option value="service">PMS / Service</option>
                    <option value="parking">🅿️ Parking Fee</option>
                    <option value="toll">Toll Fee</option>
                    <option value="other">Other Expense</option>
                </select>
            </div>

            {{-- Distance Source Selection --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Select Distance Method</label>
                <select id="expense-source-select" name="distance_source" onchange="toggleExpenseSourceFields()" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold focus:border-emerald-500 focus:outline-none">
                    <option value="destination_master" selected>Option 2: Base on Trip Ticket</option>
                    <option value="odometer">Option 1: Base on Odometer Reading (Start → End)</option>
                    <option value="manual">Option 3: Manual Distance Input (km)</option>
                </select>
            </div>

            {{-- Fields for Option 1: Odometer --}}
            <div id="field-group-odometer" class="hidden grid grid-cols-1 gap-3 sm:grid-cols-2 rounded-xl bg-blue-50/60 p-3 border border-blue-200/60">
                <div>
                    <label class="block text-[0.6875rem] font-bold text-blue-900 mb-1">Start Odometer</label>
                    <div class="flex items-center rounded-full border border-emerald-500 bg-white overflow-hidden shadow-2xs focus-within:ring-2 focus-within:ring-emerald-500/20">
                        <input
                            type="number"
                            id="expense-start-odo"
                            name="start_odometer"
                            placeholder="e.g. 10000"
                            oninput="updateLiveCalc()"
                            class="w-full bg-transparent px-3 py-1.5 text-xs font-black text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none"
                        >
                        <span class="border-l border-slate-200 bg-slate-50/80 px-3 py-1.5 text-[0.6875rem] font-bold text-slate-500 select-none shrink-0">
                            km
                        </span>
                    </div>
                </div>
                <div>
                    <label class="block text-[0.6875rem] font-bold text-blue-900 mb-1">End Odometer</label>
                    <div class="flex items-center rounded-full border border-emerald-500 bg-white overflow-hidden shadow-2xs focus-within:ring-2 focus-within:ring-emerald-500/20">
                        <input
                            type="number"
                            id="expense-end-odo"
                            name="end_odometer"
                            placeholder="e.g. 10150"
                            oninput="updateLiveCalc()"
                            class="w-full bg-transparent px-3 py-1.5 text-xs font-black text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none"
                        >
                        <span class="border-l border-slate-200 bg-slate-50/80 px-3 py-1.5 text-[0.6875rem] font-bold text-slate-500 select-none shrink-0">
                            km
                        </span>
                    </div>
                </div>
            </div>

            {{-- Fields for Option 2: Base on Trip Ticket (Searchable Dropdown) --}}
            <div id="field-group-destination" class="space-y-2 rounded-xl bg-purple-50/60 p-3 border border-purple-200/60">
                <label class="block text-xs font-bold text-purple-900">Select Trip Ticket</label>
                
                {{-- Hidden input for vehicle_request_id --}}
                <input type="hidden" id="expense-dest-select" name="vehicle_request_id" value="">
                
                {{-- Custom Searchable Picker --}}
                <div class="relative" id="trip-ticket-picker">
                    {{-- Picker Trigger Button --}}
                    <button
                        type="button"
                        id="tt-picker-btn"
                        onclick="toggleTripTicketPicker()"
                        class="flex w-full items-center justify-between rounded-xl border border-purple-300 bg-white px-3 py-2 text-left text-xs font-semibold text-slate-800 shadow-2xs hover:bg-slate-50 transition cursor-pointer"
                    >
                        <span id="tt-picker-label" class="truncate text-slate-400">-- Pick Trip Ticket / Destination --</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400 transition" id="tt-picker-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>

                    {{-- Dropdown Panel with Search Box (like image 4) --}}
                    <div id="tt-picker-panel" class="absolute left-0 right-0 z-30 mt-1 hidden max-h-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl flex flex-col">
                        {{-- Search Input Box --}}
                        <div class="p-2 border-b border-slate-100 bg-slate-50">
                            <div class="flex items-center rounded-full border border-emerald-500 bg-white overflow-hidden shadow-2xs px-2.5 py-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <input
                                    type="text"
                                    id="tt-search-input"
                                    placeholder="Search Trip Ticket No., Ref Code, Destination..."
                                    oninput="filterTripTicketOptions()"
                                    class="w-full bg-transparent text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none"
                                >
                            </div>
                        </div>

                        {{-- Options List --}}
                        <div id="tt-options-list" class="overflow-y-auto max-h-56 divide-y divide-slate-100 p-1">
                            @foreach($pendingRequests as $r)
                                @php
                                    $ticketNum = $r->trip_ticket_number ?: $r->reference_code;
                                    $dist = 0;
                                    $fuelUsed = 0;
                                    if ($r->completion) {
                                        $dist = (float) ($r->completion->distance_travelled ?? 0);
                                        $fuelUsed = (float) ($r->completion->fuel_used_liters ?? 0);
                                    } elseif ((float)$r->distance_km > 0) {
                                        $dist = (float)$r->distance_km;
                                    }
                                    $destDisplay = str_replace(';;', ', ', (string) $r->destination);
                                    $searchText = strtolower("{$r->trip_ticket_number} {$r->reference_code} {$destDisplay} {$r->requester_name}");
                                    $labelDisplay = $ticketNum . ' — ' . $destDisplay . ($fuelUsed > 0 ? " ({$fuelUsed} L used)" : ($dist > 0 ? " ({$dist} km)" : ''));
                                @endphp
                                <button
                                    type="button"
                                    onclick="pickTripTicket('{{ $r->id }}', @js($labelDisplay), {{ $dist }}, {{ $fuelUsed }})"
                                    class="tt-option-item w-full text-left px-3 py-2 rounded-lg hover:bg-emerald-50 transition cursor-pointer flex flex-col gap-0.5"
                                    data-search="{{ $searchText }}"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5 truncate">
                                            @if($r->trip_ticket_number)
                                                <span class="font-mono font-extrabold text-[0.6875rem] text-emerald-900 bg-emerald-100 px-1.5 py-px rounded">{{ $r->trip_ticket_number }}</span>
                                            @endif
                                            <span class="font-bold text-xs text-slate-900 truncate">{{ $r->reference_code }}</span>
                                        </div>
                                        @if($fuelUsed > 0)
                                            <span class="font-black text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full shrink-0">
                                                <x-icon name="fuel" class="inline-block h-3.5 w-3.5 align-[-0.2em]" /> {{ number_format($fuelUsed, 2) }} L
                                            </span>
                                        @else
                                            <span class="font-black text-xs text-emerald-700 shrink-0">{{ number_format($dist, 1) }} km</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between gap-2 text-[0.625rem] text-slate-500">
                                        <span class="truncate">{{ $destDisplay }}</span>
                                        @if($r->completion)
                                            <span class="text-blue-600 font-semibold shrink-0">✓ Completed Log ({{ number_format($dist, 1) }} km)</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                            <p id="tt-empty-state" class="hidden text-center py-4 text-xs text-slate-400">No matching Trip Tickets found.</p>
                        </div>
                    </div>
                </div>

                <p class="text-[0.6875rem] text-purple-700 italic">Fetches the actual Liters used from Part B completed trip log.</p>
            </div>

            {{-- Fields for Option 3: Manual Distance --}}
            <div id="field-group-manual" class="hidden space-y-1 rounded-xl bg-gray-50 p-3 border border-gray-200">
                <label class="block text-xs font-bold text-gray-700 mb-1">Distance Travelled</label>
                <div class="flex items-center rounded-full border border-emerald-500 bg-white overflow-hidden shadow-2xs focus-within:ring-2 focus-within:ring-emerald-500/20 max-w-xs">
                    <input
                        type="number"
                        step="0.1"
                        id="expense-manual-km"
                        name="distance_km"
                        placeholder="e.g. 75.5"
                        oninput="updateLiveCalc()"
                        class="w-full bg-transparent px-3 py-1.5 text-xs font-black text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none"
                    >
                    <span class="border-l border-slate-200 bg-slate-50/80 px-3 py-1.5 text-[0.6875rem] font-bold text-slate-500 select-none shrink-0">
                        km
                    </span>
                </div>
            </div>

            {{-- LIVE CALCULATION BOX --}}
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 space-y-3">
                <div class="flex items-center justify-between text-xs font-extrabold uppercase tracking-wider text-emerald-900 border-b border-emerald-200/80 pb-1.5">
                    <span>Live Computation Summary</span>
                    <span id="live-rate-badge" class="rounded bg-white px-2 py-0.5 text-[0.625rem] text-emerald-800 shadow-2xs">₱0/km</span>
                </div>

                {{-- Option 2 View: Liters Used + Price/Liter Input -> Fuel Expense Consumed --}}
                <div id="live-calc-trip-ticket-mode" class="hidden space-y-2.5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <span class="text-xs font-bold text-amber-900 block mb-1">Liters Used During Trip:</span>
                            <div class="flex items-center rounded-full border border-emerald-500 bg-amber-100/60 overflow-hidden shadow-2xs">
                                <span id="live-liters-used" class="w-full px-3 py-1.5 text-sm font-black text-amber-950 truncate">0.00</span>
                                <span class="border-l border-amber-200 bg-amber-200/70 px-3 py-1.5 text-xs font-black text-amber-900 select-none shrink-0">
                                    L
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-700 block mb-1">Fuel Price per Liter:</span>
                            <div class="flex items-center rounded-full border border-emerald-500 bg-white overflow-hidden shadow-2xs focus-within:ring-2 focus-within:ring-emerald-500/20">
                                <span class="border-r border-slate-200 bg-slate-50/80 px-3 py-1.5 text-xs font-bold text-slate-600 select-none shrink-0">
                                    ₱
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    id="expense-fuel-price-per-liter"
                                    name="fuel_price_per_liter"
                                    placeholder="e.g. 68.50"
                                    oninput="updateLiveCalc()"
                                    class="w-full bg-transparent px-3 py-1.5 text-xs font-black text-slate-900 placeholder:text-slate-400 focus:outline-none"
                                >
                                <span class="border-l border-slate-200 bg-slate-50/80 px-2.5 py-1.5 text-[0.625rem] font-bold text-slate-500 select-none shrink-0">
                                    price/liter
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Option 1 & 3 View: Computed Distance --}}
                <div id="live-calc-distance-mode" class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-emerald-700 font-medium">Computed Distance:</span>
                        <p class="font-extrabold text-emerald-950 text-base" id="live-distance">0.0 km</p>
                    </div>
                </div>

                {{-- Fuel Consumed and Balances Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2 border-t border-emerald-200/70 text-xs">
                    <div class="rounded-xl bg-white/80 border border-emerald-200/70 p-2.5">
                        <span class="text-emerald-700 font-bold block text-[0.6875rem]">Fuel Expense Consumed:</span>
                        <p class="font-black text-amber-800 text-base" id="live-consumed">₱0.00</p>
                    </div>
                    <div class="rounded-xl bg-white/80 border border-emerald-200/70 p-2.5">
                        <span class="text-emerald-700 font-bold block text-[0.6875rem]">Current Balance:</span>
                        <p class="font-bold text-emerald-900 text-sm" id="live-current-balance">₱0.00</p>
                    </div>
                    <div class="rounded-xl bg-emerald-100/70 border border-emerald-300 p-2.5">
                        <span class="text-emerald-800 font-bold block text-[0.6875rem]">Balance After Expense:</span>
                        <p class="font-extrabold text-emerald-950 text-base" id="live-new-balance">₱0.00</p>
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
    <div role="dialog" aria-modal="true" aria-labelledby="edit-card-title" class="w-full max-w-md max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 id="edit-card-title" class="text-base font-extrabold text-gray-900">Edit Fleet Card Parameters</h3>
            <button type="button" onclick="closeEditCardModal()" aria-label="Close" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="edit-card-form" method="POST" class="space-y-3" onsubmit="this.querySelector('[type=submit]').disabled = true">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Fleet Card Name</label>
                <input type="text" id="edit-card-name" name="card_name" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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

            {{-- READ-ONLY ASSIGNED VEHICLE DISPLAY FOR EDIT MODAL --}}
            <div id="edit-vehicle-picker">
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Assigned Vehicle
                    <span class="ml-1 text-[0.625rem] font-normal text-gray-400">(cannot be changed)</span>
                </label>

                {{-- Hidden input so vehicle_id is still submitted with the form --}}
                <input type="hidden" name="vehicle_id" id="edit-field-vehicle-id">

                {{-- Read-only display tile --}}
                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 cursor-not-allowed">
                    <span id="edit-vp-thumb" class="hidden h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-white"></span>
                    <span id="edit-vp-label" class="min-w-0 flex-1 truncate text-gray-400 text-xs">Unassigned</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="11" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
            </div>

            @include('fleetcard::partials.card-gss-fields', ['prefix' => 'edit'])

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Initial Budget Allocated (₱)</label>
                <input type="number" step="0.01" id="edit-initial-budget" name="initial_budget" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-bold text-emerald-800 focus:border-emerald-500 focus:outline-none" oninput="updateEditModalTotalBalance()">
            </div>

            {{-- TOP-UP HISTORY & TOTAL BALANCE SECTION --}}
            <div id="edit-topup-container" class="rounded-xl border border-blue-100 bg-blue-50/60 p-3 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-950 flex items-center gap-1.5">
                        <span>Top-Up Entries</span>
                    </span>
                    <span id="edit-topup-count" class="text-[0.625rem] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">0 entries</span>
                </div>

                {{-- List of Top-ups with Delete Button --}}
                <div id="edit-topup-list" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                    {{-- Dynamically populated via openEditCardModal --}}
                </div>

                {{-- Total Balance Summary --}}
                <div class="pt-2 border-t border-blue-200/80 flex items-center justify-between">
                    <span class="text-xs font-extrabold text-blue-950">Total Active Balance (Remaining):</span>
                    <span id="edit-modal-total-balance" class="text-sm font-black text-emerald-800">₱0.00</span>
                </div>
            </div>

            <div class="rounded-xl bg-amber-50/60 p-3 border border-amber-200/60 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-amber-900">Fuel Price Rule</label>
                    <span class="text-[0.625rem] text-amber-700 font-semibold">(Default fuel price per liter)</span>
                </div>
                <p class="text-[0.6875rem] text-amber-700">Specify the default price per liter for this fleet card (used to auto-calculate trip fuel costs).</p>
                <div>
                    <span class="text-[0.625rem] font-bold text-gray-500 block mb-1">Price per Liter</span>
                    <div class="flex items-center rounded-full border border-emerald-500 bg-white overflow-hidden shadow-2xs focus-within:ring-2 focus-within:ring-emerald-500/20 max-w-sm">
                        <span class="border-r border-slate-200 bg-slate-50/80 px-3 py-1.5 text-xs font-bold text-slate-600 select-none shrink-0">
                            ₱
                        </span>
                        <input
                            type="number"
                            step="0.01"
                            id="edit-rate-peso"
                            name="rate_peso"
                            required
                            class="w-full bg-transparent px-3 py-1.5 text-xs font-black text-slate-900 placeholder:text-slate-400 focus:outline-none"
                        >
                        <span class="border-l border-slate-200 bg-slate-50/80 px-3 py-1.5 text-[0.6875rem] font-bold text-slate-500 select-none shrink-0">
                            / Liter
                        </span>
                    </div>
                    {{-- Hidden rate_km defaulting to 1 so rate_peso is rate per unit --}}
                    <input type="hidden" id="edit-rate-km" name="rate_km" value="1.00">
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
    <div role="dialog" aria-modal="true" aria-labelledby="recharge-title" class="w-full max-w-sm max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 id="recharge-title" class="text-base font-extrabold text-gray-900">Top Up Fleet Card</h3>
            <button type="button" onclick="closeRechargeModal()" aria-label="Close" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="recharge-form" method="POST" class="space-y-3" onsubmit="this.querySelector('[type=submit]').disabled = true">
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

function escHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
}

// Escape closes whichever fleet-card modal is open
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    const modals = [
        ['log-detail-modal', closeLogDetailModal],
        ['edit-log-modal', closeEditLogModal],
        ['add-card-modal', closeAddCardModal],
        ['expense-modal', closeExpenseModal],
        ['edit-card-modal', closeEditCardModal],
        ['recharge-modal', closeRechargeModal],
    ];
    for (const [id, close] of modals) {
        const el = document.getElementById(id);
        if (el && !el.classList.contains('hidden')) {
            close();
        }
    }
});

// Re-enable submit buttons disabled by onsubmit when the page is restored from bfcache
window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
        document.querySelectorAll('#add-card-modal, #expense-modal, #edit-card-modal, #recharge-modal, #edit-log-modal')
            .forEach(modal => modal.querySelectorAll('[type=submit][disabled]').forEach(btn => { btn.disabled = false; }));
    }
});

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

// Starts the new card's monthly allowance from the amount listed on the vehicle (Property & Registration),
// unless the allowance was typed in by hand
function prefillAddAllowance(vehicle) {
    const allowance = document.getElementById('add-card-allowance');
    if (!allowance || (allowance.value && allowance.value !== allowance.dataset.fromVehicle)) {
        return;
    }
    const amount = vehicle && vehicle.fleet_card_allowance ? String(parseFloat(vehicle.fleet_card_allowance)) : '';
    allowance.value = amount;
    allowance.dataset.fromVehicle = amount;
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
        prefillAddAllowance(null);
        return;
    }

    const vehicle = vehiclesData.find(v => v.id == id);
    prefillAddAllowance(vehicle);
    if (vehicle) {
        if (vehicle.photo_url) {
            thumb.innerHTML = `<img src="${escHtml(vehicle.photo_url)}" alt="" class="h-full w-full object-contain">`;
            thumb.classList.remove('hidden');
        } else {
            thumb.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>`;
            thumb.classList.remove('hidden');
        }
        label.className = 'min-w-0 flex-1 text-gray-900';
        label.innerHTML = `<span class="font-bold">${escHtml(vehicle.plate_number)}</span> <span class="text-xs text-gray-500">(${escHtml(vehicle.brand)} ${escHtml(vehicle.model)})</span>`;
    }
}

// ── EDIT VEHICLE PICKER LOGIC ─────────────────────────────────────
function toggleEditVehiclePicker() {
    const panel = document.getElementById('edit-vp-panel');
    // Vehicle field is read-only in the edit modal; the picker panel/search no longer exist.
    if (!panel || !document.getElementById('edit-vp-search')) return;
    const isHidden = panel.classList.contains('hidden');
    panel.classList.toggle('hidden', !isHidden);
    if (isHidden) {
        document.getElementById('edit-vp-search').value = '';
        filterEditVehiclePicker();
        setTimeout(() => document.getElementById('edit-vp-search').focus(), 50);
    }
}

function filterEditVehiclePicker() {
    const searchEl = document.getElementById('edit-vp-search');
    // Vehicle field is read-only in the edit modal; nothing to filter.
    if (!searchEl) return;
    const q = (searchEl.value || '').toLowerCase().trim();
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
    document.getElementById('edit-vp-empty')?.classList.toggle('hidden', count > 0);
}

function onEditLocationChange(loc) {
    // Reset vehicle selection
    pickEditVehicle('');
    // Re-filter vehicle list
    filterEditVehiclePicker();
}

function pickEditVehicle(id) {
    document.getElementById('edit-field-vehicle-id').value = id;
    const panel = document.getElementById('edit-vp-panel');
    if (panel) panel.classList.add('hidden');

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
            thumb.innerHTML = `<img src="${escHtml(vehicle.photo_url)}" alt="" class="h-full w-full object-contain">`;
            thumb.classList.remove('hidden');
        } else {
            thumb.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>`;
            thumb.classList.remove('hidden');
        }
        label.className = 'min-w-0 flex-1 text-gray-900';
        label.innerHTML = `<span class="font-bold">${escHtml(vehicle.plate_number)}</span> <span class="text-xs text-gray-500">(${escHtml(vehicle.brand)} ${escHtml(vehicle.model)})</span>`;
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
    document.getElementById('edit-card-provider').value = card.provider || '';
    document.getElementById('edit-card-products').value = card.allowed_products || '';
    document.getElementById('edit-card-office').value = card.office_id || '';
    document.getElementById('edit-card-allowance').value = card.monthly_allowance || '';

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

    // Store card reference for live balance computation
    window.currentEditingCard = card;

    // Render Top-Up Entries with Delete Buttons
    const topupList = document.getElementById('edit-topup-list');
    const topupCount = document.getElementById('edit-topup-count');
    const topupContainer = document.getElementById('edit-topup-container');
    const logs = card.logs || [];
    const recharges = logs.filter(l => l.distance_source === 'recharge' || l.consumed_amount < 0);

    topupCount.textContent = `${recharges.length} ${recharges.length === 1 ? 'entry' : 'entries'}`;

    if (recharges.length === 0) {
        topupList.innerHTML = `<p class="text-[0.6875rem] text-gray-400 italic py-1">No top-ups recorded yet for this card.</p>`;
    } else {
        topupList.innerHTML = recharges.map(r => {
            const amount = Math.abs(parseFloat(r.consumed_amount) || 0);
            const dateStr = r.created_at ? new Date(r.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
            return `
                <div class="flex items-center justify-between rounded-lg bg-white p-2 border border-blue-200/80 shadow-xs">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-extrabold text-xs text-blue-900">+₱${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            <span class="text-[0.625rem] text-gray-400">${dateStr}</span>
                        </div>
                        <p class="text-[0.625rem] text-gray-500 truncate max-w-[200px]">${escHtml(r.remarks || 'Budget Top-up')}</p>
                    </div>
                    <button type="button" onclick="deleteTopUpEntry(${card.id}, ${r.id}, ${amount})" class="rounded-md p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700 transition cursor-pointer" title="Delete wrong top-up" aria-label="Delete top-up">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>
                        </svg>
                    </button>
                </div>
            `;
        }).join('');
    }

    updateEditModalTotalBalance();

    document.getElementById('edit-card-modal').classList.remove('hidden');
    document.getElementById('edit-card-modal').classList.add('flex');
}

function updateEditModalTotalBalance() {
    const card = window.currentEditingCard;
    if (!card) return;

    const baseBudget = parseFloat(document.getElementById('edit-initial-budget').value) || 0;
    const oldBudget = parseFloat(card.initial_budget) || 0;
    const currentBal = parseFloat(card.current_balance) || 0;

    // Show live remaining balance: current_balance adjusted by difference in initial budget
    const diff = baseBudget - oldBudget;
    const liveBal = Math.max(0, currentBal + diff);

    document.getElementById('edit-modal-total-balance').textContent = `₱${liveBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

async function deleteTopUpEntry(cardId, logId, amount) {
    if (!confirm(`Are you sure you want to delete this top-up of ₱${amount.toFixed(2)}? This will reduce the balance accordingly.`)) {
        return;
    }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value;

        const res = await fetch(`/fleet-cards/${cardId}/recharge/${logId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        // Reload page to reflect updated balance, top-up history, and audit log
        window.location.reload();
    } catch (err) {
        alert('Failed to delete top-up: ' + err.message);
    }
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
    document.getElementById('expense-source-select').value = 'destination_master';
    document.getElementById('expense-start-odo').value = card.vehicle ? card.vehicle.odometer : '';
    document.getElementById('expense-end-odo').value = '';
    document.getElementById('expense-manual-km').value = '';
    
    // Reset Trip Ticket picker & fuel price
    document.getElementById('expense-dest-select').value = '';
    document.getElementById('expense-dest-select').setAttribute('data-distance', '0');
    document.getElementById('expense-dest-select').setAttribute('data-liters-used', '0');
    const priceInput = document.getElementById('expense-fuel-price-per-liter');
    if (priceInput) {
        priceInput.value = card.rate_peso ? parseFloat(card.rate_peso).toFixed(2) : '';
    }

    const label = document.getElementById('tt-picker-label');
    if (label) {
        label.innerText = '-- Pick Trip Ticket / Destination --';
        label.className = 'truncate text-slate-400';
    }
    const panel = document.getElementById('tt-picker-panel');
    if (panel) panel.classList.add('hidden');
    document.getElementById('tt-picker-chevron')?.classList.remove('rotate-180');

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

    // Toggle live calculation modes
    const isTripTicket = source === 'destination_master';
    document.getElementById('live-calc-trip-ticket-mode')?.classList.toggle('hidden', !isTripTicket);
    document.getElementById('live-calc-distance-mode')?.classList.toggle('hidden', isTripTicket);

    updateLiveCalc();
}

// ── TRIP TICKET SEARCHABLE PICKER LOGIC ─────────────────────────────
function toggleTripTicketPicker() {
    const panel = document.getElementById('tt-picker-panel');
    const chevron = document.getElementById('tt-picker-chevron');
    if (!panel) return;
    const isHidden = panel.classList.contains('hidden');
    panel.classList.toggle('hidden', !isHidden);
    chevron?.classList.toggle('rotate-180', isHidden);
    if (isHidden) {
        const input = document.getElementById('tt-search-input');
        if (input) {
            input.value = '';
            filterTripTicketOptions();
            setTimeout(() => input.focus(), 50);
        }
    }
}

function filterTripTicketOptions() {
    const q = (document.getElementById('tt-search-input')?.value || '').toLowerCase().trim();
    let count = 0;
    document.querySelectorAll('.tt-option-item').forEach(el => {
        const text = (el.getAttribute('data-search') || '').toLowerCase();
        const matches = !q || text.includes(q);
        el.classList.toggle('hidden', !matches);
        if (matches) count++;
    });
    document.getElementById('tt-empty-state')?.classList.toggle('hidden', count > 0);
}

function pickTripTicket(id, labelText, distance, fuelUsed) {
    const hiddenInput = document.getElementById('expense-dest-select');
    hiddenInput.value = id;
    hiddenInput.setAttribute('data-distance', distance || 0);
    hiddenInput.setAttribute('data-liters-used', fuelUsed || 0);

    const label = document.getElementById('tt-picker-label');
    if (label) {
        label.innerText = labelText;
        label.className = 'truncate text-slate-900 font-bold';
    }

    const panel = document.getElementById('tt-picker-panel');
    if (panel) panel.classList.add('hidden');
    document.getElementById('tt-picker-chevron')?.classList.remove('rotate-180');

    updateLiveCalc();
}

// Close trip ticket picker when clicking outside
document.addEventListener('click', function(e) {
    const picker = document.getElementById('trip-ticket-picker');
    if (picker && !picker.contains(e.target)) {
        document.getElementById('tt-picker-panel')?.classList.add('hidden');
        document.getElementById('tt-picker-chevron')?.classList.remove('rotate-180');
    }
});

function updateLiveCalc() {
    if (!currentActiveCard) return;

    const source = document.getElementById('expense-source-select').value;
    const ratePeso = parseFloat(currentActiveCard.rate_peso) || 0;
    const rateKm = parseFloat(currentActiveCard.rate_km) || 1;
    const costPerKm = rateKm > 0 ? (ratePeso / rateKm) : 0;
    const currentBal = parseFloat(currentActiveCard.current_balance) || 0;

    let consumed = 0.0;
    let distance = 0.0;

    if (source === 'destination_master') {
        // Option 2: Base on Trip Ticket -> Liters Used * Price per Liter
        const destInput = document.getElementById('expense-dest-select');
        const litersUsed = parseFloat(destInput ? destInput.getAttribute('data-liters-used') : 0) || 0;
        distance = parseFloat(destInput ? destInput.getAttribute('data-distance') : 0) || 0;

        const pricePerLiter = parseFloat(document.getElementById('expense-fuel-price-per-liter')?.value) || 0;

        // Display Liters used in the Live Computation Summary
        const liveLitersEl = document.getElementById('live-liters-used');
        if (liveLitersEl) {
            liveLitersEl.innerText = litersUsed.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        if (litersUsed > 0 && pricePerLiter > 0) {
            consumed = litersUsed * pricePerLiter;
        } else if (litersUsed === 0 && distance > 0) {
            // Fallback to distance * costPerKm if no Part B completion logged yet
            consumed = distance * costPerKm;
        }

        const rateBadge = document.getElementById('live-rate-badge');
        if (rateBadge) {
            rateBadge.innerText = pricePerLiter > 0
                ? ('₱' + pricePerLiter.toFixed(2) + ' / L')
                : ('₱' + costPerKm.toFixed(2) + ' / km');
        }
    } else {
        // Option 1 & Option 3: Base on Distance
        if (source === 'odometer') {
            const start = parseFloat(document.getElementById('expense-start-odo').value) || 0;
            const end = parseFloat(document.getElementById('expense-end-odo').value) || 0;
            if (end > start) {
                distance = end - start;
            }
        } else {
            distance = parseFloat(document.getElementById('expense-manual-km').value) || 0;
        }

        consumed = distance * costPerKm;

        const rateBadge = document.getElementById('live-rate-badge');
        if (rateBadge) {
            rateBadge.innerText = '₱' + costPerKm.toFixed(2) + ' / km';
        }

        const liveDistEl = document.getElementById('live-distance');
        if (liveDistEl) {
            liveDistEl.innerText = distance.toFixed(1) + ' km';
        }
    }

    const newBal = Math.max(0, currentBal - consumed);

    document.getElementById('live-consumed').innerText = '₱' + consumed.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('live-current-balance').innerText = '₱' + currentBal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('live-new-balance').innerText = '₱' + newBal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// ── HISTORY CARD FILTER ──────────────────────────────────────────────
let activeHistoryCardId = null;

function filterHistoryByCard(cardId, cardName) {
    activeHistoryCardId = String(cardId);
    document.querySelectorAll('.history-log-row').forEach(row => {
        const rowCardId = row.getAttribute('data-card-id');
        row.style.display = (rowCardId === activeHistoryCardId) ? '' : 'none';
    });
    const badge = document.getElementById('history-card-filter-badge');
    const label = document.getElementById('history-card-filter-label');
    if (badge && label) {
        label.innerText = 'Showing: ' + cardName;
        badge.classList.remove('hidden');
    }
    // Scroll to history section
    document.getElementById('history-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function clearHistoryCardFilter() {
    activeHistoryCardId = null;
    document.querySelectorAll('.history-log-row').forEach(row => {
        row.style.display = '';
    });
    const badge = document.getElementById('history-card-filter-badge');
    if (badge) badge.classList.add('hidden');
}

// ── LOG DETAIL MODAL ─────────────────────────────────────────────────
function viewLogDetail(log) {
    const body = document.getElementById('log-detail-body');
    const fmt = v => v ? '₱' + parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '—';
    const fmtNum = v => v ? parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '—';

    const rows = [
        ['Date', log.created_at ? new Date(log.created_at).toLocaleString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'}) : '—'],
        ['Category', log.category || 'fuel'],
        ['Method', log.distance_source],
        ['Distance', log.distance_km > 0 ? fmtNum(log.distance_km) + ' km' : '—'],
        ['Liters Used', log.liters_used ? fmtNum(log.liters_used) + ' L' : '—'],
        ['Price per Liter', log.fuel_price_per_liter ? fmt(log.fuel_price_per_liter) : '—'],
        ['Fuel Expense Consumed', log.consumed_amount > 0 ? fmt(log.consumed_amount) : '—'],
        ['Balance Before', fmt(log.balance_before)],
        ['Balance After', fmt(log.balance_after)],
        ['Remarks', log.remarks || '—'],
    ];

    body.innerHTML = rows.map(([label, val]) => `
        <div class="flex justify-between gap-3 py-1.5 border-b border-gray-100 last:border-0">
            <span class="font-bold text-gray-500 shrink-0">${label}:</span>
            <span class="font-semibold text-gray-900 text-right break-words">${escHtml(val)}</span>
        </div>
    `).join('');

    const modal = document.getElementById('log-detail-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeLogDetailModal() {
    const modal = document.getElementById('log-detail-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ── EDIT LOG REMARKS ─────────────────────────────────────────────────
function editLogRemarks(logId, cardId, currentRemarks) {
    document.getElementById('edit-log-form').action = '/fleet-cards/' + cardId + '/log/' + logId;
    document.getElementById('edit-log-remarks-input').value = currentRemarks || '';
    const modal = document.getElementById('edit-log-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditLogModal() {
    const modal = document.getElementById('edit-log-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ── DELETE EXPENSE LOG ───────────────────────────────────────────────
async function deleteExpenseLog(cardId, logId, amount) {
    const fmtAmt = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (!confirm(`Delete this expense of ₱${fmtAmt}? The balance will be restored to the card.`)) return;

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value;

        await fetch(`/fleet-cards/${cardId}/log/${logId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            }
        });

        window.location.reload();
    } catch (err) {
        alert('Failed to delete expense log: ' + err.message);
    }
}
</script>
@endsection
