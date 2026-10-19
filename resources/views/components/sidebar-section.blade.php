{{-- A labelled group of sidebar links. The label is a plain heading (no icon or hover, so it never looks clickable);
     $color (operations, vehicles or administration) tints the label, the guide line beside the links and the links' icons, so each group is easy to tell apart. --}}
@props(['id', 'label', 'color'])

<div class="sidebar-section" style="--section: var(--section-{{ $color }})" role="group" aria-labelledby="{{ $id }}">
    <p id="{{ $id }}" class="sidebar-section-label">
        <span class="sidebar-label sidebar-section-text">{{ $label }}</span>
        <span class="sidebar-label sidebar-section-rule" aria-hidden="true"></span>
    </p>
    <div class="sidebar-section-items space-y-0.5">
        {{ $slot }}
    </div>
</div>
