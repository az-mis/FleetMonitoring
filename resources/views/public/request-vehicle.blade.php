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

        <form class="space-y-3" method="POST" action="{{ route('request-vehicle.store') }}">
            @csrf
            <div>
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>Your Full Name <span class="text-emerald-300">*</span></span></label>
                <div class="group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <input name="requesterName" required value="{{ old('requesterName') }}" placeholder="e.g. Juan Dela Cruz" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">
                </div>
            </div>

            <label class="flex items-center gap-2 text-[13px] text-emerald-100/80">
                <input type="checkbox" name="requesterIsPassenger" value="1" @checked(old('requesterIsPassenger')) class="h-4 w-4 rounded border-emerald-300/30 bg-[#062512] text-emerald-500">
                I am riding along on this trip as a passenger
            </label>

            <div>
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><span>Location / Office Province <span class="text-emerald-300">*</span></span></label>
                <div class="group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <select name="location" required class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
                        <option value="">— Select Location / Building —</option>
                        @foreach(['Oriental Mindoro', 'Occidental Mindoro', 'Marinduque', 'Palawan', 'Romblon', 'Quezon City Satellite Office'] as $loc)
                            <option value="{{ $loc }}" @selected(old('location') === $loc)>{{ $loc }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

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

            {{-- Custom Searchable Office / Section Dropdown --}}
            <div class="relative" id="custom-office-dropdown-container">
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                    <span>Office / Section <span class="font-normal text-emerald-100/55">(Optional)</span></span>
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
                    class="absolute left-0 right-0 top-full z-50 mt-1.5 hidden rounded-xl border border-emerald-400/30 bg-[#041d0e] p-2 shadow-2xl backdrop-blur-md"
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

            <div>
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span>Contact Number <span class="font-normal text-emerald-100/55">(Optional)</span></span></label>
                <div class="group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <input name="requesterContact" value="{{ old('requesterContact') }}" placeholder="09XX-XXX-XXXX" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg><span>Select Vehicle <span class="text-emerald-300">*</span></span></label>
                <div class="group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                    <select id="vehicle-select" name="vehicleId" required disabled class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
                        <option value="">— Select a location first —</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" data-location="{{ $vehicle->location }}" data-driver-name="{{ $vehicle->assignedDriver->name ?? '' }}" @selected((int) old('vehicleId') === $vehicle->id)>
                                {{ $vehicle->plate_number }} · {{ $vehicle->brand }} {{ $vehicle->model }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Default Assigned Driver — shown once a vehicle is selected, mirrors React's selectedVehicle info box --}}
            <div id="driver-info-box" class="hidden rounded-xl border border-white/10 bg-black/20 px-2.5 py-2 text-[11px] text-emerald-100/80 backdrop-blur">
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Default Assigned Driver: <b id="driver-info-name" class="text-[#1a202c]"></b></span>
                </div>
                <div class="mt-1 text-[10.5px] text-emerald-100/60">Admin reviewers may designate a substitute driver if the default driver is unavailable on your travel date.</div>
            </div>

            {{-- Booking calendar — mirrors React's VehicleBookingCalendar: color-coded
                 approved/pending/maintenance days for the currently selected vehicle. --}}
            <div id="booking-calendar" class="hidden rounded-xl border border-white/15 bg-black/20 p-2.5 backdrop-blur">
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
                <div id="cal-conflict" class="fade-in mt-2.5 hidden items-start gap-1.5 rounded-lg border px-2.5 py-2 text-[11.5px] font-semibold leading-snug"></div>
                <div class="mt-2.5 flex gap-3 text-[10.5px] font-semibold text-white/60">
                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#2f855a"></span>Approved Trip</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#dd6b20"></span>Pending Request</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm" style="background:#b91c1c"></span>Maintenance</span>
                </div>
            </div>

            {{-- Maintenance-only conflict banner for the selected travel date(s) — matches
                 React's separate selectedMaintenanceConflict box, shown below the calendar. --}}
            <div id="maintenance-conflict-box" class="hidden items-start gap-1.5 rounded-xl border border-red-400/45 bg-red-950/40 px-2.5 py-2 text-[12.5px] font-bold text-red-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-[15px] w-[15px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span id="maintenance-conflict-text"></span>
            </div>

            <div>
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><span>Destination <span class="text-emerald-300">*</span></span></label>
                <div class="group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <input name="destination" required value="{{ old('destination') }}" placeholder="e.g. Provincial Capitol, Calapan City" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg><span>Travel Date (From) <span class="text-emerald-300">*</span></span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                        <input type="date" name="travelDate" required value="{{ old('travelDate') }}" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg><span>Travel Date (To, optional)</span></label>
                    <div class="group relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                        <input type="date" name="travelDateEnd" value="{{ old('travelDateEnd') }}" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg><span>Official Purpose <span class="text-emerald-300">*</span></span></label>
                <div class="group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-[11px] h-4 w-4 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                    <textarea name="purpose" required rows="2" placeholder="Describe the official reason or event for this travel request..." class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 py-2 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">{{ old('purpose') }}</textarea>
                </div>
            </div>

            <div>
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Additional Passengers <span class="font-normal text-emerald-100/55">(Optional)</span></span></label>
                <div id="passenger-list" class="space-y-1.5">
                    @php $oldPassengers = old('passengers', ['']); @endphp
                    @foreach($oldPassengers as $index => $pass)
                        <div class="group relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input name="passengers[]" value="{{ $pass }}" placeholder="Passenger {{ $index + 1 }} full name" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none">
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-passenger" class="mt-2 text-[12px] font-bold text-white hover:text-emerald-200">＋ Add another passenger</button>
            </div>

            <div>
                <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold text-white/90"><svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg><span>Previous Trip Ticket Date <span class="font-normal text-emerald-100/55">(Optional)</span></span></label>
                <div class="group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
                    <input type="date" name="previousTripTicketDate" value="{{ old('previousTripTicketDate') }}" class="h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] text-emerald-100 focus:border-emerald-400 focus:outline-none">
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
        const vehicleSelect = document.getElementById('vehicle-select');
        const vehicleOptions = Array.from(vehicleSelect.querySelectorAll('option[data-location]'));
        const previousVehicleId = "{{ old('vehicleId') }}";
        const driverInfoBox = document.getElementById('driver-info-box');
        const driverInfoName = document.getElementById('driver-info-name');

        const updateDriverInfo = () => {
            const selected = vehicleSelect.options[vehicleSelect.selectedIndex];
            const vehicleChosen = Boolean(vehicleSelect.value) && selected;
            if (!vehicleChosen) {
                driverInfoBox.classList.add('hidden');
                return;
            }
            driverInfoName.textContent = selected.dataset.driverName || 'Will be assigned on dispatch';
            driverInfoBox.classList.remove('hidden');
        };

        // ── Booking calendar ──────────────────────────────────────────
        // Mirrors React's VehicleBookingCalendar + selectedMaintenanceConflict.
        const availabilityUrlTemplate = "{{ route('request-vehicle.availability', ['vehicle' => '__ID__']) }}";
        const calendarBox = document.getElementById('booking-calendar');
        const calGrid = document.getElementById('cal-grid');
        const calMonthLabel = document.getElementById('cal-month-label');
        const calConflict = document.getElementById('cal-conflict');
        const maintenanceBox = document.getElementById('maintenance-conflict-box');
        const maintenanceText = document.getElementById('maintenance-conflict-text');
        const travelDateInput = document.querySelector('input[name="travelDate"]');
        const travelDateEndInput = document.querySelector('input[name="travelDateEnd"]');

        let bookings = [];
        let viewYear = new Date().getFullYear();
        let viewMonth = new Date().getMonth();
        let calendarFocused = false;

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

        function bookedDaysMap() {
            const map = new Map();
            bookings.forEach((b) => {
                eachDateKey(b.start, b.end, (key) => {
                    const current = map.get(key);
                    if (
                        b.status === 'maintenance' ||
                        (b.status === 'approved' && current !== 'maintenance') ||
                        (!current && b.status === 'pending')
                    ) {
                        map.set(key, b.status);
                    }
                });
            });
            return map;
        }

        function selectedDateKeys() {
            const set = new Set();
            if (!travelDateInput.value) return set;
            const endVal = travelDateEndInput.value && travelDateEndInput.value >= travelDateInput.value
                ? travelDateEndInput.value
                : travelDateInput.value;
            eachDateKey(travelDateInput.value, endVal, (key) => set.add(key));
            return set;
        }

        function renderCalendar() {
            const map = bookedDaysMap();
            const selected = selectedDateKeys();
            const firstOfMonth = new Date(viewYear, viewMonth, 1);
            const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
            const startWeekday = firstOfMonth.getDay();
            calMonthLabel.textContent = firstOfMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

            const colors = { maintenance: '#b91c1c', approved: '#2f855a', pending: '#dd6b20' };
            let html = '';
            for (let i = 0; i < startWeekday; i++) html += '<div></div>';
            for (let day = 1; day <= daysInMonth; day++) {
                const key = toKey(viewYear, viewMonth, day);
                const status = map.get(key);
                const isSelected = selected.has(key);
                const bg = status ? colors[status] : 'transparent';
                const color = status || isSelected ? '#ffffff' : 'rgba(255,255,255,0.8)';
                const ring = isSelected ? 'box-shadow:inset 0 0 0 1.5px #72ebb0;' : '';
                html += `<div title="${status ? (status === 'maintenance' ? 'Vehicle maintenance' : status === 'approved' ? 'Approved trip' : 'Pending request') : ''}" style="font-size:11px;padding:4px 0;border-radius:5px;background:${bg};color:${color};font-weight:${status || isSelected ? 800 : 500};${ring}">${day}</div>`;
            }
            calGrid.innerHTML = html;

            // Worst-status conflict across the selected dates (any status), matches the calendar's own banner.
            let worst = null;
            for (const key of selected) {
                const status = map.get(key);
                if (status === 'maintenance') { worst = 'maintenance'; break; }
                if (status === 'approved') worst = 'approved';
                if (status === 'pending' && worst !== 'approved') worst = 'pending';
            }
            if (worst) {
                const isPending = worst === 'pending';
                calConflict.className = 'fade-in mt-2.5 flex items-start gap-1.5 rounded-lg border px-2.5 py-2 text-[11.5px] font-semibold leading-snug';
                calConflict.style.background = isPending ? 'rgba(180,100,20,0.35)' : 'rgba(180,40,40,0.35)';
                calConflict.style.color = isPending ? '#feebc8' : '#fed7d7';
                calConflict.style.borderColor = isPending ? 'rgba(237,137,54,0.5)' : 'rgba(245,101,101,0.5)';
                calConflict.innerHTML = `<span>${worst === 'maintenance' ? 'This vehicle has MAINTENANCE on selected date(s).' : worst === 'approved' ? 'This vehicle has an APPROVED trip on selected date(s).' : 'This vehicle has a PENDING request on selected date(s).'}</span>`;
            } else {
                calConflict.classList.add('hidden');
            }

            // Maintenance-only conflict, with the maintenance title — matches React's
            // selectedMaintenanceConflict box (separate from the calendar's own banner).
            let maintenanceHit = null;
            for (const b of bookings) {
                if (b.status !== 'maintenance') continue;
                const overlaps = travelDateInput.value && b.start <= (travelDateEndInput.value || travelDateInput.value) && travelDateInput.value <= b.end;
                if (overlaps) { maintenanceHit = b; break; }
            }
            if (maintenanceHit) {
                maintenanceText.textContent = `This vehicle is under maintenance for the selected date(s): ${maintenanceHit.title}.`;
                maintenanceBox.classList.remove('hidden');
                maintenanceBox.classList.add('flex');
            } else {
                maintenanceBox.classList.add('hidden');
                maintenanceBox.classList.remove('flex');
            }
        }

        function changeMonth(delta) {
            const next = new Date(viewYear, viewMonth + delta, 1);
            viewYear = next.getFullYear();
            viewMonth = next.getMonth();
            renderCalendar();
        }
        document.getElementById('cal-prev').addEventListener('click', () => changeMonth(-1));
        document.getElementById('cal-next').addEventListener('click', () => changeMonth(1));

        function loadAvailability() {
            if (!vehicleSelect.value) {
                bookings = [];
                calendarBox.classList.add('hidden');
                maintenanceBox.classList.add('hidden');
                return;
            }
            const url = availabilityUrlTemplate.replace('__ID__', vehicleSelect.value);
            fetch(url, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((data) => {
                    bookings = data.bookings || [];
                    calendarBox.classList.remove('hidden');
                    if (!calendarFocused && travelDateInput.value) {
                        const [y, m] = travelDateInput.value.split('-').map(Number);
                        if (y && m) { viewYear = y; viewMonth = m - 1; }
                    }
                    renderCalendar();
                })
                .catch(() => {
                    bookings = [];
                    calendarBox.classList.add('hidden');
                });
        }

        travelDateInput.addEventListener('input', () => {
            travelDateEndInput.min = travelDateInput.value || '';
            if (travelDateEndInput.value && travelDateEndInput.value < travelDateInput.value) {
                travelDateEndInput.value = '';
            }
        });
        if (travelDateInput.value) {
            travelDateEndInput.min = travelDateInput.value;
        }

        [travelDateInput, travelDateEndInput].forEach((input) => {
            input.addEventListener('input', () => {
                calendarFocused = true;
                if (travelDateInput.value) {
                    const [y, m] = travelDateInput.value.split('-').map(Number);
                    if (y && m) { viewYear = y; viewMonth = m - 1; }
                }
                renderCalendar();
            });
        });

        const filterVehicles = (isInit = false) => {
            const location = locationSelect.value;
            vehicleSelect.disabled = !location;
            if (!isInit) {
                vehicleSelect.value = '';
            }
            vehicleOptions.forEach((option) => {
                option.hidden = Boolean(location) && option.dataset.location && option.dataset.location !== location;
            });
            if (isInit && previousVehicleId) {
                vehicleSelect.value = previousVehicleId;
            }
            updateDriverInfo();
            calendarFocused = false;
            loadAvailability();
        };

        locationSelect.addEventListener('change', () => filterVehicles(false));
        vehicleSelect.addEventListener('change', () => {
            updateDriverInfo();
            calendarFocused = false;
            loadAvailability();
        });
        document.addEventListener('DOMContentLoaded', () => filterVehicles(true));
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

        document.getElementById('add-passenger').addEventListener('click', () => {
            const list = document.getElementById('passenger-list');
            if (list.children.length >= 20) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'group relative';
            wrapper.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/45 transition-colors group-focus-within:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            const input = document.createElement('input');
            input.name = 'passengers[]';
            input.placeholder = `Passenger ${list.children.length + 1} full name`;
            input.className = 'h-9 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 pl-9 pr-3 text-[12px] placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none';
            wrapper.appendChild(input);
            list.appendChild(wrapper);
        });
    </script>
</body>
</html>
