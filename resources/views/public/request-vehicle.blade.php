<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request a Vehicle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen bg-[#082b16] text-white antialiased">
    {{-- Fixed Full-screen Background --}}
    <div class="fixed inset-0 -z-10 bg-cover bg-center bg-fixed" style="background-image:linear-gradient(rgba(5,38,18,.68),rgba(5,38,18,.78)),url('{{ asset('FleetMS-bg.jpg') }}');"></div>

<main class="min-h-screen px-4 py-6 sm:px-8">
    <div class="mx-auto max-w-2xl rounded-3xl border border-emerald-300/30 bg-[#0a351b]/90 p-5 shadow-2xl backdrop-blur-sm sm:p-6">
        <nav class="mb-4 flex items-center justify-between border-b border-emerald-200/15 pb-3 text-xs text-emerald-100/75">
            <a href="{{ route('check-status') }}" class="hover:text-white">⌕ Check Request Status</a>
            <span class="rounded-full border border-emerald-400/40 bg-emerald-950/60 px-3 py-1 font-semibold uppercase tracking-wide text-emerald-300">▣ Public Request Form</span>
            <a href="{{ route('login') }}" class="hover:text-white">Staff Login →</a>
        </nav>
        <div class="mb-4 flex items-center gap-2.5">
            <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="Agency logo" class="h-10 w-10 rounded-full bg-white object-contain p-1">
            <div><h1 class="text-xl font-bold">Official Vehicle Request</h1><p class="mt-0.5 text-[12.5px] leading-snug text-emerald-100/70">Complete the form below to request government vehicle transport for official travel. A unique tracking code will be generated upon submission.</p></div>
        </div>
        @if (isset($errors) && $errors->any())
            <div class="mb-4 rounded-xl border border-rose-400/40 bg-rose-950/80 p-4 text-xs text-rose-200">
                <p class="font-bold text-rose-100 mb-1">Please fix the following issues before submitting:</p>
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
$offices = [
    ['value' => 'ORED', 'label' => 'Office of the Regional Executive Director (ORED)'],
    ['value' => 'RAFIS', 'label' => 'Regional Agriculture and Fisheries Information Section (RAFIS)'],
    ['value' => 'ORTDO', 'label' => 'Office of the Regional Technical Director for Operations (ORTDO)'],
    ['value' => 'FOD', 'label' => 'Field Operations Division (FOD)'],
    ['value' => 'FOD-RICE', 'label' => 'FOD - Rice Banner Program'],
    ['value' => 'FOD-CORN', 'label' => 'FOD - Corn Banner Program'],
    ['value' => 'FOD-LIVESTOCK', 'label' => 'FOD - Livestock Program'],
    ['value' => 'FOD-HVCDP', 'label' => 'FOD - High Value Crops Development Program (HVCDP)'],
    ['value' => 'FOD-IDS', 'label' => 'FOD - Institutional Development Section (IDS)'],
    ['value' => 'FOD-DRRM', 'label' => 'FOD - Disaster Risk Reduction and Management (DRRM)'],
    ['value' => 'FOD-RSBSA', 'label' => 'FOD - Registry System for Basic Sectors in Agriculture (RSBSA)'],
    ['value' => 'FOD-SAAD', 'label' => 'FOD - Special Area for Agricultural Development (SAAD)'],
    ['value' => 'FOD-4KS', 'label' => 'FOD - Kabuhayan at Kaunlaran ng Kababayang Katututbo (4Ks)'],
    ['value' => 'FOD-NUPAP', 'label' => 'FOD - National Urban and Peri-Urban Agriculture Program (NUPAP)'],
    ['value' => 'FOD-F2C2', 'label' => 'FOD - Farm and Fisheries Clustering and Consolidation Program (F2C2)'],
    ['value' => 'FOD-PAFES', 'label' => 'FOD - Province-led Agriculture and Fisheries Extension Systems (PAFES)'],
    ['value' => 'FOD-HFIDP', 'label' => 'FOD - Halal Food Industry Development Program (HFIDP)'],
    ['value' => 'FOD-PRDP', 'label' => 'FOD - Philippine Rural Development Program (PRDP)'],
    ['value' => 'ORTDRR', 'label' => 'Office of the Regional Technical Director for Research & Regulations (ORTDRR)'],
    ['value' => 'RD', 'label' => 'Research Division (RD)'],
    ['value' => 'ORMAES', 'label' => 'Oriental Mindoro Agricultural Experiment Station (ORMAES)'],
    ['value' => 'PRES', 'label' => 'Palawan Research Experiment Station (PRES)'],
    ['value' => 'PRES-LRC', 'label' => 'PRES - Livestock Resource Center (LRC)'],
    ['value' => 'PRES-DPDC', 'label' => 'PRES - Dairy Production and Development Center (DPDC)'],
    ['value' => 'PRES-PAC', 'label' => 'PRES - Palawan Agricultural Center (PAC)'],
    ['value' => 'PRES-ABOABO', 'label' => 'PRES - Abo-Abo Rural Agricultural Center'],
    ['value' => 'REGDIV', 'label' => 'Regulatory Division'],
    ['value' => 'REGDIV-OAP', 'label' => 'Regulatory Division - Organic Agriculture Program'],
    ['value' => 'AFD', 'label' => 'Administrative and Finance Division (AFD)'],
    ['value' => 'AFD-ACCTG', 'label' => 'AFD - Accounting Section'],
    ['value' => 'AFD-CASH', 'label' => 'AFD - Cashiering Unit'],
    ['value' => 'AFD-BUDGET', 'label' => 'AFD - Budget Section'],
    ['value' => 'AFD-HRS', 'label' => 'AFD - Human Resource Section'],
    ['value' => 'AFD-RECORDS', 'label' => 'AFD - Records Section'],
    ['value' => 'AFD-GSS', 'label' => 'AFD - General Services Section'],
    ['value' => 'AFD-PSU', 'label' => 'AFD - Property & Supply Unit'],
    ['value' => 'BAC', 'label' => 'Bids & Awards Committee (BAC)'],
    ['value' => 'PMED', 'label' => 'Planning, Monitoring & Evaluation Division (PMED)'],
    ['value' => 'MIS', 'label' => 'Management Information Systems Section (MIS)'],
    ['value' => 'RAFC', 'label' => 'Regional Agricultural and Fishery Council (RAFC)'],
    ['value' => 'AMAD', 'label' => 'Agribusiness & Marketing Assistance Division (AMAD)'],
    ['value' => 'ILD', 'label' => 'Integrated Laboratory Division (ILD)'],
    ['value' => 'ILD-RSL', 'label' => 'ILD - Regional Soils Laboratory'],
    ['value' => 'ILD-RCPC', 'label' => 'ILD - Regional Crop Protection Center (RCPC)'],
    ['value' => 'RAED', 'label' => 'Regional Agricultural Engineering Division (RAED)'],
    ['value' => 'APCO-OCCIDENTAL', 'label' => 'APCO - Occidental Mindoro (San Jose)'],
    ['value' => 'APCO-ORIENTAL', 'label' => 'APCO - Oriental Mindoro (Calapan City)'],
    ['value' => 'APCO-MARINDUQUE', 'label' => 'APCO - Marinduque (Boac)'],
    ['value' => 'APCO-ROMBLON', 'label' => 'APCO - Romblon (Odiongan)'],
    ['value' => 'APCO-PALAWAN', 'label' => 'APCO - Palawan (Puerto Princesa City)'],
    ['value' => 'COA', 'label' => 'Commission on Audit (COA)'],
];
@endphp

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>Your Full Name <span class="text-emerald-300">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input name="requesterName" required value="{{ old('requesterName') }}" placeholder="e.g. Juan Dela Cruz" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">
                    </div>
                </div>

                {{-- Custom Searchable Office / Section Dropdown --}}
                <div class="relative" id="custom-office-dropdown-container">
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                        <span>Your Office / Section <span class="font-normal text-emerald-100/55">(Optional)</span></span>
                    </label>

                    {{-- Hidden input for form submission --}}
                    <input type="hidden" name="requesterOffice" id="requester-office-input" value="{{ old('requesterOffice') }}">

                    {{-- Trigger Button --}}
                    <button
                        type="button"
                        id="office-dropdown-trigger"
                        onclick="toggleOfficeDropdown()"
                        class="group flex h-9 w-full items-center justify-between rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-left text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none transition cursor-pointer relative"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                        <span id="office-dropdown-selected-label" class="truncate {{ old('requesterOffice') ? 'text-emerald-100' : 'text-emerald-100/40' }}">{{ old('requesterOffice') ?: '— Select your office / division —' }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-emerald-300/70 transition-transform duration-200" id="office-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    {{-- Dropdown Menu Panel --}}
                    <div
                        id="office-dropdown-panel"
                        class="absolute left-0 right-0 sm:left-auto sm:w-96 top-full z-50 mt-1.5 hidden rounded-xl border border-emerald-400/30 bg-[#041d0e] p-2 shadow-2xl backdrop-blur-md"
                    >
                        {{-- Search Bar inside Dropdown --}}
                        <div class="relative mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-emerald-300/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                            <input
                                type="text"
                                id="office-search-input"
                                oninput="filterOfficeOptions()"
                                placeholder="Search office..."
                                class="w-full rounded-lg border border-emerald-500/30 bg-[#021208] py-1.5 pl-8 pr-3 text-xs text-white placeholder:text-emerald-100/40 focus:border-emerald-400 focus:outline-none"
                            >
                        </div>

                        {{-- Options List --}}
                        <div id="office-options-list" class="max-h-56 overflow-y-auto space-y-0.5 pr-1 scrollbar-thin">
                            <div
                                onclick="selectOffice('', '— Select your office / division —')"
                                class="office-option-item flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-xs text-emerald-100/60 hover:bg-emerald-800/40 hover:text-emerald-200 transition"
                                data-label="— select your office / division —"
                            >
                                <span>— Select your office / division —</span>
                            </div>
                            @foreach($offices as $office)
                                <div
                                    onclick="selectOffice('{{ addslashes($office['label']) }}')"
                                    class="office-option-item flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-xs text-emerald-100 hover:bg-emerald-800/50 hover:text-white transition"
                                    data-label="{{ strtolower($office['label'] . ' ' . $office['value']) }}"
                                >
                                    <span>{{ $office['label'] }}</span>
                                </div>
                            @endforeach
                            <div id="no-office-found" class="hidden px-3 py-4 text-center text-xs text-emerald-100/50">
                                No office found matching search.
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><span>From (Station) <span class="text-emerald-300">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <select name="location" required class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
                            <option value="">— Select Your Station —</option>
                            @foreach(['Oriental Mindoro', 'Occidental Mindoro', 'Marinduque', 'Palawan', 'Romblon', 'Quezon City Satellite Office'] as $loc)
                                <option value="{{ $loc }}" @selected(old('location') === $loc)>{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><span>To (Destination) <span class="text-emerald-300">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <input name="destination" required value="{{ old('destination') }}" placeholder="e.g. Provincial Capitol, Calapan City" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">
                    </div>
                </div>
            </div>


            {{-- Station calendar + vehicle cards. Hidden until a station is chosen. --}}
            <div id="station-panel" class="hidden space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg><span>Travel Date (From) <span class="text-emerald-300">*</span></span></label>
                        <div class="group relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                            <input type="date" name="travelDate" required value="{{ old('travelDate') }}" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg><span>Travel Date (To) <span class="text-emerald-300">*</span></span></label>
                        <div class="group relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                            <input type="date" name="travelDateEnd" required value="{{ old('travelDateEnd') }}" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div id="booking-calendar" class="rounded-xl border border-white/15 bg-black/20 p-2.5 backdrop-blur">
                    <div class="mb-2 flex items-center justify-between">
                        <button type="button" id="cal-prev" aria-label="Previous month" class="flex h-[26px] w-[26px] items-center justify-center rounded-md border border-white/20 bg-white/10 text-sm text-white">‹</button>
                        <span id="cal-month-label" class="text-xs font-bold text-white"></span>
                        <button type="button" id="cal-next" aria-label="Next month" class="flex h-[26px] w-[26px] items-center justify-center rounded-md border border-white/20 bg-white/10 text-sm text-white">›</button>
                    </div>
                    <div class="grid grid-cols-7 gap-[3px] text-center">
                        @foreach(['S','M','T','W','T','F','S'] as $d)
                            <div class="py-0.5 text-[9.5px] font-bold text-white/50">{{ $d }}</div>
                        @endforeach
                    </div>
                    <div id="cal-grid" class="grid grid-cols-7 gap-[3px] text-center"></div>
                    <p class="mt-2 text-[10.5px] text-white/55">Tip: click your travel date to see the available vehicles, then click your end date. Click the same date again for a one-day trip.</p>
                    <div class="mt-2.5 flex flex-wrap gap-x-3 gap-y-1 text-[10.5px] font-semibold text-white/60">
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:transparent;border:1px solid rgba(255,255,255,.45)"></span>No requests yet</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#2f855a"></span>Approved Trip</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#dd6b20"></span>Pending Request</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#b91c1c"></span>Maintenance</span>
                    </div>
                </div>

                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><span>Select Vehicle <span class="text-emerald-300">*</span></span></label>
                    <p id="vehicle-hint" class="mb-2 text-[11px] text-emerald-100/70"></p>
                    <input type="hidden" name="vehicleId" id="vehicle-id-input" value="{{ old('vehicleId') }}">
                    <div id="vehicle-cards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;"></div>
                    <p class="mt-2 text-[10.5px] text-emerald-100/60">The driver shown is the vehicle's default driver. Admin reviewers may designate a substitute if the default driver is unavailable on your travel date.</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg><span>Official Purpose <span class="text-emerald-300">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-[11px] h-4 w-4 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                        <textarea name="purpose" required rows="5" placeholder="Describe the official reason or event for this travel request..." class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 py-2 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">{{ old('purpose') }}</textarea>
                    </div>
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Additional Passengers <span class="font-normal text-emerald-100/55">(Optional)</span></span></label>
                    <label class="mb-2 flex items-center gap-2 text-[13px] text-emerald-100/80">
                        <input type="checkbox" name="requesterIsPassenger" value="1" @checked(old('requesterIsPassenger')) class="h-4 w-4 rounded border-emerald-300/30 bg-[#062512] text-emerald-500">
                        I am riding along on this trip as a passenger
                    </label>
                    <div id="passenger-list" style="display:flex;flex-direction:column;gap:8px;">
                        @php $oldPassengers = old('passengers', ['']); @endphp
                        @foreach($oldPassengers as $index => $pass)
                            <div class="group relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <input name="passengers[]" value="{{ $pass }}" placeholder="Passenger {{ $index + 1 }} full name" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-8 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">
                                <button type="button" data-remove-passenger aria-label="Remove passenger" title="Remove passenger" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:22px;height:22px;border:0;border-radius:999px;background:rgba(255,255,255,.1);color:#fca5a5;font-size:15px;line-height:1;cursor:pointer;">×</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-passenger" class="mt-2 text-[12px] font-bold text-white hover:text-emerald-200">＋ Add another passenger</button>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span>Contact Number <span class="font-normal text-red-300/80">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <input name="requesterContact" value="{{ old('requesterContact') }}" placeholder="09171234567" inputmode="numeric" maxlength="11" pattern="09[0-9]{9}" required title="Must start with 09 and be exactly 11 digits" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg><span>Previous Trip Ticket Date <span class="font-normal text-emerald-100/55">(Optional)</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                        <input type="date" name="previousTripTicketDate" value="{{ old('previousTripTicketDate') }}" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-green-500 px-4 py-3 text-sm font-bold shadow-lg hover:from-emerald-400 hover:to-green-400">Submit Vehicle Request&nbsp; →</button>
        </form>
        <footer class="mt-3 border-t border-emerald-200/15 pt-3 text-center text-xs text-emerald-100/55">♧ Official Government Fleet Management System<br><span class="text-[10px]">© {{ date('Y') }} {{ config('app.name', 'Fleet Monitoring') }}</span></footer>
    </div>
    </main>
    <script>
        function toggleOfficeDropdown() {
            const panel = document.getElementById('office-dropdown-panel');
            const chevron = document.getElementById('office-dropdown-chevron');
            const searchInput = document.getElementById('office-search-input');
            
            if (!panel) return;
            const isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
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
        }

        function selectOffice(val, displayLabel = null) {
            const hiddenInput = document.getElementById('requester-office-input');
            const labelSpan = document.getElementById('office-dropdown-selected-label');
            
            if (hiddenInput) hiddenInput.value = val;
            if (labelSpan) {
                labelSpan.textContent = val || (displayLabel || '— Select your office / division —');
                if (val) {
                    labelSpan.classList.remove('text-emerald-100/40');
                    labelSpan.classList.add('text-emerald-100');
                } else {
                    labelSpan.classList.remove('text-emerald-100');
                    labelSpan.classList.add('text-emerald-100/40');
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

        document.addEventListener('click', (e) => {
            const container = document.getElementById('custom-office-dropdown-container');
            if (container && !container.contains(e.target)) {
                closeOfficeDropdown();
            }
        });

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
                const color = info || inRange ? '#ffffff' : 'rgba(255,255,255,0.8)';
                const ring = inRange ? 'box-shadow:inset 0 0 0 1.5px #72ebb0;' : '';
                const title = info
                    ? `${info.busy} of ${stationVehicles.length} vehicle(s) booked · ${labels[info.worst]}`
                    : 'No requests yet';
                html += `<button type="button" data-date="${key}" title="${esc(title)}" style="width:100%;border:0;font-family:inherit;font-size:11px;padding:4px 0;border-radius:5px;background:${bg};color:${color};font-weight:${info || inRange ? 700 : 500};cursor:pointer;${ring}">${day}</button>`;
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

        const PLACEHOLDER_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>';

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
                cardsBox.innerHTML = '<p style="grid-column:1/-1;font-size:12px;color:rgba(254,215,215,.9);">No vehicles are available on this date. Try another date.</p>';
                return;
            }

            cardsBox.innerHTML = visible.map(({ v, c }) => {
                const isSelected = String(v.id) === String(vehicleIdInput.value);
                const blocked = Boolean(c.maintenance);
                let note = '';
                if (c.maintenance) {
                    note = { bg: 'rgba(185,28,28,.55)', head: '⛔ Under maintenance', sub: `${esc(c.maintenance.title || '')} · ${fmtRange(c.maintenance.start, c.maintenance.end)}` };
                } else if (c.approved) {
                    note = { bg: 'rgba(185,28,28,.45)', head: '⚠ Already approved', sub: fmtRange(c.approved.start, c.approved.end) };
                } else if (c.pending) {
                    note = { bg: 'rgba(221,107,32,.5)', head: '⚠ Pending request', sub: fmtRange(c.pending.start, c.pending.end) };
                }
                const border = isSelected ? '#72ebb0' : (note ? 'rgba(252,165,165,.55)' : 'rgba(167,243,208,.3)');
                const photo = v.photo
                    ? `<img src="${esc(v.photo)}" alt="${esc(v.plate)}" style="width:auto;max-width:100%;height:84px;object-fit:cover;border-radius:8px;">`
                    : PLACEHOLDER_SVG;
                const initial = esc((v.driver || '?').trim().charAt(0).toUpperCase());
                const avatar = v.driver_photo
                    ? `<img src="${esc(v.driver_photo)}" alt="${esc(v.driver)}" style="width:32px;height:32px;border-radius:999px;object-fit:cover;border:2px solid #6ee7b7;flex-shrink:0;">`
                    : `<span style="width:32px;height:32px;border-radius:999px;background:${v.driver ? '#059669' : 'rgba(255,255,255,.18)'};border:2px solid ${v.driver ? '#6ee7b7' : 'rgba(255,255,255,.3)'};display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;flex-shrink:0;">${v.driver ? initial : '?'}</span>`;
                const driverBadge = `<span title="Assigned driver" style="max-width:100%;display:flex;align-items:center;gap:6px;background:rgba(3,25,12,.85);border:1px solid rgba(110,231,183,.45);border-radius:999px;padding:3px 10px 3px 3px;">${avatar}<span style="min-width:0;line-height:1.15;text-align:left;"><span style="display:block;font-size:8.5px;text-transform:uppercase;letter-spacing:.06em;color:rgba(167,243,208,.75);">Driver</span><span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11.5px;font-weight:700;">${esc(v.driver || 'Not assigned')}</span></span></span>`;
                return `<button type="button" data-vehicle="${v.id}" ${blocked ? 'disabled' : ''} style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;gap:6px;padding:8px;border-radius:14px;border:2px solid ${border};background:${isSelected ? 'rgba(16,185,129,.22)' : 'rgba(0,0,0,.25)'};color:#fff;text-align:center;font-family:inherit;cursor:${blocked ? 'not-allowed' : 'pointer'};opacity:${blocked ? .6 : 1};">
                    <div style="width:100%;flex-shrink:0;display:flex;justify-content:${isSelected ? 'space-between' : 'flex-end'};align-items:center;gap:4px;">
                        ${isSelected ? '<span style="font-size:10px;font-weight:800;background:#10b981;color:#052e16;border-radius:999px;padding:1px 7px;">✓ Selected</span>' : ''}
                        ${driverBadge}
                    </div>
                    <div style="width:100%;height:92px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">${photo}</div>
                    <div style="width:100%;margin-top:auto;">
                        <div style="display:flex;align-items:baseline;justify-content:center;gap:6px;min-width:0;white-space:nowrap;"><span style="font-size:14px;font-weight:800;letter-spacing:.04em;flex-shrink:0;">${esc(v.plate)}</span><span title="${esc(v.name)}" style="font-size:10px;color:rgba(209,250,229,.75);overflow:hidden;text-overflow:ellipsis;min-width:0;">${esc(v.name)}</span></div>
                        ${note ? `<div style="margin-top:6px;border-radius:8px;padding:4px 6px;line-height:1.25;background:${note.bg};color:#fff;"><div style="font-size:10.5px;font-weight:800;">${note.head}</div><div style="font-size:9.5px;font-weight:600;opacity:.92;">${note.sub}</div></div>` : ''}
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

        locationSelect.addEventListener('change', () => loadStation(false));
        document.addEventListener('DOMContentLoaded', () => {
            rangeConfirmed = Boolean(travelDateInput.value && travelDateEndInput.value);
            loadStation(true);
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
                vehicleHint.style.color = '#fca5a5';
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
            wrapper.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            const input = document.createElement('input');
            input.name = 'passengers[]';
            input.className = 'h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-8 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none';
            wrapper.appendChild(input);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.removePassenger = '';
            remove.setAttribute('aria-label', 'Remove passenger');
            remove.title = 'Remove passenger';
            remove.textContent = '×';
            remove.style.cssText = 'position:absolute;right:6px;top:50%;transform:translateY(-50%);width:22px;height:22px;border:0;border-radius:999px;background:rgba(255,255,255,.1);color:#fca5a5;font-size:15px;line-height:1;cursor:pointer;';
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
</body>
</html>
