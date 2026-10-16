{{--
    A labelled group of sidebar links. $color tints the label, the guide line beside the links and the links' icons.
    Collapsible groups have a header button (one group open at a time, see toggleSidebarSection); $badge is shown on
    the header while the group is closed, so a pending count is never hidden. Other groups keep a plain heading.
--}}
@props(['id', 'label', 'color', 'collapsible' => false, 'open' => true, 'badge' => null])

@php($isOpen = ! $collapsible || $open)

<div @class(['sidebar-section', 'is-collapsible' => $collapsible, 'is-collapsed' => ! $isOpen]) style="--section: {{ $color }}">
    @if ($collapsible)
        <button
            type="button"
            id="{{ $id }}"
            class="sidebar-section-label sidebar-section-toggle"
            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
            aria-controls="{{ $id }}-items"
            onclick="toggleSidebarSection(this)"
        >
            <span class="sidebar-label sidebar-section-text">{{ $label }}</span>
            @if ($badge)
                <span class="sidebar-label sidebar-section-badge">{{ $badge }}<span class="sr-only"> pending</span></span>
            @endif
            <span class="sidebar-label sidebar-section-rule" aria-hidden="true"></span>
            <svg xmlns="http://www.w3.org/2000/svg" class="sidebar-label sidebar-section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
    @else
        <p id="{{ $id }}" class="sidebar-section-label">
            <span class="sidebar-label sidebar-section-text">{{ $label }}</span>
            <span class="sidebar-label sidebar-section-rule" aria-hidden="true"></span>
        </p>
    @endif

    <div class="sidebar-section-items-wrap">
        <div id="{{ $id }}-items" class="sidebar-section-items space-y-0.5" role="group" aria-labelledby="{{ $id }}">
            {{ $slot }}
        </div>
    </div>
</div>
