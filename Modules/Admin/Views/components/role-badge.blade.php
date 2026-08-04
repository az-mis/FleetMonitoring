@props(['role'])

@php
    $color = $role->badgeColor();
    $classes = match ($color) {
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    {{ $role->label() }}
</span>
