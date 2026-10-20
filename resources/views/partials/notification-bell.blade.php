{{--
    The bell: saved notifications (App\Services\Notifications\NotificationFeed), unread first. Each card shows the
    record's current state; clicking opens that record and marks the card read. Read/unread is kept per user on
    the server. $bell is ['items' => [...], 'unread' => n], or null when the notifications could not be loaded.
--}}
@php
    $bellItems = $bell['items'] ?? [];
    $badgeTones = [
        'blue' => 'text-blue-600 bg-blue-50 border-blue-100',
        'emerald' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
        'amber' => 'text-amber-700 bg-amber-50 border-amber-200',
        'red' => 'text-red-600 bg-red-50 border-red-200',
        'slate' => 'text-slate-500 bg-slate-100 border-slate-200',
    ];
    $unreadCards = [
        'blue' => 'bg-blue-50/20 border-l-4 border-l-red-500 border-blue-200/80 shadow-2xs text-slate-800',
        'emerald' => 'bg-emerald-50/20 border-l-4 border-l-red-500 border-emerald-200/80 shadow-2xs text-slate-800',
        'amber' => 'bg-amber-50/20 border-l-4 border-l-red-500 border-amber-200/80 shadow-2xs text-slate-800',
    ];
    $iconTones = [
        'blue' => 'bg-blue-50 text-blue-600 border-blue-100',
        'emerald' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'amber' => 'bg-amber-50 text-amber-600 border-amber-100',
    ];
    $bellConfig = [
        'read' => collect($bellItems)->mapWithKeys(fn ($item) => [$item['id'] => $item['read']])->all(),
        'unread' => $bell['unread'] ?? 0,
        'readUrl' => route('notifications.read', ['notification' => '__ID__']),
        'readAllUrl' => route('notifications.read-all'),
        'importUrl' => route('notifications.import-read'),
    ];
@endphp

<div class="relative" x-data="notificationBell(@js($bellConfig))">
    <button
        type="button"
        @click="open = !open"
        class="relative rounded-full p-2 text-topbar-mobile-fg hover:bg-topbar-mobile-hover sm:text-slate-500 sm:hover:bg-slate-100 sm:hover:text-slate-700 transition cursor-pointer"
        title="Notifications"
        aria-label="Notifications"
        :aria-expanded="open.toString()"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        <span x-show="unreadCount > 0" x-cloak class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
        </span>
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false; activeOpt = null"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed left-2 right-2 top-16 z-50 rounded-2xl bg-white p-4 shadow-2xl border border-slate-100
               sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96"
        style="display: none;"
    >
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Notifications</h4>
            <div class="flex items-center gap-2">
                <button type="button" x-show="unreadCount > 0" @click="markAllAsRead()" class="text-[0.625rem] font-semibold text-emerald-600 hover:text-emerald-800 hover:underline">
                    Mark all read
                </button>
                <span x-show="unreadCount > 0" class="rounded-full bg-red-50 px-2 py-0.5 text-[0.625rem] font-bold text-red-600 border border-red-100">
                    <span x-text="unreadCount"></span> New
                </span>
                <span x-show="unreadCount === 0" class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.625rem] font-medium text-slate-500">
                    All read
                </span>
            </div>
        </div>

        <div id="notif-list-container" class="space-y-2 max-h-[60vh] sm:max-h-80 overflow-y-auto pr-0.5">
            @foreach ($bellItems as $item)
                @php $nid = $item['id']; @endphp
                <div class="relative group flex items-center justify-between gap-2 rounded-xl p-2 transition border"
                     data-notif-id="{{ $nid }}"
                     :class="isRead({{ $nid }}) ? 'bg-slate-50/50 border-slate-100 text-slate-400' : @js($unreadCards[$item['accent']] ?? $unreadCards['blue'])">
                    <a href="{{ $item['open'] }}" @click="markRead({{ $nid }})" class="flex items-start gap-2.5 flex-1 min-w-0">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border {{ $iconTones[$item['accent']] ?? $iconTones['blue'] }}">
                            @switch($item['icon'])
                                @case('wrench')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                    @break
                                @case('check')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    @break
                                @case('oil')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.7s-6 6.4-6 11a6 6 0 0 0 12 0c0-4.6-6-11-6-11z"/></svg>
                                    @break
                                @default
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-6-8-12a8 8 0 0 1 16 0c0 6-8 12-8 12z"/><circle cx="12" cy="10" r="3"/></svg>
                            @endswitch
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <p class="text-xs font-bold truncate" :class="isRead({{ $nid }}) ? 'text-slate-500' : 'text-slate-800'">{{ $item['title'] }}</p>
                                <span class="text-[0.5625rem] font-semibold px-1.5 py-0.5 rounded border shrink-0 {{ $badgeTones[$item['badge']['tone']] ?? $badgeTones['slate'] }}">{{ $item['badge']['text'] }}</span>
                            </div>
                            @foreach (array_values(array_filter($item['lines'])) as $i => $line)
                                @if ($i === 0)
                                    <p class="text-[0.6875rem] font-semibold mt-0.5" :class="isRead({{ $nid }}) ? 'text-slate-400' : 'text-slate-700'">{{ $line }}</p>
                                @else
                                    <p class="text-[0.625rem] text-slate-400">{{ $line }}</p>
                                @endif
                            @endforeach
                            <p class="text-[0.625rem] text-slate-400 mt-0.5">{{ $item['ago'] }}</p>
                        </div>
                        <span x-show="!isRead({{ $nid }})" class="h-2 w-2 rounded-full bg-red-500 shrink-0 mt-1"></span>
                    </a>
                    <div class="relative shrink-0 ml-1" @click.outside="if (activeOpt === {{ $nid }}) activeOpt = null">
                        <button type="button" @click.stop.prevent="activeOpt = (activeOpt === {{ $nid }} ? null : {{ $nid }})" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition cursor-pointer" title="Options" aria-label="Notification options">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                        </button>
                        <div x-show="activeOpt === {{ $nid }}" x-cloak x-transition class="absolute right-6 top-1/2 -translate-y-1/2 z-50 rounded-xl bg-white p-1 shadow-xl border border-slate-200 text-left whitespace-nowrap" style="display: none;">
                            <button type="button" @click.stop.prevent="toggleRead({{ $nid }})" class="w-full text-left px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-lg flex items-center gap-2 cursor-pointer">
                                <span x-show="isRead({{ $nid }})" class="flex items-center gap-1.5 text-amber-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Mark Unread</span>
                                <span x-show="!isRead({{ $nid }})" class="flex items-center gap-1.5 text-emerald-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Mark Read</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            @if ($bell === null)
                <div class="py-6 text-center text-slate-400">
                    <p class="text-xs font-semibold text-slate-600">Notifications are unavailable right now</p>
                    <p class="text-[0.6875rem] text-slate-400">Please try again later.</p>
                </div>
            @elseif ($bellItems === [])
                <div class="py-6 text-center text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-7 w-7 text-slate-300 mb-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                    <p class="text-xs font-semibold text-slate-600">No notifications yet</p>
                    <p class="text-[0.6875rem] text-slate-400">You're all caught up!</p>
                </div>
            @endif
        </div>
    </div>
</div>
