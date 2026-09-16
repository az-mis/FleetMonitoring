@extends('layouts.app')

@section('title', 'Vehicle Requests')
@section('page-title', 'Vehicle Requests')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-gray-500">Review and manage public vehicle requests.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" placeholder="Search requests..." class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All statuses</option>
                @foreach(['pending', 'approved', 'declined'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Search</button>
        </form>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach([['pending', 'Pending Requests', 'amber'], ['approved', 'Approved Requests', 'emerald'], ['declined', 'Declined Requests', 'red']] as [$key, $label, $color])
            <div class="rounded-xl border border-{{ $color }}-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $counts[$key] }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Requester</th><th class="px-4 py-3">Travel</th><th class="px-4 py-3">Vehicle</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($requests as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-4 font-mono text-xs font-semibold text-emerald-700">{{ $item->reference_code }}</td>
                        <td class="px-4 py-4"><p class="font-medium text-gray-900">{{ $item->requester_name }}</p><p class="text-xs text-gray-500">{{ $item->location }}</p></td>
                        <td class="whitespace-nowrap px-4 py-4 text-gray-600">{{ $item->travel_date->format('M j, Y') }}<br><span class="text-xs">{{ $item->destination }}</span></td>
                        <td class="whitespace-nowrap px-4 py-4 text-gray-600">{{ $item->vehicle?->plate_number ?? '—' }}</td>
                        <td class="px-4 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $item->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($item->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800') }}">{{ ucfirst($item->status) }}</span></td>
                        <td class="px-4 py-4 text-right">
                            @if($item->status === 'pending')
                                <form method="POST" action="{{ route('vehicle-requests.status', $item) }}" class="inline-flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <select name="confirmed_driver_id" required class="rounded-md border-gray-300 py-1.5 text-xs"><option value="">Driver</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach</select>
                                    <button class="rounded-md bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('vehicle-requests.status', $item) }}" class="inline-flex">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="declined"><button class="rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Decline</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">Reviewed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No vehicle requests found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3">{{ $requests->links() }}</div>
    </div>
@endsection
