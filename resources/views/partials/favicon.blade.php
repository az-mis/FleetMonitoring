{{-- Browser tab and home-screen icon: the Super Admin's custom app logo (Settings → App Logo) when one is set,
     otherwise the system's 3D vehicle mascot (public/favicon.*). The custom icons get a new file name on every upload. --}}
@if (! empty($appLogoUrl))
    @if (! empty($appIconUrl))
        <link rel="icon" type="image/png" href="{{ $appIconUrl }}" sizes="192x192">
    @else
        <link rel="icon" href="{{ $appLogoUrl }}">
    @endif
    <link rel="apple-touch-icon" href="{{ $appTouchIconUrl ?? $appLogoUrl }}">
@else
    @php $iconVersion = @filemtime(public_path('favicon.png')) ?: 1; @endphp
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $iconVersion }}" sizes="16x16 32x32 48x48">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ $iconVersion }}" sizes="192x192">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ $iconVersion }}">
@endif
<meta name="theme-color" content="#005A36">
