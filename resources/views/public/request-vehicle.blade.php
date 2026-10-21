<x-public-shell title="Request a Vehicle" active="request" width="max-w-3xl">
    <x-slot:head>
    <style>
        /* ── Phone-friendly form (screens under 640px) ─────────────────────────
           - 16px input text stops iOS Safari from zooming the page on focus
           - 44px controls are comfortable to tap with a thumb */
        @media (max-width: 639px) {
            body { overflow-x: hidden; }
            /* Two-column rows collapse to ONE column that can shrink. Without minmax(0,..)
               a long value (e.g. the selected office name) widens the column past the card. */
            .form-row { grid-template-columns: minmax(0, 1fr); }
            .form-row > * { min-width: 0; }
            form input:not([type="hidden"]):not([type="checkbox"]),
            form select,
            form textarea { font-size:1rem !important; }
            form input:not([type="hidden"]):not([type="checkbox"]),
            form select,
            #office-dropdown-trigger { height: 44px !important; }
            #office-dropdown-trigger { font-size:0.875rem !important; }
            form textarea { min-height: 120px; }
            /* iOS renders native date fields wider than their box unless told otherwise */
            form input[type="date"] { min-width: 0; max-width: 100%; -webkit-appearance: none; appearance: none; }
            form input[type="checkbox"] { width: 20px !important; height: 20px !important; }
            .office-option-item { padding-top: 12px !important; padding-bottom: 12px !important; font-size:0.875rem !important; }
            #office-options-list { max-height: 45vh !important; }
            /* calendar */
            #cal-prev, #cal-next { width: 40px !important; height: 40px !important; }
            #cal-grid button { min-height: 38px; font-size:0.8125rem !important; }
            /* passengers */
            #passenger-list input { padding-right: 46px !important; }
            [data-remove-passenger] { width: 32px !important; height: 32px !important; right: 8px !important; }
            #add-passenger { padding: 10px 0; }
            /* vehicle cards: keep them a single readable column */
            #vehicle-cards { grid-template-columns: 1fr !important; }
            button, a, select { touch-action: manipulation; }
        }
    </style>
    </x-slot:head>

    <div class="public-card p-4 sm:p-6">
        <div class="mb-4">
            <h1 class="text-xl font-bold tracking-tight">Request a vehicle</h1>
            <p class="mt-1 text-[0.8125rem] text-pub-muted">Book a government vehicle for official travel. No account needed. You'll get a reference code to track it.</p>
        </div>
        @if (isset($errors) && $errors->any())
            <div class="mb-5 rounded-xl border border-pub-danger-border bg-pub-danger-soft p-4 text-sm text-pub-danger" role="alert">
                <p class="font-bold text-pub-danger mb-1">Please fix the following issues before submitting:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="space-y-3" method="POST" autocomplete="off" action="{{ route('request-vehicle.store') }}">
            @csrf
            <input type="hidden" name="form_token" value="{{ $formToken ?? '' }}">
