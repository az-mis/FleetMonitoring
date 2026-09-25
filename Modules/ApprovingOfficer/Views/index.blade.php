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

    {{-- Cards Grid --}}
    @if($officers->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <p class="text-sm font-bold text-slate-800">No approving officers configured</p>
            <p class="mt-1 text-xs text-slate-400">Click "Add Office" above to set up signatories for an office location.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($officers as $officer)
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
                                    <p class="text-[10px] font-medium text-slate-400">Trip Ticket Signatories</p>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    onclick="editOfficer({{ json_encode($officer) }})"
                                    class="flex h-6 w-6 items-center justify-center rounded-lg text-sky-600 transition hover:bg-sky-50 cursor-pointer"
                                    title="Edit"
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
                                            class="flex h-6 w-6 items-center justify-center rounded-lg text-rose-500 transition hover:bg-rose-50 cursor-pointer"
                                            title="Delete"
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

                        {{-- Signatories Blocks --}}
                        <div class="mt-3 space-y-2.5">
                            {{-- Signatory 1 --}}
                            <div class="rounded-xl border border-emerald-100/70 bg-emerald-50/40 p-2.5">
                                <p class="text-[9px] font-black uppercase tracking-wider text-emerald-700">SIGNATORY 1 (APPROVED BY)</p>
                                <p class="mt-0.5 text-xs font-bold text-slate-800">{{ $officer->signee1_name ?: '—' }}</p>
                                <p class="text-[10px] text-slate-500">{{ $officer->signee1_title ?: '—' }}</p>
                            </div>

                            {{-- Signatory 2 --}}
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-2.5">
                                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">SIGNATORY 2 (APPROVED BY)</p>
                                <p class="mt-0.5 text-xs font-bold text-slate-800">{{ $officer->signee2_name ?: '—' }}</p>
                                <p class="text-[10px] text-slate-500">{{ $officer->signee2_title ?: '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Updated By Footer --}}
                    <div class="mt-3 border-t border-gray-100 pt-2 text-right">
                        <span class="text-[10px] text-slate-400">
                            Updated by: <strong class="font-semibold text-slate-600">{{ $officer->updatedBy?->name ?: 'System Default' }}</strong>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add/Edit Officer Modal --}}
    <div id="officer-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 id="officer-modal-title" class="text-sm font-bold text-slate-900">Add Approving Officers</h3>
                <button type="button" onclick="closeOfficerModal()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('approving-officers.store') }}" class="p-5 space-y-4">
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

                {{-- Signatory 1 Box --}}
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/30 p-3.5 space-y-3">
                    <p class="text-xs font-bold text-emerald-800">Signatory 1 (First Approving Officer)</p>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Full Name</label>
                        <input
                            type="text"
                            name="signee1_name"
                            id="modal-signee1-name"
                            placeholder="Name"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Official Title / Office Designation</label>
                        <input
                            type="text"
                            name="signee1_title"
                            id="modal-signee1-title"
                            placeholder="Designation"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>
                </div>

                {{-- Signatory 2 Box --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-3.5 space-y-3">
                    <p class="text-xs font-bold text-slate-700">Signatory 2 (Second Approving Officer)</p>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Full Name</label>
                        <input
                            type="text"
                            name="signee2_name"
                            id="modal-signee2-name"
                            placeholder="Name"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Official Title / Office Designation</label>
                        <input
                            type="text"
                            name="signee2_title"
                            id="modal-signee2-title"
                            placeholder="Designation"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        id="modal-submit-btn"
                        class="w-full rounded-xl bg-[#008751] hover:bg-[#007345] py-2.5 text-xs font-bold text-white transition shadow-sm cursor-pointer"
                    >
                        Save Approving Officers
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openOfficerModal() {
            document.getElementById('officer-modal-title').textContent = 'Add Approving Officers';
            document.getElementById('modal-location-select-wrap').classList.remove('hidden');
            document.getElementById('modal-location-input-wrap').classList.add('hidden');
            document.getElementById('modal-location-select').required = true;
            document.getElementById('modal-location-select').value = '';
            document.getElementById('modal-location-input').removeAttribute('name');
            document.getElementById('modal-location-select').setAttribute('name', 'location');
            document.getElementById('modal-signee1-name').value = '';
            document.getElementById('modal-signee1-title').value = '';
            document.getElementById('modal-signee2-name').value = '';
            document.getElementById('modal-signee2-title').value = '';
            document.getElementById('modal-submit-btn').textContent = 'Save Approving Officers';
            document.getElementById('officer-modal').classList.remove('hidden');
            document.getElementById('officer-modal').classList.add('flex');
        }

        function editOfficer(officer) {
            document.getElementById('officer-modal-title').textContent = 'Edit Approving Officers — ' + officer.location;
            document.getElementById('modal-location-select-wrap').classList.add('hidden');
            document.getElementById('modal-location-input-wrap').classList.remove('hidden');
            document.getElementById('modal-location-select').required = false;
            document.getElementById('modal-location-select').removeAttribute('name');
            document.getElementById('modal-location-input').setAttribute('name', 'location');
            document.getElementById('modal-location-input').value = officer.location || '';
            document.getElementById('modal-signee1-name').value = officer.signee1_name || '';
            document.getElementById('modal-signee1-title').value = officer.signee1_title || '';
            document.getElementById('modal-signee2-name').value = officer.signee2_name || '';
            document.getElementById('modal-signee2-title').value = officer.signee2_title || '';
            document.getElementById('modal-submit-btn').textContent = 'Save Changes';
            document.getElementById('officer-modal').classList.remove('hidden');
            document.getElementById('officer-modal').classList.add('flex');
        }

        function closeOfficerModal() {
            document.getElementById('officer-modal').classList.add('hidden');
            document.getElementById('officer-modal').classList.remove('flex');
        }
    </script>
@endsection
