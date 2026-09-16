@extends('layouts.app')

@section('title', 'Approving Officers')
@section('page-title', 'Approving Officers')

@section('content')
    <div class="mb-6"><h2 class="text-lg font-semibold text-gray-900">Approving Officers</h2><p class="mt-1 text-sm text-gray-500">Manage the official signatories printed on trip tickets for each office location.</p></div>
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse($officers as $officer)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between"><h3 class="font-semibold text-gray-900">{{ $officer->location }}</h3>@if(auth()->user()->role?->value === 'super_admin')<form method="POST" action="{{ route('approving-officers.destroy', $officer) }}" onsubmit="return confirm('Remove this record?')">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600 hover:underline">Remove</button></form>@endif</div>
                <dl class="space-y-2 text-sm"><div><dt class="text-xs text-gray-500">First signatory</dt><dd class="font-medium text-gray-900">{{ $officer->signee1_name ?: '—' }}</dd><dd class="text-xs text-gray-500">{{ $officer->signee1_title }}</dd></div><div><dt class="text-xs text-gray-500">Second signatory</dt><dd class="font-medium text-gray-900">{{ $officer->signee2_name ?: '—' }}</dd><dd class="text-xs text-gray-500">{{ $officer->signee2_title }}</dd></div></dl>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No approving officers configured.</div>
        @endforelse
    </div>
    <form method="POST" action="{{ route('approving-officers.store') }}" class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        <h3 class="font-semibold text-gray-900">Add or update location</h3>
        <div class="mt-4 grid gap-3 sm:grid-cols-2"><input name="location" required placeholder="Office / Province" class="rounded-lg border-gray-300 text-sm"><input name="signee1_name" placeholder="First signatory name" class="rounded-lg border-gray-300 text-sm"><input name="signee1_title" placeholder="First signatory title" class="rounded-lg border-gray-300 text-sm"><input name="signee2_name" placeholder="Second signatory name" class="rounded-lg border-gray-300 text-sm"><input name="signee2_title" placeholder="Second signatory title" class="rounded-lg border-gray-300 text-sm"></div>
        <button class="mt-4 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save Signatories</button>
    </form>
@endsection
