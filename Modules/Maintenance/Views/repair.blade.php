@extends('layouts.app')

@section('title', 'Repair & Procurement · '.$record->plate_number)
@section('page-title', 'Repair & Procurement')

{{-- A maintenance record's repair details and purchase requests, as GSS keeps them in its workbook --}}
@section('content')
    @php
        $label = 'block text-xs font-semibold text-slate-600';
        $field = 'mt-1 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
        $area = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
        $tones = [
            'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'info' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'neutral' => 'bg-slate-100 text-slate-600 ring-slate-200',
        ];
        $date = fn ($value) => $value?->format('Y-m-d');
        $peso = fn ($value) => $value !== null ? '₱'.number_format((float) $value, 2) : '—';
        $vehicle = $record->vehicle;
        $driverName = $vehicle?->assignedDriver?->name;
        $schedule = $record->scheduled_date?->format('M j, Y').($record->scheduled_date_end && $record->scheduled_date_end->ne($record->scheduled_date) ? ' – '.$record->scheduled_date_end->format('M j, Y') : '');
    @endphp

    <div class="mx-auto max-w-5xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('maintenance.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    Maintenance
                </a>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ $record->plate_number }} <span class="font-semibold text-slate-500">· {{ $record->title }}</span></h1>
                <p class="text-sm text-slate-500">{{ $record->type }} · {{ ucfirst($record->status) }} · {{ $schedule }}{{ $record->location ? ' · '.$record->location : '' }}</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Repair cost {{ $peso($record->cost) }}</span>
                @if ($latest = $record->procurements->first())
                    <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold ring-1 ring-inset {{ $tones[$latest->tone()] }}">PR: {{ $latest->status }}</span>
                @endif
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

        <form method="POST" action="{{ route('maintenance.repair.update', $record) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="request-heading">
                <h2 id="request-heading" class="text-sm font-extrabold text-slate-900">Repair request</h2>
                <p class="text-xs text-slate-500">As listed in the Index for Repair of Vehicles.</p>
                <dl class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 text-xs sm:grid-cols-4">
                    <div><dt class="font-semibold text-slate-500">Vehicle</dt><dd class="font-semibold text-slate-800">{{ $record->vehicle_label ?: trim(($vehicle?->brand ?? '').' '.($vehicle?->model ?? '').' '.($vehicle?->year ?? '')) ?: '—' }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Property number</dt><dd class="font-semibold text-slate-800">{{ $vehicle?->property_number ?: '—' }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Driver assigned</dt><dd class="font-semibold text-slate-800">{{ $driverName ?: '—' }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Repair cost</dt><dd class="font-semibold text-slate-800">{{ $peso($record->cost) }} <span class="font-normal text-slate-500">(set in Edit)</span></dd></div>
                </dl>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="requisitioner_office_id" class="{{ $label }}">Requisitioner / end user</label>
                        <select id="requisitioner_office_id" name="requisitioner_office_id" class="{{ $field }}">
                            <option value="">{{ $vehicle?->endUserOffice ? 'Same as vehicle ('.$vehicle->endUserOffice->name.')' : '—' }}</option>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}" @selected((int) old('requisitioner_office_id', $record->requisitioner_office_id) === $office->id)>{{ $office->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="pre_inspection_date" class="{{ $label }}">Pre-repair inspection date</label><input type="date" id="pre_inspection_date" name="pre_inspection_date" value="{{ old('pre_inspection_date', $date($record->pre_inspection_date)) }}" class="{{ $field }}"></div>
                    <div><label for="pm_request_no" class="{{ $label }}">PM request no. <span class="font-normal text-slate-400">(PMS only)</span></label><input id="pm_request_no" name="pm_request_no" maxlength="30" value="{{ old('pm_request_no', $record->pm_request_no) }}" class="{{ $field }}"></div>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label for="defect_nature" class="{{ $label }}">Nature of defect / complaint</label><textarea id="defect_nature" name="defect_nature" rows="3" placeholder="e.g. Wheel bearing noise, worn brake pads" class="{{ $area }}">{{ old('defect_nature', $record->defect_nature) }}</textarea></div>
                    <div><label for="parts_replaced" class="{{ $label }}">Parts to be replaced</label><textarea id="parts_replaced" name="parts_replaced" rows="3" placeholder="e.g. Wheel bearing inner/outer, brake pad" class="{{ $area }}">{{ old('parts_replaced', $record->parts_replaced) }}</textarea></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="post-heading">
                <h2 id="post-heading" class="text-sm font-extrabold text-slate-900">Post-repair inspection &amp; current market value</h2>
                <p class="text-xs text-slate-500">
                    As listed in the Pre &amp; Post Repair CM Value sheet. Acquired {{ $vehicle?->acquisition_date?->format('M j, Y') ?? '—' }} for {{ $peso($vehicle?->acquisition_cost) }}
                    @if ($vehicle)
                        (<a href="{{ route('vehicles.registry', $vehicle) }}" class="font-semibold text-emerald-700 hover:underline">property record</a>).
                    @endif
                </p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><label for="post_inspection_date" class="{{ $label }}">Post-repair inspection date</label><input type="date" id="post_inspection_date" name="post_inspection_date" value="{{ old('post_inspection_date', $date($record->post_inspection_date)) }}" class="{{ $field }}"></div>
                    <div><label for="current_market_value" class="{{ $label }}">Current market value (₱)</label><input type="number" step="0.01" min="0" id="current_market_value" name="current_market_value" value="{{ old('current_market_value', $record->current_market_value) }}" class="{{ $field }}"></div>
                    <div><label for="cmv_issued_at" class="{{ $label }}">CM value issued on</label><input type="date" id="cmv_issued_at" name="cmv_issued_at" value="{{ old('cmv_issued_at', $date($record->cmv_issued_at)) }}" class="{{ $field }}"></div>
                    <div><label for="cmv_issued_by" class="{{ $label }}">Issued by</label><input id="cmv_issued_by" name="cmv_issued_by" list="cmv-issuers" maxlength="100" value="{{ old('cmv_issued_by', $record->cmv_issued_by) }}" placeholder="e.g. GSS-QC" class="{{ $field }}"><datalist id="cmv-issuers"><option value="GSS-QC"><option value="GSS-ORIENTAL"><option value="GSS-PALAWAN"></datalist></div>
                </div>
                <div class="mt-4"><label for="post_inspection_findings" class="{{ $label }}">Post-repair findings</label><textarea id="post_inspection_findings" name="post_inspection_findings" rows="2" placeholder="e.g. Replaced wheel bearing inner/outer and brake pad" class="{{ $area }}">{{ old('post_inspection_findings', $record->post_inspection_findings) }}</textarea></div>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-emerald-700 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">Save repair details</button>
            </div>
        </form>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="pr-heading">
            <h2 id="pr-heading" class="text-sm font-extrabold text-slate-900">Purchase requests</h2>
            <p class="text-xs text-slate-500">PR, JO/PO and COA dates for this work, as tracked in the PR of Goods and Services sheet.</p>

            <div class="mt-4 space-y-3">
                @forelse ($record->procurements as $procurement)
                    @php $formId = 'pr-'.$procurement->id; @endphp
                    <article class="rounded-xl border border-slate-200 p-4" x-data="{ edit: {{ old('_form') === $formId ? 'true' : 'false' }}, ask: false }">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $procurement->description }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $procurement->category }}
                                    · PR {{ $procurement->pr_number ?: '—' }}{{ $procurement->pr_date ? ' ('.$procurement->pr_date->format('M j, Y').')' : '' }}
                                    · {{ $peso($procurement->amount) }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    JO/PO {{ $procurement->jo_po_number ?: '—' }}{{ $procurement->jo_po_date ? ' ('.$procurement->jo_po_date->format('M j, Y').')' : '' }}
                                    · COA {{ $procurement->coa_date?->format('M j, Y') ?? '—' }}
                                </p>
                                @if ($procurement->remarks)
                                    <p class="mt-1 text-xs text-slate-600">{{ $procurement->remarks }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $tones[$procurement->tone()] }}">{{ $procurement->status }}</span>
                                <button type="button" x-show="! edit && ! ask" @click="edit = true" class="rounded-md px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Edit</button>
                                <button type="button" x-show="! edit && ! ask" @click="ask = true" class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button>
                                {{-- Two-step remove inside the page (the browser's own confirm pop-up can be blocked) --}}
                                <form x-show="ask" x-cloak method="POST" action="{{ route('maintenance.procurements.destroy', [$record, $procurement]) }}" class="inline-flex items-center gap-1 whitespace-nowrap">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700">Yes, remove</button>
                                    <button type="button" @click="ask = false" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                                </form>
                            </div>
                        </div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('maintenance.procurements.update', [$record, $procurement]) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                            @csrf
                            @method('PUT')
                            @include('maintenance::partials.procurement-fields', ['p' => $procurement, 'id' => $formId])
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="edit = false" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                                <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-800">Save</button>
                            </div>
                        </form>
                    </article>
                @empty
                    <p class="rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">No purchase request recorded for this work yet.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('maintenance.procurements.store', $record) }}" class="mt-5 space-y-3 rounded-xl bg-slate-50 p-4">
                @csrf
                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Add a purchase request</p>
                @include('maintenance::partials.procurement-fields', ['p' => null, 'id' => 'pr-new'])
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-bold text-emerald-800 hover:bg-emerald-100">Add purchase request</button>
                </div>
            </form>
        </section>
    </div>
@endsection
