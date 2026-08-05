@if ($drivers->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
        <p class="text-sm font-medium text-gray-900">No drivers found</p>
        <p class="mt-1 text-sm text-gray-500">
            @if (request('search'))
                No results match "{{ request('search') }}".
            @else
                Get started by registering your first driver.
            @endif
        </p>
    </div>
@else
    {{-- LIST VIEW --}}
    <div id="driver-view-list" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Address</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">License Expiry</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Joined</th>
                        <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($drivers as $driver)
                        <tr class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($driver->avatar_url)
                                        <img src="{{ $driver->avatar_url }}" alt="{{ $driver->name }}" class="h-8 w-8 shrink-0 rounded-full object-cover">
                                    @else
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-xs font-semibold text-emerald-700">
                                            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($driver->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $driver->name }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-600">{{ $driver->email }}</td>
                            <td class="max-w-[220px] truncate px-5 py-3.5 text-sm text-gray-600">{{ $driver->address ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-500">{{ optional($driver->license_expiration_date)->format('M d, Y') ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-500">{{ $driver->created_at->format('M d, Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <x-driver::row-actions :driver="$driver" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- GRID VIEW --}}
    <div id="driver-view-grid" class="hidden grid grid-cols-1 gap-4 xs:grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($drivers as $driver)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($driver->avatar_url)
                            <img src="{{ $driver->avatar_url }}" alt="{{ $driver->name }}" class="h-10 w-10 shrink-0 rounded-full object-cover">
                        @else
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-700">
                                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($driver->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $driver->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $driver->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3 space-y-1 text-xs text-gray-500">
                    <p class="truncate"><span class="font-medium text-gray-600">Address:</span> {{ $driver->address ?: '—' }}</p>
                    <p><span class="font-medium text-gray-600">License expiry:</span> {{ optional($driver->license_expiration_date)->format('M d, Y') ?: '—' }}</p>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                    <x-admin::role-badge :role="$driver->role" />
                    <span class="text-xs text-gray-400">{{ $driver->created_at->format('M d, Y') }}</span>
                </div>

                <div class="mt-4 flex items-center justify-end gap-1 border-t border-gray-100 pt-3">
                    <x-driver::row-actions :driver="$driver" />
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-x-auto">
        {{ $drivers->links() }}
    </div>
@endif
