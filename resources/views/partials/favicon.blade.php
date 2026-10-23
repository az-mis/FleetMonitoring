{{-- Browser tab and home-screen icon: the system's 3D vehicle mascot (public/favicon.*), not the DA seal --}}
@php $iconVersion = @filemtime(public_path('favicon.png')) ?: 1; @endphp
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $iconVersion }}" sizes="16x16 32x32 48x48">
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ $iconVersion }}" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ $iconVersion }}">
<meta name="theme-color" content="#005A36">
