{{-- One sidebar navigation link. The icon goes in the slot; $badge shows a count, $locked a "disabled by Super Admin" lock. --}}
@props(['href', 'label', 'active' => false, 'badge' => null, 'locked' => false])

<a
    href="{{ $href }}"
    title="{{ $label }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'sidebar-item group relative flex items-center gap-3 rounded-xl px-3 py-2 text-[0.84375rem] leading-5 transition-colors duration-150',
        'sidebar-active font-semibold text-white' => $active,
        'font-medium text-emerald-50/75 hover:bg-white/[0.07] hover:text-white' => ! $active,
    ]) }}
>
    <span @class([
        'relative flex h-5 w-5 shrink-0 items-center justify-center',
        'text-amber-300' => $active,
        'text-emerald-200/70 group-hover:text-emerald-50' => ! $active,
    ])>
        {{ $slot }}
        @if ($badge)
            {{-- Shown only on the collapsed icon rail, where the count itself is hidden --}}
            <span class="sidebar-rail-dot absolute -right-1 -top-1 hidden h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-[#004a2c]" aria-hidden="true"></span>
        @endif
    </span>

    <span class="sidebar-label min-w-0 flex-1 truncate">{{ $label }}</span>

    @if ($badge)
        <span class="sidebar-label inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-rose-500 px-1.5 text-[0.6875rem] font-bold tabular-nums text-white">
            {{ $badge }}<span class="sr-only"> pending</span>
        </span>
    @elseif ($locked)
        <svg xmlns="http://www.w3.org/2000/svg" class="sidebar-label h-3.5 w-3.5 shrink-0 text-amber-300/90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Disabled by Super Admin"><title>Disabled by Super Admin</title><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    @endif
</a>
