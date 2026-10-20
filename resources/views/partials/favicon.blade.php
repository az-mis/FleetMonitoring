{{-- Browser tab and home-screen icon: the system's own vehicle mark (public/favicon.svg), not the DA seal --}}
@php $iconVersion = @filemtime(public_path('favicon.svg')) ?: 1; @endphp
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $iconVersion }}" sizes="32x32 48x48">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $iconVersion }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ $iconVersion }}">
<meta name="theme-color" content="#005A36">