@php
$offices = \App\Models\Office::options();
@endphp

            <div class="flex items-center gap-2.5">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-pub-highlight text-[0.6875rem] font-bold text-pub-accent">1</span>
                <h2 class="text-sm font-semibold text-pub-text">Your details</h2>
                <span class="h-px flex-1 bg-pub-highlight" aria-hidden="true"></span>
            </div>
            <div class="form-row grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="rv-requesterName" class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>Your Full Name <span class="text-pub-accent">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input id="rv-requesterName" name="requesterName" required value="{{ old('requesterName') }}" placeholder="e.g. Juan Dela Cruz" class="h-10 w-full rounded-xl border border-pub-border bg-pub-field pl-9 pr-3 text-sm placeholder:text-pub-faint focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40">
                    </div>
                </div>

                {{-- Custom Searchable Office / Section Dropdown --}}
                <div class="relative" id="custom-office-dropdown-container">
                    <label class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text">
                        <span>Your Office / Section <span class="font-normal text-pub-muted">(Optional)</span></span>
                    </label>

                    {{-- Hidden input for form submission --}}
                    <input type="hidden" name="requesterOffice" id="requester-office-input" value="{{ old('requesterOffice') }}">

                    {{-- Trigger Button --}}
                    <button
                        type="button"
                        id="office-dropdown-trigger"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="office-dropdown-panel"
                        onclick="toggleOfficeDropdown()"
                        class="group flex h-10 w-full items-center justify-between rounded-xl border border-pub-border bg-pub-field pl-9 pr-3 text-left text-sm text-pub-text focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40 focus-visible:ring-2 focus-visible:ring-pub-accent/60 transition cursor-pointer relative"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                        <span id="office-dropdown-selected-label" class="truncate {{ old('requesterOffice') ? 'text-pub-text' : 'text-pub-faint' }}">{{ old('requesterOffice') ?: '— Select your office / division —' }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-pub-accent transition-transform duration-200" id="office-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    {{-- Dropdown Menu Panel --}}
                    <div
                        id="office-dropdown-panel"
                        class="absolute left-0 right-0 sm:left-auto sm:w-96 top-full z-50 mt-1.5 hidden rounded-xl border border-pub-border bg-pub-panel p-2 shadow-2xl backdrop-blur-md"
                    >
                        {{-- Search Bar inside Dropdown --}}
                        <div class="relative mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                            <input
                                type="text"
                                id="office-search-input"
                                aria-label="Search office"
                                oninput="filterOfficeOptions()"
                                placeholder="Search office..."
                                class="w-full rounded-lg border border-pub-border bg-pub-field py-1.5 pl-8 pr-3 text-xs text-pub-text placeholder:text-pub-faint focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40"
                            >
                        </div>

                        {{-- Options List --}}
                        <div id="office-options-list" role="listbox" aria-label="Offices" class="max-h-56 overflow-y-auto space-y-0.5 pr-1 scrollbar-thin">
                            <div
                                role="option" tabindex="0"
                                onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }"
                                onclick="selectOffice('', '— Select your office / division —')"
                                class="office-option-item flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-xs text-pub-muted hover:bg-pub-highlight hover:text-pub-accent transition"
                                data-label="— select your office / division —"
                            >
                                <span>— Select your office / division —</span>
                            </div>
                            @foreach($offices as $office)
                                <div
                                    role="option" tabindex="0"
                                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }"
                                    onclick="selectOffice('{{ addslashes($office['label']) }}')"
                                    class="office-option-item flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-xs text-pub-text hover:bg-pub-highlight hover:text-pub-text transition"
                                    data-label="{{ strtolower($office['label'] . ' ' . $office['value']) }}"
                                >
                                    <span>{{ $office['label'] }}</span>
                                </div>
                            @endforeach
                            <div id="no-office-found" class="hidden px-3 py-4 text-center text-xs text-pub-muted">
                                No office found matching search.
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="flex items-center gap-2.5 pt-1">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-pub-highlight text-[0.6875rem] font-bold text-pub-accent">2</span>
                <h2 class="text-sm font-semibold text-pub-text">Trip details</h2>
                <span class="h-px flex-1 bg-pub-highlight" aria-hidden="true"></span>
            </div>
            <div class="form-row grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="rv-location" class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>From (Station) <span class="text-pub-accent">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <select id="rv-location" name="location" required class="h-10 w-full rounded-xl border border-pub-border bg-pub-field pl-9 pr-3 text-sm text-pub-text focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40">
                            <option value="">— Select Your Station —</option>
                            @foreach(\App\Support\FleetLocations::ALL as $loc)
                                <option value="{{ $loc }}" @selected(old('location') === $loc)>{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                    {{-- Searchable Destination Dropdown with Distance Badges --}}
                <div class="relative" id="public-dest-dropdown-container">
                    <label class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text">
                        <span id="public-dest-label">To (Destination) <span class="text-pub-accent">*</span></span>
                    </label>

                    {{-- Hidden inputs --}}
                    {{-- Hidden inputs submitted with form --}}
                    <input type="hidden" name="destination" id="public-dest-name-input" value="{{ old('destination') }}" required>
                    <input type="hidden" name="distance_km" id="public-dest-distance-input" value="{{ old('distance_km') }}">

                    {{-- Trigger Button / Pill Display Box --}}
                    <div
                        id="public-dest-dropdown-trigger"
                        role="button"
                        tabindex="0"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="public-dest-dropdown-panel"
                        aria-labelledby="public-dest-label"
                        onclick="togglePublicDestDropdown()"
                        onkeydown="if (event.target === this && (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown')) { event.preventDefault(); togglePublicDestDropdown(); }"
                        class="group flex min-h-10 w-full items-center justify-between rounded-xl border border-pub-border bg-pub-field pl-3 pr-3 text-left text-sm text-pub-text focus-within:border-pub-accent focus-within:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent/60 transition cursor-pointer relative"
                    >
                        <div id="public-dest-pills-container" class="flex flex-wrap items-center gap-1.5 min-w-0 flex-1 py-1">
                            <span id="public-dest-placeholder" class="text-pub-faint">
                                — Select destination(s) or type custom place —
                            </span>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-pub-accent transition-transform duration-200" id="public-dest-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Dropdown Panel --}}
                    <div
                        id="public-dest-dropdown-panel"
                        role="listbox"
                        aria-multiselectable="true"
                        aria-labelledby="public-dest-label"
                        onkeydown="if (event.key === 'Escape') { event.preventDefault(); closePublicDestDropdown(); document.getElementById('public-dest-dropdown-trigger')?.focus(); }"
                        style="max-height: 230px; overflow-y: auto; overscroll-behavior: contain;"
                        class="absolute left-0 right-0 top-full z-50 mt-1.5 hidden rounded-xl border border-pub-border bg-pub-panel p-2 shadow-2xl backdrop-blur-md max-h-[230px] overflow-y-auto overscroll-contain scrollbar-thin"
                    >
                        {{-- Search & Direct Add Input --}}
                        <div class="sticky top-0 z-10 bg-pub-panel pb-2 pt-0.5">
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                                </svg>
                                <input
                                    type="text"
                                    id="public-dest-search-input"
                                    aria-label="Search or add a destination"
                                    oninput="filterPublicDestOptions()"
                                    onkeydown="onPublicDestSearchKeyDown(event)"
                                    placeholder="Type place name or search registered locations..."
                                    class="w-full rounded-lg border border-pub-border bg-pub-field py-1.5 pl-8 pr-3 text-xs text-pub-text placeholder:text-pub-faint focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40"
                                >
                            </div>
                            <p class="text-xs text-pub-muted mt-1 px-1">Tip: Select multiple registered places below or type any custom location and press Enter.</p>
                        </div>

                        {{-- Custom Add Option (Shown when typing) --}}
                        <div id="public-dest-custom-add-item" class="hidden mb-1">
                            {{-- Dynamically inserted --}}
                        </div>

                        {{-- Options List --}}
                        <div id="public-dest-options-list" class="space-y-0.5 pr-1">
                            {{-- Loaded dynamically via loadPublicDestinations() --}}
                        </div>
                    </div>
                </div>
            </div>


            {{-- Station calendar + vehicle cards. Hidden until a station is chosen. --}}
            <div id="station-panel" class="hidden space-y-3">
                <div class="form-row grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="rv-travelDate" class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>Travel Date (From) <span class="text-pub-accent">*</span></span></label>
                        <div class="group relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                            <input id="rv-travelDate" type="date" name="travelDate" required min="{{ today()->toDateString() }}" max="{{ today()->addDays(\Modules\VehicleRequest\Requests\StoreVehicleRequest::MAX_DAYS_AHEAD)->toDateString() }}" value="{{ old('travelDate') }}" class="h-10 w-full rounded-xl border border-pub-border bg-pub-field pl-9 pr-3 text-sm text-pub-text focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40">
                        </div>
                    </div>
                    <div>
                        <label for="rv-travelDateEnd" class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>Travel Date (To) <span class="text-pub-accent">*</span></span></label>
                        <div class="group relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                            <input id="rv-travelDateEnd" type="date" name="travelDateEnd" required min="{{ today()->toDateString() }}" max="{{ today()->addDays(\Modules\VehicleRequest\Requests\StoreVehicleRequest::MAX_DAYS_AHEAD + \Modules\VehicleRequest\Requests\StoreVehicleRequest::MAX_TRIP_DAYS - 1)->toDateString() }}" value="{{ old('travelDateEnd') }}" class="h-10 w-full rounded-xl border border-pub-border bg-pub-field pl-9 pr-3 text-sm text-pub-text focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40">
                        </div>
                    </div>
                </div>

                <div id="booking-calendar" class="rounded-xl border border-pub-border bg-pub-field p-2.5 backdrop-blur">
                    <div class="mb-2 flex items-center justify-between">
                        <button type="button" id="cal-prev" aria-label="Previous month" class="flex h-8 w-8 items-center justify-center rounded-lg border border-pub-border bg-pub-highlight text-base text-pub-text transition hover:bg-pub-highlight">‹</button>
                        <span id="cal-month-label" class="text-xs font-bold text-pub-text"></span>
                        <button type="button" id="cal-next" aria-label="Next month" class="flex h-8 w-8 items-center justify-center rounded-lg border border-pub-border bg-pub-highlight text-base text-pub-text transition hover:bg-pub-highlight">›</button>
                    </div>
                    <div class="grid grid-cols-7 gap-[3px] text-center">
                        @foreach(['S','M','T','W','T','F','S'] as $d)
                            <div class="py-0.5 text-[0.6875rem] font-bold text-pub-muted">{{ $d }}</div>
                        @endforeach
                    </div>
                    <div id="cal-grid" class="grid grid-cols-7 gap-[3px] text-center"></div>
                    <p class="mt-2 text-xs text-pub-muted">Tip: click your travel date to see the available vehicles, then click your end date. Click the same date again for a one-day trip.</p>
                    <div class="mt-2.5 flex flex-wrap gap-x-3 gap-y-1 text-xs font-semibold text-pub-muted">
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:transparent;border:1px solid var(--color-pub-faint)"></span>No requests yet</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#2f855a"></span>Approved Trip</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#dd6b20"></span>Pending Request</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#b91c1c"></span>Maintenance</span>
                    </div>
                </div>

                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>Select Vehicle <span class="text-pub-accent">*</span></span></label>
                    <p id="vehicle-hint" class="mb-2 text-xs text-pub-muted"></p>
                    <input type="hidden" name="vehicleId" id="vehicle-id-input" value="{{ old('vehicleId') }}">
                    <div id="vehicle-cards" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5"></div>
                    <p class="mt-2 text-xs text-pub-muted">The admin confirms the driver when approving your request. The driver's name appears on your printable trip ticket.</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 pt-1">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-pub-highlight text-[0.6875rem] font-bold text-pub-accent">3</span>
                <h2 class="text-sm font-semibold text-pub-text">Purpose and passengers</h2>
                <span class="h-px flex-1 bg-pub-highlight" aria-hidden="true"></span>
            </div>
            <div class="form-row grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="rv-purpose" class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>Official Purpose <span class="text-pub-accent">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                        <textarea id="rv-purpose" name="purpose" required rows="3" placeholder="Describe the official reason or event for this travel request..." class="w-full rounded-xl border border-pub-border bg-pub-field py-2 pl-9 pr-3 text-sm placeholder:text-pub-faint focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40">{{ old('purpose') }}</textarea>
                    </div>
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>Additional Passengers <span class="font-normal text-pub-muted">(Optional)</span></span></label>
                    <label class="mb-2 flex items-center gap-2 text-[0.8125rem] text-pub-muted">
                        <input type="checkbox" name="requesterIsPassenger" value="1" @checked(old('requesterIsPassenger')) class="h-4 w-4 rounded border-pub-border bg-pub-field text-pub-accent">
                        I am riding along on this trip as a passenger
                    </label>
                    <div id="passenger-list" style="display:flex;flex-direction:column;gap:8px;">
                        @php $oldPassengers = old('passengers', ['']); @endphp
                        @foreach($oldPassengers as $index => $pass)
                            <div class="group relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <input name="passengers[]" value="{{ $pass }}" placeholder="Passenger {{ $index + 1 }} full name" class="h-10 w-full rounded-xl border border-pub-border bg-pub-field pl-9 pr-8 text-sm placeholder:text-pub-faint focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40">
                                <button type="button" data-remove-passenger aria-label="Remove passenger" title="Remove passenger" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:22px;height:22px;border:0;border-radius:999px;background:var(--color-pub-highlight);color:var(--color-pub-danger);font-size:0.9375rem;line-height:1;cursor:pointer;">×</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-passenger" class="mt-2 text-sm font-bold text-pub-text hover:text-pub-accent">＋ Add another passenger</button>
                </div>
            </div>

            <div class="flex items-center gap-2.5 pt-1">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-pub-highlight text-[0.6875rem] font-bold text-pub-accent">4</span>
                <h2 class="text-sm font-semibold text-pub-text">Contact</h2>
                <span class="h-px flex-1 bg-pub-highlight" aria-hidden="true"></span>
            </div>
            <div class="form-row grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="rv-requesterContact" class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>Contact Number <span class="text-pub-accent">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <input id="rv-requesterContact" name="requesterContact" value="{{ old('requesterContact') }}" placeholder="09171234567" inputmode="numeric" maxlength="11" pattern="09[0-9]{9}" required title="Must start with 09 and be exactly 11 digits" class="h-10 w-full rounded-xl border border-pub-border bg-pub-field pl-9 pr-3 text-sm placeholder:text-pub-faint focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40">
                    </div>
                </div>
                <div>
                    <label for="rv-previousTripTicketDate" class="mb-1 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-pub-text"><span>Previous Trip Ticket Date <span class="font-normal text-pub-muted">(Optional)</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                        <input id="rv-previousTripTicketDate" type="date" name="previousTripTicketDate" value="{{ old('previousTripTicketDate') }}" class="h-10 w-full rounded-xl border border-pub-border bg-pub-field pl-9 pr-3 text-sm text-pub-text focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40">
                    </div>
                </div>
            </div>

            <div class="border-t border-pub-border pt-4">
                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-pub-primary px-4 text-sm font-bold text-pub-on-primary shadow-lg shadow-emerald-950/40 transition hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent">
                    Submit request
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
                <p class="mt-2 text-center text-xs text-pub-muted">An administrator reviews every request. Check its progress any time on Check Status.</p>
            </div>
        </form>
        </div>

    <script>
        function toggleOfficeDropdown() {
            const panel = document.getElementById('office-dropdown-panel');
            const chevron = document.getElementById('office-dropdown-chevron');
            const searchInput = document.getElementById('office-search-input');
            
            if (!panel) return;
            const isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
                document.getElementById('office-dropdown-trigger')?.setAttribute('aria-expanded', 'true');
                if (chevron) chevron.classList.add('rotate-180');
                if (searchInput) {
                    searchInput.value = '';
                    filterOfficeOptions();
                    setTimeout(() => searchInput.focus(), 50);
                }
            } else {
                closeOfficeDropdown();
            }
        }

        function closeOfficeDropdown() {
            const panel = document.getElementById('office-dropdown-panel');
            const chevron = document.getElementById('office-dropdown-chevron');
            if (panel) panel.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            document.getElementById('office-dropdown-trigger')?.setAttribute('aria-expanded', 'false');
        }

        function selectOffice(val, displayLabel = null) {
            const hiddenInput = document.getElementById('requester-office-input');
            const labelSpan = document.getElementById('office-dropdown-selected-label');
            
            if (hiddenInput) hiddenInput.value = val;
            if (labelSpan) {
                labelSpan.textContent = val || (displayLabel || '— Select your office / division —');
                if (val) {
                    labelSpan.classList.remove('text-pub-faint');
                    labelSpan.classList.add('text-pub-text');
                } else {
                    labelSpan.classList.remove('text-pub-text');
                    labelSpan.classList.add('text-pub-faint');
                }
            }
            closeOfficeDropdown();
        }

        function filterOfficeOptions() {
            const query = (document.getElementById('office-search-input')?.value || '').toLowerCase().trim();
            const items = document.querySelectorAll('.office-option-item');
            let visibleCount = 0;

            items.forEach(item => {
                const text = item.getAttribute('data-label') || '';
                if (!query || text.includes(query)) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            const noFound = document.getElementById('no-office-found');
            if (noFound) {
                if (visibleCount === 0) {
                    noFound.classList.remove('hidden');
                } else {
                    noFound.classList.add('hidden');
                }
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            const officePanel = document.getElementById('office-dropdown-panel');
            if (officePanel && !officePanel.classList.contains('hidden')) {
                closeOfficeDropdown();
                document.getElementById('office-dropdown-trigger')?.focus();
            }
        });

        document.addEventListener('click', (e) => {
            const container = document.getElementById('custom-office-dropdown-container');
            if (container && !container.contains(e.target)) {
                closeOfficeDropdown();
            }
            const destContainer = document.getElementById('public-dest-dropdown-container');
            if (destContainer && !destContainer.contains(e.target)) {
                closePublicDestDropdown();
            }
        });

        // ── Public QR Form: Destination searchable dropdown & distance support ──
        let currentPublicDestinations = [];

        function loadPublicDestinations() {
            const locSelect = document.querySelector('select[name="location"]');
            const location = locSelect?.value || '';

            if (!location) {
                // No station selected yet — show prompt, don't fetch
                const list = document.getElementById('public-dest-options-list');
                if (list) {
                    list.innerHTML = `
                        <div class="flex items-center gap-2 px-3 py-4 text-xs text-pub-muted italic">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            Please select your station first
                        </div>
                    `;
                }
                currentPublicDestinations = [];
                return;
            }

            fetch(`/api/destinations-by-location?location=${encodeURIComponent(location)}`)
                .then(res => res.json())
                .then(data => {
                    currentPublicDestinations = data.destinations || [];
                    renderPublicDestOptions(currentPublicDestinations);
                })
                .catch(err => {
                    console.error('Failed to load public destinations:', err);
                    renderPublicDestOptions([]);
                });
        }

        let selectedPublicDestinations = []; // [{ name: '', distance_km: 0 }]

        function renderPublicDestOptions(destinations) {
            const list = document.getElementById('public-dest-options-list');
            if (!list) return;

            let html = '';
            // Keep the rendered list so option handlers reference entries by index instead of inlining names into JS
            window.__publicDestRenderList = destinations;
            destinations.forEach((d, i) => {
                const isPicked = selectedPublicDestinations.some(item => item.name.toLowerCase() === d.name.toLowerCase());

                html += `
                    <div role="option" tabindex="0" aria-selected="${isPicked ? 'true' : 'false'}" onclick="togglePublicSelectedDest(window.__publicDestRenderList[${i}].name, window.__publicDestRenderList[${i}].distance_km)" onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }" class="public-dest-option flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-xs transition ${isPicked ? 'bg-pub-highlight text-pub-text font-bold border border-pub-accent' : 'text-pub-text hover:bg-pub-highlight hover:text-pub-text'}" data-label="${esc((d.name + ' ' + d.distance_km + 'km').toLowerCase())}">
                        <div class="flex items-center gap-2">
                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border ${isPicked ? 'border-pub-accent bg-pub-primary text-pub-on-primary font-black' : 'border-pub-border bg-pub-highlight'}">
                                ${isPicked ? '✓' : ''}
                            </span>
                            <span>${esc(d.name)}</span>
                        </div>
                        <span class="inline-flex items-center rounded-md bg-pub-highlight border border-pub-border px-2 py-0.5 text-[0.625rem] font-bold text-pub-accent shrink-0">
                            ${esc(d.distance_km)} kms
                        </span>
                    </div>
                `;
            });

            list.innerHTML = html;
            filterPublicDestOptions();
        }

        function togglePublicSelectedDest(name, distanceKm) {
            const idx = selectedPublicDestinations.findIndex(item => item.name.toLowerCase() === name.toLowerCase());
            if (idx >= 0) {
                selectedPublicDestinations.splice(idx, 1);
            } else {
                selectedPublicDestinations.push({ name: name, distance_km: distanceKm || 0 });
            }
            updatePublicDestFormState();
        }

        function addPublicCustomDest(customText) {
            if (!customText) return;
            const parts = customText.split(',').map(p => p.trim()).filter(Boolean);
            parts.forEach(part => {
                const existingRegistered = currentPublicDestinations.find(d => d.name.toLowerCase() === part.toLowerCase());
                if (existingRegistered) {
                    if (!selectedPublicDestinations.some(item => item.name.toLowerCase() === existingRegistered.name.toLowerCase())) {
                        selectedPublicDestinations.push({ name: existingRegistered.name, distance_km: existingRegistered.distance_km });
                    }
                } else {
                    if (!selectedPublicDestinations.some(item => item.name.toLowerCase() === part.toLowerCase())) {
                        selectedPublicDestinations.push({ name: part, distance_km: 0 });
                    }
                }
            });

            const searchInput = document.getElementById('public-dest-search-input');
            if (searchInput) searchInput.value = '';
            updatePublicDestFormState();
        }

        function removePublicSelectedDest(index, event) {
            if (event) event.stopPropagation();
            if (index >= 0 && index < selectedPublicDestinations.length) {
                selectedPublicDestinations.splice(index, 1);
                updatePublicDestFormState();
            }
        }

        function updatePublicDestFormState() {
            const nameInput = document.getElementById('public-dest-name-input');
            const distInput = document.getElementById('public-dest-distance-input');
            const container = document.getElementById('public-dest-pills-container');
            const totalKmSpan = document.getElementById('public-dest-total-km');

            const destinationString = selectedPublicDestinations.map(d => d.name).join(';;');
            const totalDistance = selectedPublicDestinations.reduce((sum, d) => sum + (parseInt(d.distance_km) || 0), 0);

            if (nameInput) nameInput.value = destinationString;
            if (distInput) distInput.value = totalDistance > 0 ? totalDistance : '';

            if (container) {
                if (selectedPublicDestinations.length === 0) {
                    container.innerHTML = `<span id="public-dest-placeholder" class="text-pub-faint">— Select destination(s) or type custom place —</span>`;
                } else {
                    let pillsHtml = '';
                    selectedPublicDestinations.forEach((item, index) => {
                        pillsHtml += `
                            <span class="inline-flex items-center gap-1 rounded-md bg-pub-highlight border border-pub-accent px-2 py-0.5 text-xs font-bold text-pub-text shadow-2xs">
                                <span>${esc(item.name)}</span>
                                <button type="button" onclick="removePublicSelectedDest(${index}, event)" aria-label="Remove ${esc(item.name)}" class="hover:text-pub-danger text-pub-accent font-extrabold ml-1 cursor-pointer">✕</button>
                            </span>
                        `;
                    });
                    container.innerHTML = pillsHtml;
                }
            }

            renderPublicDestOptions(currentPublicDestinations);
        }

        function togglePublicDestDropdown() {
            const panel = document.getElementById('public-dest-dropdown-panel');
            const chevron = document.getElementById('public-dest-dropdown-chevron');
            const searchInput = document.getElementById('public-dest-search-input');
            if (!panel) return;
            const isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
                document.getElementById('public-dest-dropdown-trigger')?.setAttribute('aria-expanded', 'true');
                if (chevron) chevron.classList.add('rotate-180');
                if (searchInput) {
                    searchInput.value = '';
                    filterPublicDestOptions();
                    setTimeout(() => searchInput.focus(), 50);
                }
            } else {
                closePublicDestDropdown();
            }
        }

        function closePublicDestDropdown() {
            const panel = document.getElementById('public-dest-dropdown-panel');
            const chevron = document.getElementById('public-dest-dropdown-chevron');
            if (panel) panel.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            document.getElementById('public-dest-dropdown-trigger')?.setAttribute('aria-expanded', 'false');
        }

        function filterPublicDestOptions() {
            const searchInput = document.getElementById('public-dest-search-input');
            const query = (searchInput?.value || '').trim();
            const queryLower = query.toLowerCase();

            const customAddItem = document.getElementById('public-dest-custom-add-item');
            if (customAddItem) {
                if (query.length > 0) {
                    customAddItem.innerHTML = `
                        <div role="button" tabindex="0" onclick="addPublicCustomDest((document.getElementById('public-dest-search-input')?.value || '').trim())" onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }" class="flex cursor-pointer items-center justify-between rounded-lg bg-pub-highlight border border-pub-accent p-2 text-xs font-bold text-pub-accent hover:bg-pub-highlight transition">
                            <span class="truncate">➕ Add custom: "${esc(query)}"</span>
                            <span class="text-[0.625rem] text-pub-accent font-semibold shrink-0">Press Enter</span>
                        </div>
                    `;
                    customAddItem.classList.remove('hidden');
                } else {
                    customAddItem.classList.add('hidden');
                }
            }

            const items = document.querySelectorAll('.public-dest-option');
            items.forEach(item => {
                const label = (item.dataset.label || '').toLowerCase();
                const match = !queryLower || label.includes(queryLower);
                item.classList.toggle('hidden', !match);
            });
        }

        function onPublicDestSearchKeyDown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const searchInput = document.getElementById('public-dest-search-input');
                const val = (searchInput?.value || '').trim();
                if (val) {
                    addPublicCustomDest(val);
                }
            }
        }

        const locationSelect = document.querySelector('select[name="location"]');
        const stationUrl = "{{ route('request-vehicle.station-availability') }}";
        const stationPanel = document.getElementById('station-panel');
        const calGrid = document.getElementById('cal-grid');
        const calMonthLabel = document.getElementById('cal-month-label');
        const cardsBox = document.getElementById('vehicle-cards');
        const vehicleHint = document.getElementById('vehicle-hint');
        const vehicleIdInput = document.getElementById('vehicle-id-input');
        const travelDateInput = document.querySelector('input[name="travelDate"]');
        const travelDateEndInput = document.querySelector('input[name="travelDateEnd"]');

        let stationVehicles = [];
        let stationLoaded = false;
        let rangeConfirmed = false;   // false = only start date picked -> show free vehicles only
        let pickingEnd = false;
        let calendarFocused = false;
        let viewYear = new Date().getFullYear();
        let viewMonth = new Date().getMonth();

        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const fmtDay = (k) => new Date(k + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        const fmtRange = (a, b) => (a === b ? fmtDay(a) : `${fmtDay(a)} – ${fmtDay(b)}`);

        function toKey(y, m, d) {
            return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        }

        function eachDateKey(startStr, endStr, cb) {
            const start = new Date(startStr + 'T00:00:00');
            const end = endStr ? new Date(endStr + 'T00:00:00') : start;
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                cb(toKey(d.getFullYear(), d.getMonth(), d.getDate()));
            }
        }

        const RANK = { pending: 1, approved: 2, maintenance: 3 };

        // Per-day summary across every vehicle at the chosen station.
        function stationDayMap() {
            const map = new Map();
            stationVehicles.forEach((v) => {
                const perVehicle = new Map();
                v.bookings.forEach((b) => eachDateKey(b.start, b.end, (key) => {
                    if ((RANK[b.status] || 0) > (RANK[perVehicle.get(key)] || 0)) perVehicle.set(key, b.status);
                }));
                perVehicle.forEach((status, key) => {
                    const cur = map.get(key) || { worst: null, busy: 0 };
                    cur.busy += 1;
                    if ((RANK[status] || 0) > (RANK[cur.worst] || 0)) cur.worst = status;
                    map.set(key, cur);
                });
            });
            return map;
        }

        function selectedRange() {
            if (!travelDateInput.value) return null;
            const start = travelDateInput.value;
            const end = travelDateEndInput.value && travelDateEndInput.value >= start ? travelDateEndInput.value : start;
            return { start, end };
        }

        function renderCalendar() {
            const map = stationDayMap();
            const range = selectedRange();
            const firstOfMonth = new Date(viewYear, viewMonth, 1);
            const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
            calMonthLabel.textContent = firstOfMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

            const colors = { maintenance: '#b91c1c', approved: '#2f855a', pending: '#dd6b20' };
            const labels = { maintenance: 'Maintenance', approved: 'Approved trip', pending: 'Pending request' };
            let html = '';
            for (let i = 0; i < firstOfMonth.getDay(); i++) html += '<div></div>';
            for (let day = 1; day <= daysInMonth; day++) {
                const key = toKey(viewYear, viewMonth, day);
                const info = map.get(key);
                const inRange = range && key >= range.start && key <= range.end;
                const bg = info ? colors[info.worst] : 'transparent';
                const color = info ? '#ffffff' : (inRange ? 'var(--color-pub-text)' : 'var(--color-pub-muted)');
                const ring = inRange ? 'outline:2.5px solid var(--color-pub-accent);outline-offset:-2.5px;' : '';
                const title = info
                    ? `${info.busy} of ${stationVehicles.length} vehicle(s) booked · ${labels[info.worst]}`
                    : 'No requests yet';
                html += `<button type="button" data-date="${key}" title="${esc(title)}" style="width:100%;border:0;font-family:inherit;font-size:0.6875rem;padding:4px 0;border-radius:5px;background:${bg};color:${color};font-weight:${info || inRange ? 700 : 500};cursor:pointer;${ring}">${day}</button>`;
            }
            calGrid.innerHTML = html;
        }

        // What (if anything) blocks this vehicle inside the chosen dates.
        function conflictFor(vehicle, start, end) {
            const out = { maintenance: null, approved: null, pending: null };
            vehicle.bookings.forEach((b) => {
                if (b.start <= end && start <= b.end && !out[b.status]) out[b.status] = b;
            });
            return out;
        }

        const PLACEHOLDER_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>';

        function renderCards() {
            const range = selectedRange();
            if (!stationLoaded) { cardsBox.innerHTML = ''; return; }
            if (!stationVehicles.length) {
                vehicleHint.textContent = 'No vehicles are registered at this station yet.';
                cardsBox.innerHTML = '';
                return;
            }
            if (!range) {
                vehicleHint.textContent = 'Pick your travel date on the calendar to see the vehicles available on that day.';
                cardsBox.innerHTML = '';
                vehicleIdInput.value = '';
                return;
            }

            const rows = stationVehicles.map((v) => ({ v, c: conflictFor(v, range.start, range.end) }));
            const severity = (c) => (c.maintenance ? 3 : c.approved ? 2 : c.pending ? 1 : 0);
            let visible = rangeConfirmed ? rows : rows.filter((r) => severity(r.c) === 0);
            visible = visible.slice().sort((a, b) => severity(a.c) - severity(b.c) || a.v.plate.localeCompare(b.v.plate));

            const hiddenCount = rows.length - visible.length;
            if (rangeConfirmed) {
                vehicleHint.textContent = `Vehicles for ${fmtRange(range.start, range.end)}. A vehicle with a warning already has a request or maintenance within these dates.`;
            } else {
                vehicleHint.textContent = `Available on ${fmtDay(range.start)}. Now click your end date (or the same date again for a one-day trip)`
                    + (hiddenCount ? ` — ${hiddenCount} other vehicle(s) already have a request that day and will appear with a warning.` : '.');
            }

            // Drop a previously picked vehicle that is no longer selectable.
            const picked = rows.find((r) => String(r.v.id) === String(vehicleIdInput.value));
            if (vehicleIdInput.value && (!picked || picked.c.maintenance || !visible.includes(picked))) {
                vehicleIdInput.value = '';
            }

            if (!visible.length) {
                cardsBox.innerHTML = '<p style="grid-column:1/-1;font-size:0.75rem;color:var(--color-pub-danger);">No vehicles are available on this date. Try another date.</p>';
                return;
            }

            cardsBox.innerHTML = visible.map(({ v, c }) => {
                const isSelected = String(v.id) === String(vehicleIdInput.value);
                const blocked = Boolean(c.maintenance);
                let note = '';
                if (c.maintenance) {
                    note = { bg: 'rgba(185,28,28,.65)', head: '⛔ Maintenance: ' + fmtRange(c.maintenance.start, c.maintenance.end) };
                } else if (c.approved) {
                    note = { bg: 'rgba(185,28,28,.55)', head: '⚠ Approved Trip: ' + fmtRange(c.approved.start, c.approved.end) };
                } else if (c.pending) {
                    note = { bg: 'rgba(217,119,6,.6)', head: '⚠ Pending Request: ' + fmtRange(c.pending.start, c.pending.end) };
                } else {
                    note = { bg: 'var(--color-pub-highlight)', head: '✓ Available', textClr: 'var(--color-pub-accent)' };
                }

                const border = isSelected ? 'var(--color-pub-accent)' : (blocked ? 'rgba(239,68,68,.4)' : 'var(--color-pub-border)');
                const photo = v.photo
                    ? `<img src="${esc(v.photo)}" alt="${esc(v.plate)}" style="width:auto;max-width:100%;height:56px;object-fit:contain;border-radius:6px;">`
                    : PLACEHOLDER_SVG;

                // Driver names and photos are not shown publicly (decision D11), only whether one is assigned
                const driverLabel = v.has_driver ? 'Driver assigned' : 'No driver yet';
                const avatar = `<span style="width:20px;height:20px;border-radius:999px;background:var(--color-pub-highlight);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-pub-accent);" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg></span>`;

                // min-width:0 lets the grid cell shrink so long model names truncate instead of widening the page on phones
                return `<button type="button" data-vehicle="${v.id}" ${blocked ? 'disabled' : ''} style="position:relative;min-width:0;display:flex;flex-direction:column;justify-content:space-between;padding:10px;border-radius:14px;border:2px solid ${border};background:${isSelected ? 'var(--color-pub-highlight)' : 'var(--color-pub-field)'};color:#fff;text-align:left;font-family:inherit;cursor:${blocked ? 'not-allowed' : 'pointer'};opacity:${blocked ? .65 : 1};transition:all .15s ease;">
                    <div style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:6px;padding-bottom:6px;border-bottom:1px solid var(--color-pub-border);margin-bottom:8px;">
                        <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
                            ${avatar}
                            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.71875rem;font-weight:700;color:${v.has_driver ? 'var(--color-pub-text)' : 'var(--color-pub-muted)'};">${driverLabel}</span>
                        </div>
                        ${isSelected ? '<span style="font-size:0.625rem;font-weight:800;background:#10b981;color:#052e16;border-radius:999px;padding:1px 7px;flex-shrink:0;">✓ Selected</span>' : ''}
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;width:100%;">
                        <div style="width:72px;height:56px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--color-pub-highlight);color:var(--color-pub-faint);border-radius:8px;padding:2px;">
                            ${photo}
                        </div>
                        <div style="min-width:0;flex:1;text-align:left;">
                            <div style="font-size:0.875rem;font-weight:900;letter-spacing:.03em;color:var(--color-pub-text);line-height:1.2;">${esc(v.plate)}</div>
                            <div style="font-size:0.6875rem;font-weight:600;color:var(--color-pub-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:2px;" title="${esc(v.name)}">${esc(v.name)}</div>
                        </div>
                    </div>

                    <div style="margin-top:8px;padding-top:4px;">
                        <div style="display:inline-flex;align-items:center;border-radius:6px;padding:2px 7px;font-size:0.625rem;font-weight:800;background:${note.bg};color:${note.textClr || '#fff'};">
                            ${note.head}
                        </div>
                    </div>
                </button>`;
            }).join('');
        }

        cardsBox.addEventListener('click', (event) => {
            const btn = event.target.closest('button[data-vehicle]');
            if (!btn || btn.disabled) return;
            vehicleIdInput.value = btn.dataset.vehicle;
            renderCards();
        });

        // Click-to-pick: 1st click = start date (shows free vehicles),
        // 2nd click = end date (same date again keeps a one-day trip; shows warnings too).
        function setDateInput(input, value) {
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        calGrid.addEventListener('click', (event) => {
            const btn = event.target.closest('button[data-date]');
            if (!btn) return;
            const key = btn.dataset.date;
            calendarFocused = true;
            const keepYear = viewYear, keepMonth = viewMonth;
            if (pickingEnd && travelDateInput.value && key >= travelDateInput.value) {
                rangeConfirmed = true;
                setDateInput(travelDateEndInput, key);
                pickingEnd = false;
            } else {
                rangeConfirmed = false;
                setDateInput(travelDateInput, key);
                setDateInput(travelDateEndInput, key);
                pickingEnd = true;
            }
            viewYear = keepYear;
            viewMonth = keepMonth;
            renderCalendar();
            renderCards();
        });

        function changeMonth(delta) {
            const next = new Date(viewYear, viewMonth + delta, 1);
            viewYear = next.getFullYear();
            viewMonth = next.getMonth();
            renderCalendar();
        }
        document.getElementById('cal-prev').addEventListener('click', () => changeMonth(-1));
        document.getElementById('cal-next').addEventListener('click', () => changeMonth(1));

        travelDateInput.addEventListener('input', () => {
            travelDateEndInput.min = travelDateInput.value || '';
            if (travelDateEndInput.value && travelDateEndInput.value < travelDateInput.value) {
                travelDateEndInput.value = '';
            }
            // Convenience for one-day trips: default "To" to the same date as "From"
            // when it's empty (it was just cleared above if it fell before "From").
            if (travelDateInput.value && !travelDateEndInput.value) {
                travelDateEndInput.value = travelDateInput.value;
                travelDateEndInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        if (travelDateInput.value) {
            travelDateEndInput.min = travelDateInput.value;
        }

        [travelDateInput, travelDateEndInput].forEach((input) => {
            input.addEventListener('input', (event) => {
                // Typing/picking in the date boxes counts as a finished range.
                if (event.isTrusted) rangeConfirmed = true;
                calendarFocused = true;
                if (travelDateInput.value) {
                    const [y, m] = travelDateInput.value.split('-').map(Number);
                    if (y && m) { viewYear = y; viewMonth = m - 1; }
                }
                renderCalendar();
                renderCards();
            });
        });

        function loadStation(isInit = false) {
            const location = locationSelect.value;
            if (!isInit) vehicleIdInput.value = '';
            if (!location) {
                stationLoaded = false;
                stationVehicles = [];
                stationPanel.classList.add('hidden');
                return;
            }
            stationPanel.classList.remove('hidden');
            vehicleHint.textContent = 'Loading vehicles…';
            cardsBox.innerHTML = '';
            fetch(`${stationUrl}?location=${encodeURIComponent(location)}`, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((data) => {
                    if (locationSelect.value !== location) return; // user switched station meanwhile
                    stationVehicles = data.vehicles || [];
                    stationLoaded = true;
                    if (!calendarFocused && travelDateInput.value) {
                        const [y, m] = travelDateInput.value.split('-').map(Number);
                        if (y && m) { viewYear = y; viewMonth = m - 1; }
                    }
                    renderCalendar();
                    renderCards();
                })
                .catch(() => {
                    stationLoaded = false;
                    vehicleHint.textContent = 'Could not load vehicles. Please re-select your station or refresh the page.';
                });
        }

        locationSelect.addEventListener('change', () => {
            loadStation(false);
            // Reset destination selection when station changes
            selectedPublicDestinations = [];
            updatePublicDestFormState();
            loadPublicDestinations();
        });
        document.addEventListener('DOMContentLoaded', () => {
            rangeConfirmed = Boolean(travelDateInput.value && travelDateEndInput.value);
            loadStation(true);
            loadPublicDestinations();
        });

        // Vehicle cards replace the old dropdown, so guard the submit here.
        document.querySelector('form').addEventListener('submit', (event) => {
            if (event.target.dataset.submitting === '1') {
                // Already sent: ignore double clicks / repeated Enter presses.
                event.preventDefault();
                return;
            }
            if (!vehicleIdInput.value) {
                event.preventDefault();
                stationPanel.classList.remove('hidden');
                vehicleHint.textContent = locationSelect.value
                    ? 'Please choose a vehicle card below before submitting.'
                    : 'Please select your station first, then choose a vehicle.';
                vehicleHint.style.color = 'var(--color-pub-danger)';
                stationPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            event.target.dataset.submitting = '1';
            const submitBtn = event.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });

        // Back/forward can restore this page from the browser's cache with the old
        // (already submitted) values. Reload so the user always gets a clean form.
        window.addEventListener('pageshow', (event) => {
            const nav = performance.getEntriesByType('navigation')[0];
            if (event.persisted || (nav && nav.type === 'back_forward')) {
                window.location.reload();
            }
        });

        // Title Case names as soon as the field loses focus (the server applies the same rule on submit).
        function toTitleCaseName(value) {
            return value
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase()
                .replace(/(^|[\s\-\/(.])(\p{Ll})/gu, (m, sep, ch) => sep + ch.toUpperCase())
                .replace(/\b([OD])'(\p{Ll})/gu, (m, a, b) => a + "'" + b.toUpperCase())
                .replace(/\bMc(\p{Ll})/gu, (m, a) => 'Mc' + a.toUpperCase())
                .replace(/\s(Ii|Iii|Iv)$/, (m, s) => ' ' + s.toUpperCase());
        }

        document.addEventListener('focusout', (event) => {
            const field = event.target;
            if (field instanceof HTMLInputElement && field.matches('input[name="requesterName"], input[name="passengers[]"]')) {
                field.value = toTitleCaseName(field.value);
            }
        });

        // Contact number: digits only, capped at 11 characters, forced to start with 09
        // (the server re-validates this with the same ^09\d{9}$ rule on submit).
        document.addEventListener('input', (event) => {
            const field = event.target;
            if (!(field instanceof HTMLInputElement) || field.name !== 'requesterContact') return;
            let digits = field.value.replace(/\D/g, '').slice(0, 11);
            if (digits.length >= 1 && digits[0] !== '0') digits = '0' + digits.replace(/^0*/, '').slice(0, 10);
            if (digits.length >= 2 && digits[1] !== '9') digits = digits.slice(0, 1);
            field.value = digits;
        });

        const passengerList = document.getElementById('passenger-list');
        const renumberPassengers = () => {
            Array.from(passengerList.children).forEach((row, i) => {
                const input = row.querySelector('input');
                if (input) input.placeholder = `Passenger ${i + 1} full name`;
            });
        };
        document.getElementById('add-passenger').addEventListener('click', () => {
            if (passengerList.children.length >= 20) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'group relative';
            wrapper.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint transition-colors group-focus-within:text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            const input = document.createElement('input');
            input.name = 'passengers[]';
            input.className = 'h-9 w-full rounded-xl border border-pub-border bg-pub-field pl-9 pr-8 text-sm placeholder:text-pub-faint focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40';
            wrapper.appendChild(input);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.removePassenger = '';
            remove.setAttribute('aria-label', 'Remove passenger');
            remove.title = 'Remove passenger';
            remove.textContent = '×';
            remove.style.cssText = 'position:absolute;right:6px;top:50%;transform:translateY(-50%);width:22px;height:22px;border:0;border-radius:999px;background:var(--color-pub-highlight);color:var(--color-pub-danger);font-size:0.9375rem;line-height:1;cursor:pointer;';
            wrapper.appendChild(remove);
            passengerList.appendChild(wrapper);
            renumberPassengers();
            input.focus();
        });
        passengerList.addEventListener('click', (event) => {
            const btn = event.target.closest('[data-remove-passenger]');
            if (!btn) return;
            const row = btn.closest('.group');
            if (passengerList.children.length <= 1) {
                // Keep one row on screen: just clear it.
                const input = row.querySelector('input');
                if (input) input.value = '';
                return;
            }
            row.remove();
            renumberPassengers();
        });
    </script>
</x-public-shell>
