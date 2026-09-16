<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Fleet Management System</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#082b16] text-white antialiased">
<main class="flex min-h-screen items-center justify-center bg-cover bg-center p-6" style="background-image:linear-gradient(rgba(5,38,18,.72),rgba(5,38,18,.82)),url('{{ asset('FleetMS-bg.jpg') }}')">
    <div class="w-full max-w-4xl rounded-3xl border border-emerald-300/30 bg-[#0a351b]/90 p-8 text-center shadow-2xl backdrop-blur-sm sm:p-12">
        <img src="{{ asset('da-logo.png') }}" alt="Agency logo" class="mx-auto h-16 w-16 rounded-full bg-white object-contain p-1">
        <span class="mt-5 inline-flex rounded-full border border-emerald-400/40 bg-emerald-950/60 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-300">Fleet Management System</span>
        <h1 class="mt-5 text-3xl font-extrabold tracking-tight sm:text-5xl">Smart Fleet Operations<br><span class="text-emerald-300">&amp; Monitoring</span></h1>
        <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-emerald-100/75">A unified, secure platform for tracking vehicles, assigning drivers, and managing official trip requests in real time.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3"><a href="{{ route('login') }}" class="rounded-xl bg-gradient-to-r from-emerald-500 to-green-500 px-5 py-3 text-sm font-bold hover:from-emerald-400 hover:to-green-400">Staff Login →</a><a href="{{ route('request-vehicle') }}" class="rounded-xl border border-emerald-300/30 bg-emerald-950/50 px-5 py-3 text-sm font-semibold text-emerald-100 hover:bg-emerald-900/60">Request a Vehicle</a><a href="{{ route('check-status') }}" class="rounded-xl border border-emerald-300/30 bg-emerald-950/50 px-5 py-3 text-sm font-semibold text-emerald-100 hover:bg-emerald-900/60">Check Request Status</a></div>
        <div class="mt-10 grid gap-3 text-left sm:grid-cols-3"><div class="rounded-xl border border-emerald-300/10 bg-[#062512]/70 p-4"><p class="font-semibold">Role-Based Security</p><p class="mt-1 text-xs text-emerald-100/60">Secure workflows for admins and drivers.</p></div><div class="rounded-xl border border-emerald-300/10 bg-[#062512]/70 p-4"><p class="font-semibold">Real-Time Allocation</p><p class="mt-1 text-xs text-emerald-100/60">Manage vehicles, drivers, and availability.</p></div><div class="rounded-xl border border-emerald-300/10 bg-[#062512]/70 p-4"><p class="font-semibold">Trip Ticket Generation</p><p class="mt-1 text-xs text-emerald-100/60">Printable official travel authorizations.</p></div></div>
        <p class="mt-8 text-[10px] text-emerald-100/50">Authorized Agency Personnel Only · All activities logged</p>
    </div>
</main>
</body>
</html>
