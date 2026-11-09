{{-- "Back to …" button above a page title (vehicle registry, repair record, monthly spend, GSS import); the slot names the list --}}
@props(['href'])

<a href="{{ $href }}" {{ $attributes->class('group inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 py-2 pl-2.5 pr-3.5 text-sm font-semibold text-emerald-800 shadow-xs transition hover:border-emerald-700 hover:bg-emerald-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2') }}>
    <svg class="h-4 w-4 transition group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    Back to {{ $slot }}
</a>
