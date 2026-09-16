<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request a Vehicle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#082b16] text-white antialiased">
<main class="min-h-screen bg-cover bg-center px-4 py-6 sm:px-8" style="background-image:linear-gradient(rgba(5,38,18,.68),rgba(5,38,18,.78)),url('{{ asset('FleetMS-bg.jpg') }}');">
    <div class="mx-auto max-w-2xl rounded-3xl border border-emerald-300/30 bg-[#0a351b]/90 p-6 shadow-2xl backdrop-blur-sm sm:p-8">
        <nav class="mb-6 flex items-center justify-between border-b border-emerald-200/15 pb-4 text-xs text-emerald-100/75">
            <a href="{{ route('check-status') }}" class="hover:text-white">⌕ Check Request Status</a>
            <span class="rounded-full border border-emerald-400/40 bg-emerald-950/60 px-3 py-1 font-semibold uppercase tracking-wide text-emerald-300">▣ Public Request Form</span>
            <a href="{{ route('login') }}" class="hover:text-white">Staff Login →</a>
        </nav>
        <div class="mb-7 flex items-center gap-3">
            <img src="{{ asset('da-logo.png') }}" alt="Agency logo" class="h-11 w-11 rounded-full bg-white object-contain p-1">
            <div><h1 class="text-2xl font-bold">Official Vehicle Request</h1><p class="mt-1 text-sm text-emerald-100/70">Complete the form below to request government vehicle transport for official travel. A unique tracking code will be generated upon submission.</p></div>
        </div>
        <form class="space-y-4" method="POST" action="{{ route('request-vehicle.store') }}">
            @csrf
            <div><label class="mb-1 block text-xs font-semibold">Your Full Name <span class="text-emerald-300">*</span></label><input name="requesterName" required placeholder="e.g. Juan Dela Cruz" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div>
            <label class="flex items-center gap-2 text-xs text-emerald-100/80"><input type="checkbox" name="requesterIsPassenger" class="rounded border-emerald-300/30 bg-[#062512] text-emerald-500"> I am riding along on this trip as a passenger</label>
            <div><label class="mb-1 block text-xs font-semibold">Location / Office Province <span class="text-emerald-300">*</span></label><select name="location" required class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"><option value="">— Select Location / Building —</option><option>Oriental Mindoro</option><option>Occidental Mindoro</option><option>Marinduque</option><option>Palawan</option><option>Romblon</option><option>Quezon City Satellite Office</option></select></div>
            <div><label class="mb-1 block text-xs font-semibold">Office / Section <span class="font-normal text-emerald-100/55">(Optional)</span></label><input name="requesterOffice" placeholder="— Select your office / division —" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div>
            <div><label class="mb-1 block text-xs font-semibold">Contact Number <span class="font-normal text-emerald-100/55">(Optional)</span></label><input name="requesterContact" placeholder="09XX-XXX-XXXX" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div>
            <div><label class="mb-1 block text-xs font-semibold">Select Vehicle <span class="text-emerald-300">*</span></label><select id="vehicle-select" name="vehicleId" required disabled class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"><option value="">— Select a location first —</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" data-location="{{ $vehicle->location }}">{{ $vehicle->plate_number }} · {{ $vehicle->brand }} {{ $vehicle->model }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-xs font-semibold">Destination <span class="text-emerald-300">*</span></label><input name="destination" required placeholder="e.g. Provincial Capitol, Calapan City" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div>
            <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-xs font-semibold">Travel Date (From) <span class="text-emerald-300">*</span></label><input type="date" name="travelDate" required class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"></div><div><label class="mb-1 block text-xs font-semibold">Travel Date (To, optional)</label><input type="date" name="travelDateEnd" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"></div></div>
            <div><label class="mb-1 block text-xs font-semibold">Official Purpose <span class="text-emerald-300">*</span></label><textarea name="purpose" required rows="3" placeholder="Describe the official reason or event for this travel request..." class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></textarea></div>
            <div><label class="mb-1 block text-xs font-semibold">Additional Passengers <span class="font-normal text-emerald-100/55">(Optional)</span></label><div id="passenger-list"><input name="passengers[]" placeholder="Passenger 1 full name" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none"></div><button type="button" id="add-passenger" class="mt-2 text-xs font-semibold text-emerald-200 hover:text-white">＋ Add another passenger</button></div>
            <div><label class="mb-1 block text-xs font-semibold">Previous Trip Ticket Date <span class="font-normal text-emerald-100/55">(Optional)</span></label><input type="date" name="previousTripTicketDate" class="w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm text-emerald-100 focus:border-emerald-400 focus:outline-none"></div>
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-green-500 px-4 py-3 text-sm font-bold shadow-lg hover:from-emerald-400 hover:to-green-400">Submit Vehicle Request&nbsp; →</button>
        </form>
        <footer class="mt-5 border-t border-emerald-200/15 pt-4 text-center text-xs text-emerald-100/55">♧ Official Government Fleet Management System<br><span class="text-[10px]">© {{ date('Y') }} {{ config('app.name', 'Fleet Monitoring') }}</span></footer>
    </div>
    </main>
    <script>
        const locationSelect = document.querySelector('select[name="location"]');
        const vehicleSelect = document.getElementById('vehicle-select');
        const vehicleOptions = Array.from(vehicleSelect.querySelectorAll('option[data-location]'));
        locationSelect.addEventListener('change', () => {
            const location = locationSelect.value;
            vehicleSelect.disabled = !location;
            vehicleSelect.value = '';
            vehicleOptions.forEach((option) => {
                option.hidden = Boolean(location) && option.dataset.location && option.dataset.location !== location;
            });
        });
        document.getElementById('add-passenger').addEventListener('click', () => {
            const list = document.getElementById('passenger-list');
            if (list.children.length >= 20) return;
            const input = document.createElement('input');
            input.name = 'passengers[]';
            input.placeholder = `Passenger ${list.children.length + 1} full name`;
            input.className = 'mt-2 w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-sm placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none';
            list.appendChild(input);
        });
    </script>
</body>
</html>
