@extends('layouts.app')

@section('title', 'Approving Officers')
@section('page-title', 'Approving Officers')

@section('content')
    {{-- Page header --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Approving Officers</h1>
            <p class="text-sm text-slate-500">Manage the official signatories (Approved By) printed on Driver's Trip Tickets for each Office Location</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('approving-officers.index') }}" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-96">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        autocomplete="off"
                        aria-label="Search location or officer"
                        placeholder="Search location or officer..."
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                </div>

                @if(request('search'))
                    <a href="{{ route('approving-officers.index') }}" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-slate-700">Clear</a>
                @endif
            </form>

            <button
                type="button"
                onclick="openOfficerModal()"
                class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                <span>Add Office</span>
            </button>
        </div>
    </div>

    {{-- Page-level error summary (the modal is closed after a redirect, so its own summary is not visible) --}}
    @if($errors->any())
        <div role="alert" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-semibold text-rose-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Cards Grid --}}
    @if($officers->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <p class="text-sm font-bold text-slate-800">No approving officers configured</p>
            <p class="mt-1 text-xs text-slate-400">Click "Add Office" above to set up signatories for an office location.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 {{ $officers->count() > 1 ? '2xl:grid-cols-2' : '' }}">
            @foreach($officers as $officer)
                @php
                    $editPayload = [
                        'location' => $officer->location,
                        'signatories' => $officer->signatories->map(function ($s) {
                            return ['name' => $s->name, 'title' => $s->title, 'show_on_ticket' => $s->show_on_ticket];
                        })->values()->all(),
                    ];
                @endphp
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md flex flex-col justify-between">
                    <div>
                        {{-- Card Header --}}
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate text-xs font-black text-slate-900 leading-tight">{{ $officer->location }}</h3>
                                    <p class="text-[0.625rem] font-medium text-slate-400">Trip Ticket Signatories</p>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    onclick="editOfficer({{ \Illuminate\Support\Js::from($editPayload) }})"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-sky-600 transition hover:bg-sky-50 cursor-pointer"
                                    title="Edit"
                                    aria-label="Edit {{ $officer->location }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="m15 5 4 4"/>
                                    </svg>
                                </button>

                                @if(auth()->user()->role?->value === 'super_admin' && $officer->location !== 'Regional Office (Default)')
                                    <form method="POST" action="{{ route('approving-officers.destroy', $officer) }}" onsubmit="return confirm('Remove signatories for {{ $officer->location }}?')" class="inline-flex">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-rose-500 transition hover:bg-rose-50 cursor-pointer"
                                            title="Delete"
                                            aria-label="Delete {{ $officer->location }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Signatories list with quick "On Ticket" selection --}}
                        <form method="POST" action="{{ route('approving-officers.ticket-selection', $officer) }}" class="ticket-select-form mt-3" data-max="{{ \Modules\ApprovingOfficer\Models\ApprovingOfficer::MAX_ON_TICKET }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-[0.6875rem] font-semibold text-slate-500">Tick who appears on the approved Trip Ticket <span class="font-bold text-emerald-700">(1 to 2)</span></p>
                                <span class="text-[0.625rem] font-medium text-slate-400">{{ $officer->signatories->count() }} / {{ \Modules\ApprovingOfficer\Models\ApprovingOfficer::MAX_SIGNATORIES }} signatories</span>
                            </div>

                            @if($officer->signatories->isEmpty())
                                <p class="rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400">No signatories yet. Click the edit icon to add some.</p>
                            @else
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 {{ $officers->count() > 1 ? '' : 'xl:grid-cols-3' }}">
                                    @foreach($officer->signatories as $sig)
                                        <label class="sig-card flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition {{ $sig->show_on_ticket ? 'border-emerald-300 bg-emerald-50/60' : 'border-slate-200 bg-white hover:border-emerald-200' }}">
                                            <input type="checkbox" value="{{ $sig->id }}" data-order="{{ $sig->show_on_ticket ? ($sig->ticket_order ?? 99) : '' }}" {{ $sig->show_on_ticket ? 'checked' : '' }}
                                                   class="ticket-check mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 text-emerald-600 accent-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                            <span class="min-w-0">
                                                <span data-sig-name class="block truncate text-xs font-bold text-slate-800">{{ $sig->name }}</span>
                                                <span class="block text-[0.6875rem] text-slate-500">{{ $sig->title ?: '—' }}</span>
                                                <span class="on-ticket-badge mt-1 {{ $sig->show_on_ticket ? 'inline-block' : 'hidden' }} rounded-full bg-emerald-600 px-2 py-0.5 text-[0.5625rem] font-black uppercase tracking-wide text-white">On Ticket</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Printed order: 1st = top of the ticket. Swap flips the two. --}}
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                    <p class="ticket-preview hidden text-[0.6875rem] font-medium text-slate-500"></p>
                                    <button type="button" class="ticket-swap hidden rounded-lg border border-emerald-300 bg-white px-2.5 py-1 text-[0.6875rem] font-bold text-emerald-700 transition hover:bg-emerald-50 cursor-pointer">&#8645; Swap order</button>
                                </div>

                                <div class="mt-3 flex items-center justify-end gap-3">
                                    <p class="ticket-msg hidden text-[0.6875rem] font-semibold text-rose-600"></p>
                                    <button type="submit" disabled class="ticket-save rounded-xl bg-[#008751] px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-[#007345] disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer">
                                        Save Trip Ticket selection
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>

                    {{-- Updated By Footer --}}
                    <div class="mt-3 border-t border-gray-100 pt-2 text-right">
                        <span class="text-[0.625rem] text-slate-400">
                            Updated by: <strong class="font-semibold text-slate-600">{{ $officer->updatedBy?->name ?: 'System Default' }}</strong>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add/Edit Officer Modal --}}
    <div id="officer-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
        <div role="dialog" aria-modal="true" aria-labelledby="officer-modal-title" class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 id="officer-modal-title" class="text-sm font-bold text-slate-900">Add Approving Officers</h3>
                <button type="button" onclick="closeOfficerModal()" aria-label="Close" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('approving-officers.store') }}" class="p-5 space-y-4" onsubmit="return validateSignatories()">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Location / Office Province <span class="text-rose-500">*</span>
                    </label>

                    {{-- Add mode dropdown --}}
                    <div id="modal-location-select-wrap">
                        <select
                            name="location"
                            id="modal-location-select"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer"
                        >
                            <option value="">— Select Office Location —</option>
                            <option value="Oriental Mindoro">Oriental Mindoro</option>
                            <option value="Occidental Mindoro">Occidental Mindoro</option>
                            <option value="Marinduque">Marinduque</option>
                            <option value="Palawan">Palawan</option>
                            <option value="Romblon">Romblon</option>
                            <option value="Quezon City Satellite Office">Quezon City Satellite Office</option>
                            <option value="Regional Office (Default)">Regional Office (Default)</option>
                        </select>
                    </div>

                    {{-- Edit mode disabled input --}}
                    <div id="modal-location-input-wrap" class="hidden">
                        <input
                            type="text"
                            id="modal-location-input"
                            readonly
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-800"
                        >
                    </div>
                </div>

                @if($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif

                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <p class="text-xs font-bold text-slate-800">Signatories <span id="sig-count" class="font-medium text-slate-400">(0/10)</span></p>
                        <button type="button" id="sig-add-btn" onclick="addSignatoryRow()" class="rounded-lg bg-emerald-50 px-2.5 py-1 text-[0.6875rem] font-bold text-emerald-700 hover:bg-emerald-100 cursor-pointer">+ Add signatory</button>
                    </div>
                    <p class="mb-2 text-[0.6875rem] text-slate-500">Tick the box for who will appear on the approved Trip Ticket (at least 1, maximum 2).</p>
                    <div id="sig-rows" class="max-h-72 space-y-2 overflow-y-auto pr-1"></div>
                    <p id="sig-error" class="mt-2 hidden text-xs font-semibold text-rose-600"></p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        id="modal-submit-btn"
                        class="w-full rounded-xl bg-[#008751] hover:bg-[#007345] py-2.5 text-xs font-bold text-white transition shadow-sm cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Save Approving Officers
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.ticket-select-form').forEach(form => {
            const max = parseInt(form.dataset.max, 10);
            const boxes = [...form.querySelectorAll('.ticket-check')];
            // An officer with no signatories yet has nothing to pick
            if (!boxes.length) return;
            const save = form.querySelector('.ticket-save');
            const msg = form.querySelector('.ticket-msg');
            const swapBtn = form.querySelector('.ticket-swap');
            const preview = form.querySelector('.ticket-preview');
            const ordinal = n => ['1st', '2nd', '3rd'][n - 1] || `${n}th`;
            const nameOf = id => boxes.find(b => b.value === id).closest('.sig-card').querySelector('[data-sig-name]').textContent.trim();

            // Printed order on the ticket: first id = 1st (top). Seeded from the saved order.
            let order = boxes.filter(b => b.checked)
                .sort((a, b) => (parseInt(a.dataset.order, 10) || 99) - (parseInt(b.dataset.order, 10) || 99))
                .map(b => b.value);
            const initial = order.join();

            function refresh() {
                boxes.forEach(b => {
                    const idx = order.indexOf(b.value);
                    const on = idx !== -1;
                    b.checked = on;
                    const card = b.closest('.sig-card');
                    card.classList.toggle('border-emerald-300', on);
                    card.classList.toggle('bg-emerald-50/60', on);
                    card.classList.toggle('border-slate-200', !on);
                    card.classList.toggle('bg-white', !on);
                    const badge = card.querySelector('.on-ticket-badge');
                    badge.classList.toggle('hidden', !on);
                    badge.classList.toggle('inline-block', on);
                    badge.textContent = on ? `On Ticket \u00b7 ${ordinal(idx + 1)}` : 'On Ticket';
                });
                swapBtn.classList.toggle('hidden', order.length < 2);
                preview.classList.toggle('hidden', order.length < 1);
                preview.textContent = order.length
                    ? 'Prints top to bottom: ' + order.map((id, i) => `${ordinal(i + 1)} ${nameOf(id)}`).join(' \u2192 ')
                    : '';
                save.disabled = order.length < 1 || order.join() === initial;
            }

            boxes.forEach(box => box.addEventListener('change', () => {
                if (box.checked) {
                    if (order.length >= max) {
                        box.checked = false;
                        msg.textContent = `Only ${max} signatories can appear on the Trip Ticket. Untick one first.`;
                        msg.classList.remove('hidden');
                        return;
                    }
                    order.push(box.value);   // ticked second = printed second
                } else {
                    order = order.filter(id => id !== box.value);
                }
                msg.classList.add('hidden');
                refresh();
                if (order.length < 1) {
                    msg.textContent = 'Select at least 1 signatory for the Trip Ticket.';
                    msg.classList.remove('hidden');
                }
            }));

            swapBtn.addEventListener('click', () => { order = order.slice().reverse(); refresh(); });

            // Send the ids in printed order (checkbox order on the page is not the chosen order).
            form.addEventListener('submit', () => {
                form.querySelectorAll('input[name="ticket[]"]').forEach(i => i.remove());
                order.forEach(id => {
                    const h = document.createElement('input');
                    h.type = 'hidden'; h.name = 'ticket[]'; h.value = id;
                    form.appendChild(h);
                });
            });

            refresh();
        });

        const MAX_SIGS = {{ \Modules\ApprovingOfficer\Models\ApprovingOfficer::MAX_SIGNATORIES }};
        const MAX_TICKET = {{ \Modules\ApprovingOfficer\Models\ApprovingOfficer::MAX_ON_TICKET }};
        const inputCls = 'w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500';

        function esc(v) { return String(v ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

        function reindexRows() {
            const rows = document.querySelectorAll('#sig-rows [data-sig-row]');
            rows.forEach((row, i) => {
                row.querySelector('[data-f=name]').name = `signatories[${i}][name]`;
                row.querySelector('[data-f=title]').name = `signatories[${i}][title]`;
                row.querySelector('[data-f=show]').name = `signatories[${i}][show_on_ticket]`;
            });
            document.getElementById('sig-count').textContent = `(${rows.length}/${MAX_SIGS})`;
            document.getElementById('sig-add-btn').classList.toggle('hidden', rows.length >= MAX_SIGS);
            enforceTicketLimit();
        }

        function enforceTicketLimit(changed) {
            const boxes = [...document.querySelectorAll('#sig-rows [data-f=show]')];
            const checked = boxes.filter(b => b.checked);
            if (changed && changed.checked && checked.length > MAX_TICKET) {
                changed.checked = false;
                showSigError(`Only ${MAX_TICKET} signatories can appear on the Trip Ticket. Untick one first.`);
                return;
            }
            const full = checked.length >= MAX_TICKET;
            boxes.forEach(b => { b.parentElement.classList.toggle('opacity-50', full && !b.checked); });
            if (changed) showSigError('');
        }

        function showSigError(msg) {
            const el = document.getElementById('sig-error');
            el.textContent = msg;
            el.classList.toggle('hidden', !msg);
        }

        function addSignatoryRow(data = {}) {
            const wrap = document.getElementById('sig-rows');
            if (wrap.children.length >= MAX_SIGS) return;
            const div = document.createElement('div');
            div.setAttribute('data-sig-row', '');
            div.className = 'rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 space-y-2';
            div.innerHTML = `
                <div class="flex items-center gap-2">
                    <input type="text" data-f="name" required maxlength="150" aria-label="Signatory full name" placeholder="Full name" value="${esc(data.name)}" class="${inputCls}">
                    <button type="button" title="Remove" aria-label="Remove signatory" onclick="this.closest('[data-sig-row]').remove(); reindexRows();" class="shrink-0 rounded-lg p-1.5 text-rose-500 hover:bg-rose-50 cursor-pointer">&#10005;</button>
                </div>
                <input type="text" data-f="title" maxlength="150" aria-label="Signatory title or office designation" placeholder="Title / Office designation" value="${esc(data.title)}" class="${inputCls}">
                <label class="flex items-center gap-2 text-[0.6875rem] font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" data-f="show" value="1" ${data.show_on_ticket ? 'checked' : ''} onchange="enforceTicketLimit(this)" class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 accent-emerald-600 focus:ring-emerald-500">
                    Show on approved Trip Ticket
                </label>`;
            wrap.appendChild(div);
            reindexRows();
        }

        function validateSignatories() {
            const rows = document.querySelectorAll('#sig-rows [data-sig-row]');
            const selected = document.querySelectorAll('#sig-rows [data-f=show]:checked').length;
            if (rows.length < 1) { showSigError('Add at least one signatory.'); return false; }
            if (selected < 1 || selected > MAX_TICKET) {
                showSigError(`Select at least 1 and at most ${MAX_TICKET} signatories for the Trip Ticket.`);
                return false;
            }
            document.getElementById('modal-submit-btn').disabled = true; // prevent double submit
            return true;
        }

        function fillRows(list) {
            document.getElementById('sig-rows').innerHTML = '';
            showSigError('');
            (list && list.length ? list : [{ show_on_ticket: true }]).forEach(addSignatoryRow);
        }

        function showModal() {
            document.getElementById('modal-submit-btn').disabled = false;
            document.getElementById('officer-modal').classList.remove('hidden');
            document.getElementById('officer-modal').classList.add('flex');
        }

        function openOfficerModal() {
            document.getElementById('officer-modal-title').textContent = 'Add Approving Officers';
            document.getElementById('modal-location-select-wrap').classList.remove('hidden');
            document.getElementById('modal-location-input-wrap').classList.add('hidden');
            document.getElementById('modal-location-select').required = true;
            document.getElementById('modal-location-select').value = '';
            document.getElementById('modal-location-input').removeAttribute('name');
            document.getElementById('modal-location-select').setAttribute('name', 'location');
            document.getElementById('modal-submit-btn').textContent = 'Save Approving Officers';
            fillRows([]);
            showModal();
        }

        function editOfficer(officer) {
            document.getElementById('officer-modal-title').textContent = 'Edit Approving Officers — ' + officer.location;
            document.getElementById('modal-location-select-wrap').classList.add('hidden');
            document.getElementById('modal-location-input-wrap').classList.remove('hidden');
            document.getElementById('modal-location-select').required = false;
            document.getElementById('modal-location-select').removeAttribute('name');
            document.getElementById('modal-location-input').setAttribute('name', 'location');
            document.getElementById('modal-location-input').value = officer.location || '';
            document.getElementById('modal-submit-btn').textContent = 'Save Changes';
            fillRows(officer.signatories);
            showModal();
        }

        function closeOfficerModal() {
            document.getElementById('officer-modal').classList.add('hidden');
            document.getElementById('officer-modal').classList.remove('flex');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !document.getElementById('officer-modal').classList.contains('hidden')) closeOfficerModal();
        });
    </script>
@endsection
