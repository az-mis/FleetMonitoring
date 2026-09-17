<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request a Vehicle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#082b16] text-white antialiased">
<main class="min-h-screen bg-cover bg-center px-4 py-6 sm:px-8" style="background-image:linear-gradient(rgba(5,38,18,.68),rgba(5,38,18,.78)),url('{{ asset('FleetMS-bg.jpg') }}');">
    <div class="mx-auto max-w-2xl rounded-3xl border border-emerald-300/30 bg-[#0a351b]/90 p-6 shadow-2xl backdrop-blur-sm sm:p-8">
        <nav class="mb-6 flex items-center justify-between border-b border-emerald-200/15 pb-4 text-xs text-emerald-100/75">
            <a href="{{ route('check-status') }}" class="hover:text-white">⌕ Check Request Status</a>
            <span class="rounded-full border border-emerald-400/40 bg-emerald-950/60 px-3 py-1 font-semibold uppercase tracking-wide text-emerald-300">▣ Public Request Form</span>
            <a href="{{ route('login') }}" class="hover:text-white">Staff Login →</a>
        </nav>
        <div class="mb-7 flex items-center gap-3">
            <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="Agency logo" class="h-11 w-11 rounded-full bg-white object-contain p-1">
            <div><h1 class="text-2xl font-bold">Official Vehicle Request</h1><p class="mt-1 text-sm text-emerald-100/70">Complete the form below to request government vehicle transport for official travel. A unique tracking code will be generated upon submission.</p></div>
        </div>
        <form class="space-y-4" method="POST" action="{{ route('request-vehicle.store') }}">
            @csrf
            <div><label class="mb-1 block text-xs font-semibold">Your Full Name <span class="text-emerald-300">*</span></label><input name="requesterName" required placeholder="e.g. Juan Dela Cruz" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div>
            <label class="flex items-center gap-2 text-xs text-emerald-100/80"><input type="checkbox" name="requesterIsPassenger" class="rounded border-emerald-300/30 bg-[#062512] text-emerald-500"> I am riding along on this trip as a passenger</label>
            <div><label class="mb-1 block text-xs font-semibold">Location / Office Province <span class="text-emerald-300">*</span></label><select name="location" required class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"><option value="">— Select Location / Building —</option><option>Oriental Mindoro</option><option>Occidental Mindoro</option><option>Marinduque</option><option>Palawan</option><option>Romblon</option><option>Quezon City Satellite Office</option></select></div>
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
                <label class="mb-1 block text-xs font-semibold text-emerald-100">
                    Office / Section <span class="font-normal text-emerald-100/55">(Optional)</span>
                </label>

                {{-- Hidden input for form submission --}}
                <input type="hidden" name="requesterOffice" id="requester-office-input" value="">

                {{-- Trigger Button --}}
                <button
                    type="button"
                    id="office-dropdown-trigger"
                    onclick="toggleOfficeDropdown()"
                    class="flex w-full items-center justify-between rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-left text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none transition cursor-pointer"
                >
                    <span id="office-dropdown-selected-label" class="truncate text-emerald-100/40">— Select your office / division —</span>
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
            <div><label class="mb-1 block text-xs font-semibold">Contact Number <span class="font-normal text-emerald-100/55">(Optional)</span></label><input name="requesterContact" placeholder="09XX-XXX-XXXX" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div>
            <div><label class="mb-1 block text-xs font-semibold">Select Vehicle <span class="text-emerald-300">*</span></label><select id="vehicle-select" name="vehicleId" required disabled class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"><option value="">— Select a location first —</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" data-location="{{ $vehicle->location }}">{{ $vehicle->plate_number }} · {{ $vehicle->brand }} {{ $vehicle->model }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-xs font-semibold">Destination <span class="text-emerald-300">*</span></label><input name="destination" required placeholder="e.g. Provincial Capitol, Calapan City" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div>
            <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-xs font-semibold">Travel Date (From) <span class="text-emerald-300">*</span></label><input type="date" name="travelDate" required class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"></div><div><label class="mb-1 block text-xs font-semibold">Travel Date (To, optional)</label><input type="date" name="travelDateEnd" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"></div></div>
            <div><label class="mb-1 block text-xs font-semibold">Official Purpose <span class="text-emerald-300">*</span></label><textarea name="purpose" required rows="3" placeholder="Describe the official reason or event for this travel request..." class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></textarea></div>
            <div><label class="mb-1 block text-xs font-semibold">Additional Passengers <span class="font-normal text-emerald-100/55">(Optional)</span></label><div id="passenger-list"><input name="passengers[]" placeholder="Passenger 1 full name" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div><button type="button" id="add-passenger" class="mt-2 text-xs font-semibold text-emerald-200 hover:text-white">＋ Add another passenger</button></div>
            <div><label class="mb-1 block text-xs font-semibold">Previous Trip Ticket Date <span class="font-normal text-emerald-100/55">(Optional)</span></label><input type="date" name="previousTripTicketDate" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"></div>
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-green-500 px-4 py-3 text-sm font-bold shadow-lg hover:from-emerald-400 hover:to-green-400">Submit Vehicle Request&nbsp; →</button>
        </form>
        <footer class="mt-5 border-t border-emerald-200/15 pt-4 text-center text-xs text-emerald-100/55">♧ Official Government Fleet Management System<br><span class="text-[10px]">© {{ date('Y') }} {{ config('app.name', 'Fleet Monitoring') }}</span></footer>
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
        locationSelect.addEventListener('change', () => {
            const location = locationSelect.value;
            vehicleSelect.disabled = !location;
            vehicleSelect.value = '';
            vehicleOptions.forEach((option) => {
                option.hidden = Boolean(location) && option.dataset.location && option.dataset.location !== location;
            });
        });
        document.getElementById('add-passenger').addEventListener('click', () => {
            const list = document.getElementById('passenger-list');
            if (list.children.length >= 20) return;
            const input = document.createElement('input');
            input.name = 'passengers[]';
            input.placeholder = `Passenger ${list.children.length + 1} full name`;
            input.className = 'mt-2 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none';
            list.appendChild(input);
        });
    </script>
</body>
</html>
