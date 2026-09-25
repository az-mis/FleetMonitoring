{{--
    Vehicle type icon.

    Usage:
      @include('vehicle::partials.vehicle-type-icon', [
          'type'         => $vehicle->vehicle_type,   // raw type string, resolved against the vehicle_types table
          'iconOverride' => 'bus',                    // optional: force a specific icon key instead
          'class'        => 'h-5 w-5',                // optional
          'stroke'       => '1.75',                   // optional
      ])

    Icon keys: car, truck, van, motorcycle, bus, bicycle (see VehicleType::ICONS).
--}}
@php
    $iconKey = $iconOverride ?? null;

    if (! $iconKey) {
        $iconKey = \Modules\Vehicle\Models\VehicleType::resolve($type ?? null)?->icon ?? 'car';
    }
@endphp
<svg xmlns="http://www.w3.org/2000/svg" class="{{ $class ?? 'h-5 w-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke ?? '1.75' }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @if ($iconKey === 'truck')
        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>
    @elseif ($iconKey === 'van')
        <path d="M5 17H3a1 1 0 0 1-1-1V8a3 3 0 0 1 3-3h8.5a3 3 0 0 1 2.3 1.1L20.6 11a2 2 0 0 1 .4 1.2V16a1 1 0 0 1-1 1h-1"/><path d="M2 11h12.5V5.5"/><path d="M14.5 11H21"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>
    @elseif ($iconKey === 'bus')
        <path d="M8 6v6"/><path d="M15 6v6"/><path d="M2 12h19.6"/><path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-.4-.1-.8-.2-1.2l-1.4-5C20.1 6.8 19.1 6 18 6H4a2 2 0 0 0-2 2v10h3"/><circle cx="7" cy="18" r="2"/><path d="M9 18h5"/><circle cx="16" cy="18" r="2"/>
    @elseif ($iconKey === 'bicycle')
        <circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>
    @elseif ($iconKey === 'motorcycle')
        <circle cx="5" cy="17" r="3"/><circle cx="19" cy="17" r="3"/><path d="M8 17h3a1 1 0 0 0 1-1 6 6 0 0 1 6-6 1 1 0 0 0 1-1v-.75A5 5 0 0 0 17 5"/><path d="m3 9 6 2a2 2 0 0 1 2-2h2a2 2 0 0 1 2 1.8"/><path d="m18 14-1-3"/>
    @else
        <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>
    @endif
</svg>
