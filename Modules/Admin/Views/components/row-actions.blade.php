@props(['admin', 'officer' => null])

<div class="flex items-center justify-end gap-1">
    <button
        type="button"
        onclick="openViewModal(this)"
        data-initial="{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($admin->name, 0, 1)) }}"
        data-name="{{ $admin->name }}"
        data-email="{{ $admin->email }}"
        data-role="{{ $admin->role->label() }}"
        data-avatar="{{ $admin->avatar_url }}"
        data-created="{{ $admin->created_at->format('M d, Y \a\t g:i A') }}"
        data-updated="{{ $admin->updated_at->format('M d, Y \a\t g:i A') }}"
        title="View"
        aria-label="View {{ $admin->name }}"
        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>

    <button
        type="button"
        onclick="openEditAdminModal(this)"
        data-name="{{ $admin->name }}"
        data-email="{{ $admin->email }}"
        data-avatar="{{ $admin->avatar_url }}"
        data-initial="{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($admin->name, 0, 1)) }}"
        data-role-value="{{ $admin->role->value }}"
        data-phone-number="{{ $admin->phone_number }}"
        data-location="{{ $admin->location }}"
        data-signee1-name="{{ $officer?->signee1_name }}"
        data-signee1-title="{{ $officer?->signee1_title }}"
        data-signee2-name="{{ $officer?->signee2_name }}"
        data-signee2-title="{{ $officer?->signee2_title }}"
        data-update-url="{{ route('admin.update', $admin) }}"
        title="Edit"
        aria-label="Edit {{ $admin->name }}"
        class="rounded-lg p-2 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 transition"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
        </svg>
    </button>

    <button
        type="button"
        onclick="openDeleteModal(this)"
        data-name="{{ $admin->name }}"
        data-delete-url="{{ route('admin.destroy', $admin) }}"
        title="Delete"
        aria-label="Delete {{ $admin->name }}"
        class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600 transition"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"/>
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
            <path d="M10 11v6M14 11v6"/>
        </svg>
    </button>
</div>
