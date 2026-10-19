@extends('layouts.app')

@section('title', 'Vehicle Inspections')
@section('page-title', 'Vehicle Inspections')

@section('content')
    @php
        $locationOptions = \App\Support\FleetLocations::ALL;
        $selectedLocation = $selectedLocation ?? request('location');
        $locationButtonLabel = $selectedLocation ?: 'All Locations';
    @endphp

    {{-- Page header --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Vehicle Inspections</h1>
            <p class="text-sm text-slate-500">Conduct & manage vehicle inspection checklists and change oil logs</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('vehicles.inspections.page') }}" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-96">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" aria-label="Search inspections" placeholder="Search by plate number, model, notes..." class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
            </form>

            <button type="button" onclick="openNewInspectionModal()" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-800 transition cursor-pointer shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Conduct Inspection</span>
            </button>
        </div>
    </div>

    {{-- Inspection Logs Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                <thead class="bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Inspection Date</th>
                        <th class="px-4 py-3 font-semibold">Vehicle</th>
                        <th class="px-4 py-3 font-semibold">Inspector</th>
                        <th class="px-4 py-3 font-semibold">Odometer</th>
                        <th class="px-4 py-3 font-semibold">Change Oil Status</th>
                        <th class="px-4 py-3 font-semibold">Overall Result</th>
                        <th class="px-4 py-3 text-right font-semibold">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($inspections as $insp)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-900">
                                {{ $insp->inspected_at?->format('M j, Y g:i A') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div>
                                    <span class="font-black text-slate-900 block">{{ $insp->vehicle?->plate_number }}</span>
                                    <span class="text-[0.6875rem] text-slate-500 block">{{ $insp->vehicle?->brand }} {{ $insp->vehicle?->model }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600 font-medium">
                                {{ $insp->inspector?->name ?: 'Admin' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-800">
                                {{ number_format($insp->odometer_km) }} km
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($insp->change_oil_done)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 border border-blue-200 px-2.5 py-0.5 text-[0.625rem] font-extrabold text-blue-700">
                                        🛢️ Change Oil Performed
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($insp->overall_status === 'failed')
                                    <span class="rounded-full bg-red-50 text-red-700 border border-red-200 px-2.5 py-0.5 text-[0.625rem] font-extrabold">✕ Failed</span>
                                @elseif ($insp->overall_status === 'needs_attention')
                                    <span class="rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-0.5 text-[0.625rem] font-extrabold">⚠️ Needs Attention</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 text-[0.625rem] font-extrabold">✓ Passed</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" onclick="viewInspectionDetail({{ json_encode($insp->load('items')) }})" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-[0.6875rem] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                                    View Breakdown
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                    </svg>
                                    <p class="text-xs font-semibold">No inspection records found</p>
                                    <p class="text-[0.6875rem] text-slate-400">Click "+ Conduct Inspection" to log your first checklist inspection.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination bar --}}
        <div class="border-t border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="text-xs text-slate-500 font-medium">
                Showing <span class="font-bold text-slate-700">{{ $inspections->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-700">{{ $inspections->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ $inspections->total() }}</span> results
            </div>
            @if($inspections->hasPages())
                <div>{{ $inspections->links() }}</div>
            @endif
        </div>
    </div>

    {{-- CONDUCT NEW INSPECTION MODAL --}}
    <div id="new-inspection-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div role="dialog" aria-modal="true" aria-labelledby="new-inspection-modal-title" class="w-full max-w-4xl rounded-2xl bg-white p-5 shadow-2xl border border-slate-200 max-h-[92vh] flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-700 border border-blue-200 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </span>
                    <div>
                        <h3 id="new-inspection-modal-title" class="text-base font-black text-slate-900">Conduct Vehicle Inspection</h3>
                        <p class="text-[0.6875rem] text-slate-500">Select a vehicle and fill out the inspection checklist</p>
                    </div>
                </div>
                <button type="button" onclick="closeNewInspectionModal()" aria-label="Close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form id="new-inspection-form" onsubmit="submitNewInspection(event)" class="flex flex-col min-h-0 flex-1">
                @csrf
                <div class="overflow-y-auto py-3 space-y-3 flex-1 pr-1 scrollbar-thin">
                    {{-- Select Vehicle & Odometer --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:items-start">
                            <div class="relative" id="insp-vehicle-picker">
                                <label class="block text-[0.6875rem] font-bold text-slate-700 mb-1">Select Vehicle <span class="text-red-500">*</span></label>
                                <select id="insp-vehicle-select" onchange="onVehicleSelected(this)" required tabindex="-1" aria-hidden="true" class="pointer-events-none absolute bottom-0 left-0 h-px w-full opacity-0">
                                    <option value="">— Choose Vehicle —</option>
                                    @foreach ($allVehicles as $v)
                                        <option value="{{ $v->id }}" data-store-url="{{ route('vehicles.inspections.store', $v) }}" data-odometer="{{ $v->odometer }}">{{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }} ({{ number_format($v->odometer) }} km)</option>
                                    @endforeach
                                </select>

                                <button type="button" id="insp-vp-trigger" onclick="toggleInspVehiclePicker()" class="flex h-[46px] w-full items-center gap-2.5 rounded-lg border border-slate-300 bg-white px-3 text-left text-xs font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <span id="insp-vp-thumb" class="hidden h-8 w-11 shrink-0 items-center justify-center overflow-hidden rounded border border-slate-200 bg-slate-50"></span>
                                    <span id="insp-vp-label" class="min-w-0 flex-1 truncate text-slate-400">— Choose Vehicle —</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>

                                <div id="insp-vp-panel" class="absolute left-0 right-0 z-50 mt-1 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                                    <input type="text" id="insp-vp-search" oninput="filterInspVehiclePicker()" aria-label="Search vehicles" placeholder="Search plate or model..." class="mb-2 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div class="max-h-60 space-y-0.5 overflow-y-auto">
                                        <button type="button" onclick="pickInspVehicle('')" class="insp-vp-opt flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left text-xs font-semibold text-slate-500 hover:bg-blue-50">
                                            — Choose Vehicle —
                                        </button>
                                        @foreach($allVehicles as $v)
                                            <button type="button" onclick="pickInspVehicle('{{ $v->id }}')"
                                                class="insp-vp-opt flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left transition hover:bg-blue-50"
                                                data-id="{{ $v->id }}"
                                                data-plate="{{ $v->plate_number }}"
                                                data-model="{{ trim($v->brand.' '.$v->model) }}"
                                                data-odometer="{{ $v->odometer }}"
                                                data-location="{{ $v->location }}"
                                                data-photo="{{ $v->photo_url ?? '' }}"
                                                data-store-url="{{ route('vehicles.inspections.store', $v) }}"
                                                data-search="{{ strtolower($v->plate_number.' '.$v->brand.' '.$v->model.' '.$v->location) }}">
                                                <span class="flex h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-100 bg-white">
                                                    @if(!empty($v->photo_url))
                                                        <img src="{{ $v->photo_url }}" alt="" class="h-full w-full object-contain">
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>
                                                    @endif
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block text-xs font-bold text-slate-900">{{ $v->plate_number }}</span>
                                                    <span class="block truncate text-[0.6875rem] text-slate-500">{{ trim($v->brand.' '.$v->model) }} @if($v->location) &middot; {{ $v->location }} @endif</span>
                                                </span>
                                            </button>
                                        @endforeach
                                        <p id="insp-vp-empty" class="hidden px-3 py-3 text-center text-xs text-slate-400">No vehicles found.</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[0.6875rem] font-bold text-slate-700 mb-1">Current Odometer Reading (KM) <span class="text-red-500">*</span></label>
                                <div class="flex h-[46px] items-center">
                                    <input type="number" name="odometer_km" id="new-insp-odometer" min="0" required placeholder="0" class="h-full w-full rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 focus:border-blue-500 focus:outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[0.6875rem] font-bold text-slate-700 mb-1">&nbsp;</label>
                                <label class="flex h-[46px] items-center gap-2.5 cursor-pointer bg-white border border-slate-200 rounded-lg px-3 hover:border-emerald-300 transition">
                                    <input type="checkbox" name="change_oil_done" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                    <div class="min-w-0 leading-tight">
                                        <span class="text-xs font-bold text-slate-800 block">🛢️ Mark Change Oil Done</span>
                                        <span class="text-[0.625rem] text-slate-400 block truncate">Resets change oil counter to current odometer</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Checklist Items Grouped by Category --}}
                    @php
                        $checklistCategories = [
                            'Oils & Fluids' => [
                                'Engine Oil Level & Condition',
                                'Brake Fluid Level',
                                'Transmission Fluid Level',
                                'Radiator Coolant Level',
                                'Windshield Washer Fluid',
                            ],
                            'Brakes & Steering' => [
                                'Service Brake Operation',
                                'Parking / Hand Brake',
                                'Power Steering Fluid / Steering Play',
                            ],
                            'Tires & Wheels' => [
                                'Front & Rear Tire Pressure',
                                'Tire Tread Condition',
                                'Spare Tire Condition & Air',
                                'Jack & Lug Wrench Tools',
                            ],
                            'Electrical & Lights' => [
                                'Headlights (Low & High Beam)',
                                'Turn Signal Lights & Hazard Warning',
                                'Brake Lights & Reverse Lights',
                                'Horn Operation',
                                'Battery Terminals & Charge Status',
                            ],
                            'Body & Cabin' => [
                                'Windshield Wipers & Washers',
                                'Side & Rearview Mirrors',
                                'Seatbelts Functionality',
                                'Air Conditioning Performance',
                                'Cleanliness & Body Condition',
                            ],
                        ];
                        $itemIndex = 0;
                    @endphp

                    @foreach ($checklistCategories as $catName => $catItems)
                        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-2xs">
                            <h4 class="text-[0.6875rem] font-black uppercase tracking-wider text-slate-700 pb-1.5 border-b border-slate-100 mb-2">{{ $catName }}</h4>
                            <div class="space-y-1.5">
                                @foreach ($catItems as $itemName)
                                    @php $idx = $itemIndex++; @endphp
                                    <input type="hidden" name="items[{{ $idx }}][category]" value="{{ $catName }}">
                                    <input type="hidden" name="items[{{ $idx }}][name]" value="{{ $itemName }}">
                                    <div class="flex flex-col gap-1.5 text-xs py-0.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                                        <span class="font-medium text-slate-800 min-w-0 sm:shrink sm:truncate" title="{{ $itemName }}">{{ $itemName }}</span>

                                        <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                                            <div class="flex items-center gap-0.5 bg-slate-100 p-0.5 rounded-lg">
                                                <label class="relative inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[0.6875rem] font-bold cursor-pointer transition has-[:checked]:bg-emerald-600 has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500 text-slate-600">
                                                    <input type="radio" name="items[{{ $idx }}][status]" value="pass" checked class="sr-only">
                                                    <span>✓ Pass</span>
                                                </label>
                                                <label class="relative inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[0.6875rem] font-bold cursor-pointer transition has-[:checked]:bg-amber-500 has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500 text-slate-600">
                                                    <input type="radio" name="items[{{ $idx }}][status]" value="attention" class="sr-only">
                                                    <span>⚠️ Attention</span>
                                                </label>
                                                <label class="relative inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[0.6875rem] font-bold cursor-pointer transition has-[:checked]:bg-red-600 has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500 text-slate-600">
                                                    <input type="radio" name="items[{{ $idx }}][status]" value="fail" class="sr-only">
                                                    <span>✕ Fail</span>
                                                </label>
                                            </div>

                                            <input type="text" name="items[{{ $idx }}][remarks]" aria-label="Remarks for {{ $itemName }}" placeholder="Remarks (opt)" class="w-full sm:w-32 rounded-md border border-slate-200 px-2 py-0.5 text-[0.6875rem] text-slate-700 focus:border-blue-500 focus:outline-none">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">General Notes / Remarks</label>
                        <textarea name="notes" rows="2" placeholder="Optional inspector notes..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 shrink-0">
                    <button type="button" onclick="closeNewInspectionModal()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="new-insp-submit-btn" class="rounded-xl bg-blue-700 px-5 py-2 text-xs font-bold text-white hover:bg-blue-800 transition cursor-pointer">
                        Save Inspection Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- VIEW INSPECTION DETAIL BREAKDOWN MODAL --}}
    <div id="view-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div role="dialog" aria-modal="true" aria-labelledby="detail-modal-title" class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl border border-slate-200 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 shrink-0">
                <div>
                    <h3 class="text-base font-black text-slate-900" id="detail-modal-title">Inspection Report Detail</h3>
                    <p class="text-xs text-slate-500" id="detail-modal-subtitle">—</p>
                </div>
                <button type="button" onclick="closeViewDetailModal()" aria-label="Close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div id="detail-modal-body" class="overflow-y-auto py-4 space-y-3 flex-1 pr-1 scrollbar-thin">
                {{-- Dynamic detail --}}
            </div>
        </div>
    </div>

    <script>
        // HTML-escape user-provided values before interpolating them into innerHTML templates.
        function escHtml(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        let selectedVehicleStoreUrl = '';

        function toggleInspVehiclePicker() {
            const panel = document.getElementById('insp-vp-panel');
            const isHidden = panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !isHidden);
            if (isHidden) {
                document.getElementById('insp-vp-search').value = '';
                filterInspVehiclePicker();
                setTimeout(() => document.getElementById('insp-vp-search').focus(), 50);
            }
        }

        function filterInspVehiclePicker() {
            const q = (document.getElementById('insp-vp-search').value || '').toLowerCase().trim();
            let count = 0;
            document.querySelectorAll('.insp-vp-opt').forEach(btn => {
                const text = btn.getAttribute('data-search') || '';
                if (!q || text.includes(q) || !btn.getAttribute('data-id')) {
                    btn.classList.remove('hidden');
                    count++;
                } else {
                    btn.classList.add('hidden');
                }
            });
            document.getElementById('insp-vp-empty').classList.toggle('hidden', count > 0);
        }

        function pickInspVehicle(id) {
            document.getElementById('insp-vehicle-select').value = id;
            document.getElementById('insp-vp-panel').classList.add('hidden');

            const thumb = document.getElementById('insp-vp-thumb');
            const label = document.getElementById('insp-vp-label');

            if (!id) {
                selectedVehicleStoreUrl = '';
                thumb.classList.add('hidden');
                label.className = 'min-w-0 flex-1 truncate text-slate-400';
                label.innerText = '— Choose Vehicle —';
                document.getElementById('new-insp-odometer').value = '';
                return;
            }

            const btn = document.querySelector(`.insp-vp-opt[data-id="${id}"]`);
            if (btn) {
                selectedVehicleStoreUrl = btn.dataset.storeUrl || '';
                document.getElementById('new-insp-odometer').value = btn.dataset.odometer || 0;

                const photo = btn.dataset.photo;
                const plate = btn.dataset.plate;
                const model = btn.dataset.model;

                if (photo) {
                    thumb.innerHTML = `<img src="${escHtml(photo)}" class="h-full w-full object-contain">`;
                    thumb.classList.remove('hidden');
                } else {
                    thumb.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>`;
                    thumb.classList.remove('hidden');
                }
                label.className = 'min-w-0 flex-1 text-slate-900';
                label.innerHTML = `<span class="font-bold">${escHtml(plate)}</span> <span class="text-xs text-slate-500">(${escHtml(model)})</span>`;
            }
        }

        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('insp-vehicle-picker');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('insp-vp-panel')?.classList.add('hidden');
            }
        });

        function openNewInspectionModal() {
            pickInspVehicle('');
            const modal = document.getElementById('new-inspection-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeNewInspectionModal() {
            const modal = document.getElementById('new-inspection-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            // Reset the entire form
            const form = document.getElementById('new-inspection-form');
            if (form) form.reset();

            // Clear state
            selectedVehicleStoreUrl = '';
            pickInspVehicle('');
        }

        function submitNewInspection(e) {
            e.preventDefault();
            if (!selectedVehicleStoreUrl) {
                alert('Please select a vehicle first.');
                return;
            }

            const form = document.getElementById('new-inspection-form');
            const submitBtn = document.getElementById('new-insp-submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            fetch(selectedVehicleStoreUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: new FormData(form)
            })
            .then(async res => {
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    // Server returned non-JSON (e.g. HTML error page)
                    console.error('Non-JSON server response:', text.substring(0, 500));
                    throw new Error(`Server error (${res.status}): The server returned an unexpected response. Check the console for details.`);
                }
                if (!res.ok) {
                    // Laravel validation errors come as 422 with {errors: {...}, message: "..."}
                    const errMsg = data.message || Object.values(data.errors || {}).flat().join('\n') || `Server error ${res.status}`;
                    throw new Error(errMsg);
                }
                return data;
            })
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Inspection Report';
                if (data.success) {
                    closeNewInspectionModal();
                    window.location.reload();
                } else {
                    alert(data.message || 'Error saving inspection.');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Inspection Report';
                console.error('Inspection submit error:', err);
                alert(err.message || 'An error occurred. Please try again.');
            });
        }

        function viewInspectionDetail(insp) {
            const modal = document.getElementById('view-detail-modal');
            const title = document.getElementById('detail-modal-title');
            const subtitle = document.getElementById('detail-modal-subtitle');
            const body = document.getElementById('detail-modal-body');

            title.textContent = `Inspection: ${insp.vehicle?.plate_number || 'Vehicle'}`;
            const dateStr = new Date(insp.inspected_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            subtitle.textContent = `Inspected on ${dateStr} by ${insp.inspector?.name || 'Admin'}`;

            let itemsHtml = '';
            (insp.items || []).forEach(it => {
                const icon = it.status === 'pass' ? '✓' : (it.status === 'attention' ? '⚠️' : '✕');
                const color = it.status === 'pass' ? 'text-emerald-600' : (it.status === 'attention' ? 'text-amber-600 font-bold' : 'text-red-600 font-bold');
                itemsHtml += `
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 text-xs">
                        <span class="font-semibold text-slate-800">${escHtml(it.item_name)} <span class="text-[0.625rem] text-slate-400">(${escHtml(it.category)})</span></span>
                        <span class="${color}">${icon} ${escHtml(String(it.status ?? '').toUpperCase())} ${it.remarks ? '— ' + escHtml(it.remarks) : ''}</span>
                    </div>
                `;
            });

            body.innerHTML = `
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800">Odometer at Inspection:</span>
                            <span class="font-extrabold text-slate-900">${Number(insp.odometer_km).toLocaleString()} km</span>
                        </div>
                        <div>
                            ${insp.change_oil_done ? '<span class="rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 text-[0.625rem] font-bold">🛢️ Oil Changed</span>' : ''}
                        </div>
                    </div>
                    ${insp.notes ? `<div class="text-slate-600 italic pt-1 border-t border-slate-200/60">"${escHtml(insp.notes)}"</div>` : ''}
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-2">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100">Checklist Items Breakdown</h4>
                    <div class="space-y-1">
                        ${itemsHtml}
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeViewDetailModal() {
            const modal = document.getElementById('view-detail-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Escape closes the open vehicle picker first, otherwise the topmost open modal via its existing close function.
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;

            const picker = document.getElementById('insp-vp-panel');
            if (picker && !picker.classList.contains('hidden')) {
                picker.classList.add('hidden');
                document.getElementById('insp-vp-trigger')?.focus();
                return;
            }

            const modals = [
                ['new-inspection-modal', closeNewInspectionModal],
                ['view-detail-modal', closeViewDetailModal],
            ];
            for (let i = modals.length - 1; i >= 0; i--) {
                const el = document.getElementById(modals[i][0]);
                if (el && !el.classList.contains('hidden')) {
                    modals[i][1]();
                    break;
                }
            }
        });
    </script>
@endsection
