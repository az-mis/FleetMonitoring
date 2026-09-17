@if ($admins->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
        <p class="text-sm font-bold text-gray-900">No admins found</p>
        <p class="mt-1 text-sm text-gray-500">
            @if (request('search'))
                No results match "{{ request('search') }}".
            @else
                Get started by adding your first admin.
            @endif
        </p>
    </div>
@else
    <div id="admin-view-list" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Name</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Email</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Role</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Assigned Location</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($admins as $admin)
                        @php
                            $initial = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($admin->name, 0, 1));
                            $isCurrent = auth()->id() === $admin->id;
                            $location = $admin->role === \Modules\Admin\Enums\UserRole::SUPER_ADMIN ? 'All (Super Admin)' : ($admin->location ?: 'Not assigned');
                            $officer = $admin->location ? ($approvingOfficers->get($admin->location) ?? null) : null;
                        @endphp
                        <tr class="{{ $isCurrent ? 'bg-emerald-50/50' : 'hover:bg-gray-50/70' }}">
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($admin->avatar_url)
                                        <img src="{{ $admin->avatar_url }}" alt="{{ $admin->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-gray-200">
                                    @else
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">
                                            {{ $initial }}
                                        </div>
                                    @endif
                                    <span class="text-sm font-semibold text-gray-900">{{ $admin->name }}@if($isCurrent) <span class="font-normal text-gray-500">(you)</span>@endif</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $admin->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-admin::role-badge :role="$admin->role" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm italic text-slate-500">{{ $location }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <x-admin::row-actions :admin="$admin" :officer="$officer" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div id="admin-view-grid" class="hidden grid grid-cols-1 gap-3 md:grid-cols-3 2xl:grid-cols-5">
        @foreach ($admins as $admin)
            @php
                $initial = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($admin->name, 0, 1));
                $isCurrent = auth()->id() === $admin->id;
                $location = $admin->role === \Modules\Admin\Enums\UserRole::SUPER_ADMIN ? 'All (Super Admin)' : ($admin->location ?: 'Not assigned');
                $officer = $admin->location ? ($approvingOfficers->get($admin->location) ?? null) : null;
            @endphp
            <article class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
                <div class="flex items-start gap-2.5">
                    @if ($admin->avatar_url)
                        <img src="{{ $admin->avatar_url }}" alt="{{ $admin->name }}" class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-gray-200">
                    @else
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-sm font-bold text-white">
                            {{ $initial }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-sm font-extrabold text-gray-900">{{ $admin->name }}@if($isCurrent) <span class="font-normal text-gray-500">(you)</span>@endif</h3>
                            <x-admin::role-badge :role="$admin->role" />
                        </div>
                        <p class="mt-2 flex items-center gap-1.5 truncate text-xs text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/>
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                            </svg>
                            {{ $admin->email }}
                        </p>
                        <p class="mt-2 flex items-center gap-1.5 truncate text-xs text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-pink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            {{ $location }}
                        </p>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button type="button" onclick="openEditAdminModal(this)" data-name="{{ $admin->name }}" data-email="{{ $admin->email }}" data-avatar="{{ $admin->avatar_url }}" data-initial="{{ $initial }}" data-role-value="{{ $admin->role->value }}" data-phone-number="{{ $admin->phone_number }}" data-location="{{ $admin->location }}" data-signee1-name="{{ $officer?->signee1_name }}" data-signee1-title="{{ $officer?->signee1_title }}" data-signee2-name="{{ $officer?->signee2_name }}" data-signee2-title="{{ $officer?->signee2_title }}" data-update-url="{{ route('admin.update', $admin) }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-blue-100 px-2 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
                        </svg>
                        Edit
                    </button>
                    <button type="button" onclick="openDeleteModal(this)" data-name="{{ $admin->name }}" data-delete-url="{{ route('admin.destroy', $admin) }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-100 px-2 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                        </svg>
                        Delete
                    </button>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 font-medium">
            <div>
                Showing <span class="font-bold text-slate-700">{{ $admins->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-700">{{ $admins->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ $admins->total() }}</span> admins
            </div>

            <div class="flex items-center gap-2">
                <span>Rows per page:</span>
                <select id="admin-per-page-select" onchange="changePerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    @foreach ([10, 15, 20, 25, 50, 75, 100] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-1">
            <button type="button" onclick="goToAdminPage(1)" @disabled($admins->onFirstPage()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &laquo;
            </button>
            <button type="button" onclick="goToAdminPage({{ max(1, $admins->currentPage() - 1) }})" @disabled($admins->onFirstPage()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &lt;
            </button>

            @foreach ($admins->getUrlRange(max(1, $admins->currentPage() - 1), min($admins->lastPage(), $admins->currentPage() + 1)) as $page => $url)
                <button type="button" onclick="goToAdminPage({{ $page }})" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $admins->currentPage() ? 'bg-emerald-700 text-white border-emerald-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    {{ $page }}
                </button>
            @endforeach

            <button type="button" onclick="goToAdminPage({{ min($admins->lastPage(), $admins->currentPage() + 1) }})" @disabled(!$admins->hasMorePages()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &gt;
            </button>
            <button type="button" onclick="goToAdminPage({{ $admins->lastPage() }})" @disabled(!$admins->hasMorePages()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &raquo;
            </button>
        </div>
    </div>
@endif
