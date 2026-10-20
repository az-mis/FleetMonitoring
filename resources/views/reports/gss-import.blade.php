@extends('layouts.app')

@section('title', 'GSS Workbook Import')
@section('page-title', 'GSS Workbook Import')

{{-- Result of checking (dry run) or importing an uploaded GSS workbook --}}
@section('content')
    @php
        $committed = $import['committed'];
        $totals = collect($import['counts'])->reduce(fn ($sum, $c) => [
            'created' => $sum['created'] + $c['created'], 'updated' => $sum['updated'] + $c['updated'],
            'unchanged' => $sum['unchanged'] + $c['unchanged'], 'skipped' => $sum['skipped'] + $c['skipped'], 'review' => $sum['review'] + $c['review'],
        ], ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'review' => 0]);
    @endphp

    <div class="mx-auto max-w-5xl space-y-5">
        <div>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Reports
            </a>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ $committed ? 'Workbook imported' : 'Workbook checked' }}</h1>
            <p class="text-sm text-slate-500">{{ $import['file_name'] }} · {{ $import['year'] }}</p>
        </div>

        <div role="status" @class([
            'rounded-xl border p-4 text-sm',
            'border-emerald-200 bg-emerald-50 text-emerald-900' => $committed,
            'border-amber-200 bg-amber-50 text-amber-900' => ! $committed,
        ])>
            @if ($committed)
                <p class="font-bold">Saved.</p>
                <p class="mt-0.5">{{ number_format($totals['created']) }} records added and {{ number_format($totals['updated']) }} updated. Rows already imported without changes were left as they are.</p>
            @else
                <p class="font-bold">Nothing has been saved yet.</p>
                <p class="mt-0.5">This is what importing the file would do. Check the counts and the review list, then import it.</p>
            @endif
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm" aria-labelledby="counts-heading">
            <h2 id="counts-heading" class="px-5 pt-5 text-sm font-extrabold text-slate-900">Per sheet</h2>
            <div class="relative mt-3 overflow-x-auto">
                <table class="min-w-full text-sm tabular-nums">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50 text-left text-[0.6875rem] font-bold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-2">Sheet</th>
                            <th class="px-3 py-2 text-right">{{ $committed ? 'Added' : 'Would add' }}</th>
                            <th class="px-3 py-2 text-right">{{ $committed ? 'Updated' : 'Would update' }}</th>
                            <th class="px-3 py-2 text-right">Unchanged</th>
                            <th class="px-3 py-2 text-right">Not imported</th>
                            <th class="px-5 py-2 text-right">Needs review</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($import['counts'] as $sheet => $c)
                            <tr>
                                <td class="px-5 py-2 font-semibold text-slate-800">{{ $sheet }}</td>
                                <td class="px-3 py-2 text-right {{ $c['created'] ? 'font-bold text-emerald-700' : 'text-slate-400' }}">{{ number_format($c['created']) }}</td>
                                <td class="px-3 py-2 text-right {{ $c['updated'] ? 'font-bold text-sky-700' : 'text-slate-400' }}">{{ number_format($c['updated']) }}</td>
                                <td class="px-3 py-2 text-right text-slate-500">{{ number_format($c['unchanged']) }}</td>
                                <td class="px-3 py-2 text-right {{ $c['skipped'] ? 'font-bold text-red-700' : 'text-slate-400' }}">{{ number_format($c['skipped']) }}</td>
                                <td class="px-5 py-2 text-right {{ $c['review'] ? 'font-bold text-amber-700' : 'text-slate-400' }}">{{ number_format($c['review']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold text-slate-900">
                            <td class="px-5 py-2">Total</td>
                            <td class="px-3 py-2 text-right">{{ number_format($totals['created']) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($totals['updated']) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($totals['unchanged']) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($totals['skipped']) }}</td>
                            <td class="px-5 py-2 text-right">{{ number_format($totals['review']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                <p>The review list has {{ number_format($import['review_rows']) }} rows: rows that were not imported, and rows imported with a guess, each with the reason.</p>
                <a href="{{ route('reports.gss-import.review', $import['review_file']) }}" class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-white px-4 text-xs font-extrabold text-emerald-700 hover:bg-emerald-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                    Download review list
                </a>
            </div>
        </section>

        @unless ($committed)
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm" aria-labelledby="confirm-heading">
                <h2 id="confirm-heading" class="text-sm font-extrabold text-slate-900">Import this workbook?</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600">
                    <li>Rows imported before and unchanged are skipped; rows that changed update the records they created.</li>
                    <li>Vehicle details typed in the system are kept; the workbook only fills empty ones.</li>
                    <li>A trip's Part B logged in the system and statement amounts recorded on Fleet Card Monthly Spend are never replaced.</li>
                </ul>
                <form method="POST" action="{{ route('reports.gss-import.commit') }}" class="mt-4 flex flex-wrap gap-2" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <input type="hidden" name="token" value="{{ $import['token'] }}">
                    <button type="submit" :disabled="busy" class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-[#005a36] px-5 text-sm font-extrabold text-white shadow-sm hover:bg-[#00432a] disabled:opacity-60">
                        <span x-text="busy ? 'Importing… this takes a few seconds' : 'Import now'">Import now</span>
                    </button>
                    <a href="{{ route('reports.index') }}" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
                </form>
            </section>
        @endunless
    </div>
@endsection
