@extends('layouts.app')

@section('title', 'Property & Registration · '.$vehicle->plate_number)
@section('page-title', 'Property & Registration')

{{-- A vehicle's property record and paperwork, as GSS keeps them in its workbook --}}
@section('content')
    @php
        $label = 'block text-xs font-semibold text-slate-600';
        $field = 'mt-1 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
        $area = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
        $tones = [
            'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'danger' => 'bg-red-50 text-red-700 ring-red-200',
            'neutral' => 'bg-slate-100 text-slate-600 ring-slate-200',
        ];
        $registration = $vehicle->registrationStatus();
        $insurance = $vehicle->insuranceStatus();
        $date = fn ($value) => $value?->format('Y-m-d');
    @endphp

    <div class="mx-auto max-w-5xl space-y-5">
        {{-- Stays at the top while the long form scrolls, so the vehicle stays in view --}}
        <div class="sticky -top-4 z-20 -mx-4 flex flex-col gap-3 border-b border-slate-200/80 bg-gray-50/95 px-4 py-3 backdrop-blur sm:-top-6 sm:-mx-6 sm:flex-row sm:items-end sm:justify-between sm:px-6">
            <div class="min-w-0">
                <a href="{{ route('vehicles.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    Vehicles
                </a>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ $vehicle->plate_number }}</h1>
                <p class="text-sm text-slate-500">{{ trim($vehicle->brand.' '.$vehicle->model.' '.$vehicle->year) }} · {{ $vehicle->location ?: 'No location' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $tones[$registration['tone']] }}">{{ $registration['label'] }}</span>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $tones[$insurance['tone']] }}">{{ $insurance['label'] }}</span>
            </div>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <p class="font-bold">Please fix the following:</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('vehicles.registry.update', $vehicle) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="property-heading">
                <h2 id="property-heading" class="text-sm font-extrabold text-slate-900">Property record</h2>
                <p class="text-xs text-slate-500">As listed in the Inventory of Vehicles.</p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div><label for="par_number" class="{{ $label }}">PAR number</label><input id="par_number" name="par_number" value="{{ old('par_number', $vehicle->par_number) }}" placeholder="e.g. MV-001" class="{{ $field }}"></div>
                    <div><label for="property_number" class="{{ $label }}">Property number</label><input id="property_number" name="property_number" value="{{ old('property_number', $vehicle->property_number) }}" placeholder="e.g. 01-00-MV01" class="{{ $field }}"></div>
                    <div><label for="old_plate_number" class="{{ $label }}">Old / temporary plate</label><input id="old_plate_number" name="old_plate_number" value="{{ old('old_plate_number', $vehicle->old_plate_number) }}" class="{{ $field }}"></div>
                    <div><label for="conduction_sticker" class="{{ $label }}">Conduction sticker</label><input id="conduction_sticker" name="conduction_sticker" value="{{ old('conduction_sticker', $vehicle->conduction_sticker) }}" class="{{ $field }}"></div>
                    <div><label for="acquisition_date" class="{{ $label }}">Date acquired</label><input type="date" id="acquisition_date" name="acquisition_date" value="{{ old('acquisition_date', $date($vehicle->acquisition_date)) }}" class="{{ $field }}"></div>
                    <div><label for="acquisition_cost" class="{{ $label }}">Total value (₱)</label><input type="number" step="0.01" min="0" id="acquisition_cost" name="acquisition_cost" value="{{ old('acquisition_cost', $vehicle->acquisition_cost) }}" class="{{ $field }}"></div>
                    <div><label for="custodian_name" class="{{ $label }}">Custodian</label><input id="custodian_name" name="custodian_name" value="{{ old('custodian_name', $vehicle->custodian_name) }}" class="{{ $field }}"></div>
                    <div>
                        <label for="end_user_office_id" class="{{ $label }}">End user (division)</label>
                        <select id="end_user_office_id" name="end_user_office_id" class="{{ $field }}">
                            <option value="">—</option>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}" @selected((int) old('end_user_office_id', $vehicle->end_user_office_id) === $office->id)>{{ $office->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="assigned_person" class="{{ $label }}">Person assigned the vehicle</label><input id="assigned_person" name="assigned_person" value="{{ old('assigned_person', $vehicle->assigned_person) }}" class="{{ $field }}"></div>
                    <div><label for="book_status" class="{{ $label }}">Book status</label><input id="book_status" name="book_status" list="book-statuses" value="{{ old('book_status', $vehicle->book_status) }}" placeholder="e.g. BOOK UP" class="{{ $field }}"><datalist id="book-statuses"><option value="BOOK UP"><option value="FOR RECONCILIATION"></datalist></div>
                    <div>
                        <label for="condition" class="{{ $label }}">Condition</label>
                        <select id="condition" name="condition" class="{{ $field }}">
                            <option value="">—</option>
                            @foreach ($conditions as $condition)
                                <option value="{{ $condition }}" @selected(old('condition', $vehicle->condition) === $condition)>{{ $condition }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label for="registry_remarks" class="{{ $label }}">Remarks</label><textarea id="registry_remarks" name="registry_remarks" rows="2" placeholder="e.g. w/ fleetcard, PTR #2025-08-0037" class="{{ $area }}">{{ old('registry_remarks', $vehicle->registry_remarks) }}</textarea></div>
                    <div><label for="history" class="{{ $label }}">History</label><textarea id="history" name="history" rows="2" class="{{ $area }}">{{ old('history', $vehicle->history) }}</textarea></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="papers-heading">
                <h2 id="papers-heading" class="text-sm font-extrabold text-slate-900">Registration &amp; insurance</h2>
                <p class="text-xs text-slate-500">The dashboard warns {{ \Modules\Vehicle\Models\Vehicle::PAPERS_DUE_DAYS }} days before either date.</p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="or_cr_status" class="{{ $label }}">OR / CR</label>
                        <select id="or_cr_status" name="or_cr_status" class="{{ $field }}">
                            <option value="">—</option>
                            @foreach ($orCrStatuses as $status)
                                <option value="{{ $status }}" @selected(old('or_cr_status', $vehicle->or_cr_status) === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="registration_place" class="{{ $label }}">Place of registration &amp; renewal</label><input id="registration_place" name="registration_place" value="{{ old('registration_place', $vehicle->registration_place) }}" placeholder="e.g. Manila" class="{{ $field }}"></div>
                    <div><label for="registration_expiry" class="{{ $label }}">Registration valid until</label><input type="date" id="registration_expiry" name="registration_expiry" value="{{ old('registration_expiry', $date($vehicle->registration_expiry)) }}" class="{{ $field }}"></div>
                    <div><label for="renewal_schedule" class="{{ $label }}">Renewal schedule</label><input id="renewal_schedule" name="renewal_schedule" value="{{ old('renewal_schedule', $vehicle->renewal_schedule) }}" placeholder="e.g. 1st week of August" class="{{ $field }}"></div>
                    <div><label for="insurance_type" class="{{ $label }}">Insurance</label><input id="insurance_type" name="insurance_type" list="insurance-types" value="{{ old('insurance_type', $vehicle->insurance_type) }}" placeholder="e.g. TPL / Comprehensive" class="{{ $field }}"><datalist id="insurance-types"><option value="TPL"><option value="Comprehensive"><option value="TPL / Comprehensive"></datalist></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label for="insurance_from" class="{{ $label }}">Insured from</label><input type="date" id="insurance_from" name="insurance_from" value="{{ old('insurance_from', $date($vehicle->insurance_from)) }}" class="{{ $field }}"></div>
                        <div><label for="insurance_to" class="{{ $label }}">Insured until</label><input type="date" id="insurance_to" name="insurance_to" value="{{ old('insurance_to', $date($vehicle->insurance_to)) }}" class="{{ $field }}"></div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="equipment-heading">
                <h2 id="equipment-heading" class="text-sm font-extrabold text-slate-900">Equipment &amp; allowance</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><label for="tire_type" class="{{ $label }}">Tire type</label><input id="tire_type" name="tire_type" value="{{ old('tire_type', $vehicle->tire_type) }}" placeholder="e.g. 255/65/R17" class="{{ $field }}"></div>
                    <div>
                        <label for="has_ewd" class="{{ $label }}">Early warning device</label>
                        @php $ewd = old('has_ewd', $vehicle->has_ewd === null ? '' : (int) $vehicle->has_ewd); @endphp
                        <select id="has_ewd" name="has_ewd" class="{{ $field }}">
                            <option value="" @selected($ewd === '' || $ewd === null)>—</option>
                            <option value="1" @selected((string) $ewd === '1')>Yes</option>
                            <option value="0" @selected((string) $ewd === '0')>No</option>
                        </select>
                    </div>
                    <div><label for="tools" class="{{ $label }}">Tools</label><input id="tools" name="tools" value="{{ old('tools', $vehicle->tools) }}" placeholder="e.g. 1 set jack, 1 tire wrench" class="{{ $field }}"></div>
                    @php $cardAllowance = $vehicle->cardAllowance(); @endphp
                    @if ($cardAllowance !== null)
                        {{-- Kept on the registered card, so the two never disagree --}}
                        <div>
                            <label for="fleet_card_allowance" class="{{ $label }}">Fleet card allowance (₱ / month)</label>
                            <input id="fleet_card_allowance" value="{{ number_format($cardAllowance, 2) }}" readonly aria-describedby="fleet_card_allowance_note" class="{{ $field }} cursor-not-allowed bg-slate-50 text-slate-600">
                            <p id="fleet_card_allowance_note" class="mt-1 text-[0.6875rem] text-slate-500">
                                @php $cardNumbers = $vehicle->fleetCards->whereNotNull('monthly_allowance')->pluck('card_number')->filter()->implode(', '); @endphp
                                {{ $cardNumbers !== '' ? "From fleet card {$cardNumbers}." : 'From the vehicle\'s fleet card.' }}
                                <a href="{{ route('fleet-cards.index') }}" class="font-semibold text-emerald-700 hover:underline">Change it on Fleet Cards</a>
                            </p>
                        </div>
                    @else
                        <div><label for="fleet_card_allowance" class="{{ $label }}">Fleet card allowance (₱ / month)</label><input type="number" step="0.01" min="0" id="fleet_card_allowance" name="fleet_card_allowance" value="{{ old('fleet_card_allowance', $vehicle->fleet_card_allowance) }}" placeholder="e.g. 15000" class="{{ $field }}"></div>
                    @endif
                    {{-- Prices fleet card charges by distance: km ÷ km per litre × price per litre --}}
                    <div>
                        <label for="fuel_economy_km_per_liter" class="{{ $label }}">Fuel economy (km per litre)</label>
                        <input type="number" step="0.1" min="1" max="60" id="fuel_economy_km_per_liter" name="fuel_economy_km_per_liter" value="{{ old('fuel_economy_km_per_liter', $vehicle->fuel_economy_km_per_liter) }}" placeholder="e.g. 10" aria-describedby="fuel_economy_note" class="{{ $field }}">
                        <p id="fuel_economy_note" class="mt-1 text-[0.6875rem] text-slate-500">Needed to charge a fleet card by distance (odometer or km) when no litres are recorded.</p>
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-emerald-700 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">Save details</button>
            </div>
        </form>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="accessories-heading">
            <h2 id="accessories-heading" class="text-sm font-extrabold text-slate-900">Accessories</h2>
            <p class="text-xs text-slate-500">Bedliner, canopy, camera and other items recorded with the vehicle.</p>

            @if ($vehicle->accessories->isNotEmpty())
                {{-- relative: keeps the table's hidden "Remove" header label inside it, so it can't stretch the page --}}
                <div class="relative mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-4">Description</th><th class="py-2 pr-4">Date acquired</th><th class="py-2 pr-4 text-right">Value</th><th class="py-2"><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($vehicle->accessories as $accessory)
                                <tr>
                                    <td class="py-2 pr-4 text-slate-800">{{ $accessory->description }}</td>
                                    <td class="py-2 pr-4 text-slate-600">{{ $accessory->date_acquired?->format('M j, Y') ?? '—' }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-slate-800">{{ $accessory->value !== null ? '₱'.number_format((float) $accessory->value, 2) : '—' }}</td>
                                    {{-- Two-step remove inside the page (the browser's own confirm pop-up can be blocked) --}}
                                    <td class="py-2 text-right" x-data="{ ask: false }">
                                        <button type="button" x-show="! ask" @click="ask = true" class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button>
                                        <form x-show="ask" x-cloak method="POST" action="{{ route('vehicles.accessories.destroy', [$vehicle, $accessory]) }}" class="inline-flex items-center gap-1 whitespace-nowrap">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700">Yes, remove</button>
                                            <button type="button" @click="ask = false" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mt-4 rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">No accessories recorded yet.</p>
            @endif

            <form method="POST" action="{{ route('vehicles.accessories.store', $vehicle) }}" class="mt-4 grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_10rem_9rem_auto]">
                @csrf
                <div><label for="acc-description" class="{{ $label }}">Accessory</label><input id="acc-description" name="description" required maxlength="200" placeholder="e.g. Smart Top Canopy" class="{{ $field }}"></div>
                <div><label for="acc-date" class="{{ $label }}">Date acquired</label><input type="date" id="acc-date" name="date_acquired" class="{{ $field }}"></div>
                <div><label for="acc-value" class="{{ $label }}">Value (₱)</label><input type="number" step="0.01" min="0" id="acc-value" name="value" class="{{ $field }}"></div>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-bold text-emerald-800 hover:bg-emerald-100">Add</button>
            </form>
        </section>
    </div>
@endsection
