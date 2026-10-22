@extends('layouts.app')

@section('title', 'Fleet Card Monthly Spend · '.$year)
@section('page-title', 'Fleet Card Monthly Spend')

{{-- Spend per division and month, laid out like the GSS "Index for Fleetcard" sheet --}}
@section('content')
    @php
        $label = 'block text-xs font-semibold text-slate-600';
        $field = 'mt-1 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
        $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $money = fn ($v) => $v === null ? '' : number_format((float) $v, 2);
        $rows = $grid['rows'];
        $counted = $rows->where('excluded', false);
        $excluded = $rows->where('excluded', true);
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('fleet-cards.index', array_filter(['location' => $canPickLocation ? $location : null])) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    Fleet Cards
                </a>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Monthly Spend {{ $year }}</h1>
                <p class="text-sm text-slate-500">{{ $location ?: 'All locations' }} · per division, as in the Index for Fleetcard sheet</p>
            </div>
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="f-year" class="{{ $label }}">Year</label>
                    <select id="f-year" name="year" onchange="this.form.submit()" class="{{ $field }} w-28">
                        @foreach (range((int) now()->format('Y') + 1, 2024) as $y)
                            <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="f-provider" class="{{ $label }}">Provider</label>
                    <select id="f-provider" name="provider" onchange="this.form.submit()" class="{{ $field }} w-32">
                        <option value="">All</option>
                        @foreach ($providers as $p)
                            <option value="{{ $p }}" @selected($p === $provider)>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($canPickLocation)
                    <div>
                        <label for="f-location" class="{{ $label }}">Location</label>
                        <select id="f-location" name="location" onchange="this.form.submit()" class="{{ $field }} w-56">
                            <option value="">All locations</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc }}" @selected($loc === $location)>{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <noscript><button class="h-10 rounded-lg border border-slate-300 px-3 text-sm font-semibold">Show</button></noscript>
            </form>
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

        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm" aria-labelledby="grid-heading">
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 pt-5">
                <h2 id="grid-heading" class="text-sm font-extrabold text-slate-900">Spend per division (₱)</h2>
                {{-- How to read the amounts: words in each style, not sample figures that could pass for real ones --}}
                <p class="flex flex-wrap items-center gap-3 text-[0.6875rem] text-slate-500">
                    <span><span class="font-semibold text-slate-800">Bold</span> = statement</span>
                    <span><span class="italic text-slate-500">Italic</span> = from logged expenses</span>
                    <span><span class="rounded bg-amber-100 px-1 font-semibold text-amber-800">Highlighted</span> = over allowance</span>
                </p>
            </div>

            @if ($rows->isEmpty())
                <p class="m-5 rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">No fleet card spend recorded for {{ $year }} yet. Record a statement amount below, or log expenses on the cards.</p>
            @else
                {{-- relative: keeps the sticky Division column and hidden labels inside the scroll area --}}
                <div class="relative mt-4 overflow-x-auto">
                    <table class="min-w-full text-xs tabular-nums">
                        <thead>
                            <tr class="border-y border-slate-200 bg-slate-50 text-left text-[0.625rem] font-bold uppercase tracking-wide text-slate-500">
                                <th class="sticky left-0 z-10 bg-slate-50 px-4 py-2 shadow-[6px_0_8px_-6px_rgba(15,23,42,0.15)]">Division / program</th>
                                <th class="px-3 py-2">Provider</th>
                                @foreach ($months as $m)
                                    <th class="px-3 py-2 text-right">{{ $m }}</th>
                                @endforeach
                                <th class="px-3 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-right">Allowance / mo.</th>
                            </tr>
                        </thead>
                        @foreach ([['rows' => $counted, 'title' => null], ['rows' => $excluded, 'title' => 'Excluded from the total']] as $group)
                            @continue($group['rows']->isEmpty())
                            <tbody class="divide-y divide-slate-100">
                                @if ($group['title'])
                                    <tr><td colspan="16" class="bg-slate-50 px-4 py-1.5 text-[0.625rem] font-bold uppercase tracking-wide text-slate-500">{{ $group['title'] }}</td></tr>
                                @endif
                                @foreach ($group['rows'] as $row)
                                    <tr class="group hover:bg-slate-50">
                                        <td class="sticky left-0 z-10 max-w-[16rem] truncate bg-white px-4 py-2 font-semibold text-slate-800 shadow-[6px_0_8px_-6px_rgba(15,23,42,0.15)] group-hover:bg-slate-50" title="{{ $row['label'] }}">{{ $row['label'] }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $row['provider'] }}</td>
                                        @foreach ($months as $n => $m)
                                            @php
                                                $cell = $row['months'][$n];
                                                $over = $cell && $row['allowance'] !== null && $cell['amount'] > $row['allowance'];
                                            @endphp
                                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                                @if ($cell)
                                                    <span @class([
                                                        'rounded px-1',
                                                        'bg-amber-100 font-semibold text-amber-800' => $over,
                                                        'italic text-slate-500' => ! $over && $cell['source'] === 'logs',
                                                        'font-semibold text-slate-800' => ! $over && $cell['source'] !== 'logs',
                                                    ]) title="{{ $cell['source'] === 'logs' ? 'From expenses logged on the cards' : ($cell['source'] === 'import' ? 'Imported from the GSS workbook' : ($cell['source'] === 'mixed' ? 'Statements for some areas, logged expenses for the others' : 'From the provider statement')) }}">{{ $money($cell['amount']) }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="whitespace-nowrap px-3 py-2 text-right font-bold text-slate-900">{{ $money($row['total']) }}</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right text-slate-600">{{ $row['allowance'] !== null ? $money($row['allowance']) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @endforeach
                        <tfoot>
                            <tr class="border-t-2 border-slate-300 bg-slate-50 font-bold text-slate-900">
                                <td class="sticky left-0 z-10 bg-slate-50 px-4 py-2 shadow-[6px_0_8px_-6px_rgba(15,23,42,0.15)]">Total</td>
                                <td class="px-3 py-2"></td>
                                @foreach ($months as $n => $m)
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ $grid['totals'][$n] > 0 ? $money($grid['totals'][$n]) : '' }}</td>
                                @endforeach
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($grid['grand']) }}</td>
                                <td class="px-4 py-2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
            <div class="h-5"></div>
        </section>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,26rem)_1fr]">
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm" aria-labelledby="record-heading">
                <h2 id="record-heading" class="text-sm font-extrabold text-slate-900">Record a statement amount</h2>
                <p class="text-xs text-slate-500">The amount on the provider's statement replaces the logged expenses for that month. Saving the same division and month again updates it.</p>
                <form method="POST" action="{{ route('fleet-cards.monthly-spend.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="s-provider" class="{{ $label }}">Provider</label>
                            <select id="s-provider" name="provider" required class="{{ $field }}">
                                @foreach ($providers as $p)
                                    <option value="{{ $p }}" @selected(old('provider', $provider ?? 'Shell') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="s-month" class="{{ $label }}">Month</label>
                                <select id="s-month" name="month" required class="{{ $field }} px-2">
                                    @foreach ($months as $n => $m)
                                        <option value="{{ $n }}" @selected((int) old('month', $year === (int) now()->format('Y') ? now()->format('n') : 1) === $n)>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="s-year" class="{{ $label }}">Year</label>
                                <input id="s-year" name="year" type="number" min="2000" max="2100" required value="{{ old('year', $year) }}" class="{{ $field }} px-2">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="s-office" class="{{ $label }}">Division / program</label>
                        <select id="s-office" name="office_id" required class="{{ $field }}">
                            <option value="">Choose a division</option>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}" @selected((int) old('office_id') === $office->id)>{{ $office->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="s-amount" class="{{ $label }}">Amount (₱)</label>
                            <input id="s-amount" name="amount" type="number" step="0.01" min="0" required value="{{ old('amount') }}" class="{{ $field }}">
                        </div>
                        @if ($canPickLocation)
                            <div>
                                <label for="s-location" class="{{ $label }}">Location</label>
                                <select id="s-location" name="location" class="{{ $field }}">
                                    <option value="">Region-wide</option>
                                    @foreach ($locations as $loc)
                                        <option value="{{ $loc }}" @selected(old('location', $location) === $loc)>{{ $loc }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <label class="flex items-start gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="excluded_from_total" value="1" @checked(old('excluded_from_total')) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>Exclude from the total (e.g. BAR-PRES or PRDP vehicles)</span>
                    </label>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-emerald-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">Save amount</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm" aria-labelledby="statements-heading">
                <h2 id="statements-heading" class="text-sm font-extrabold text-slate-900">Statement amounts in {{ $year }}</h2>
                @if ($statements->isEmpty())
                    <p class="mt-3 rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">None recorded yet. Every month shows the expenses logged on the cards.</p>
                @else
                    <ul class="mt-3 divide-y divide-slate-100">
                        @foreach ($statements as $spend)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm" x-data="{ ask: false }">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-800">{{ $spend->office?->name ?? $spend->division_label }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $spend->provider }} · {{ $months[$spend->month] }} {{ $spend->year }} · ₱{{ $money($spend->amount) }}
                                        · {{ $spend->location ?: 'Region-wide' }}{{ $spend->source === 'import' ? ' · imported' : '' }}{{ $spend->excluded_from_total ? ' · excluded from total' : '' }}
                                    </p>
                                </div>
                                {{-- Two-step remove inside the page (the browser's own confirm pop-up can be blocked) --}}
                                <button type="button" x-show="! ask" @click="ask = true" class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button>
                                <form x-show="ask" x-cloak method="POST" action="{{ route('fleet-cards.monthly-spend.destroy', $spend) }}" class="inline-flex items-center gap-1 whitespace-nowrap">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700">Yes, remove</button>
                                    <button type="button" @click="ask = false" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
