<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ระบบเช็คชื่อกิจกรรมนักศึกษา SRRU') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @include('partials.pwa-head')
    @include('partials.fonts')
    <script>
        if (localStorage.theme === 'dark' || (! ('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    @php
        $user = auth()->user();
        $isAdmin = $user?->isAdmin();
        $isSuperAdmin = $user?->role === 'super_admin';
        $displayName = $user?->name_thai ?? $user?->name;
        $initials = mb_substr(preg_replace('/^(นาย|นางสาว|นาง)/u', '', (string) $displayName), 0, 2);

        // Opt-in per page (set by the contact chat views) — hides the
        // surrounding nav chrome on phone-width screens so the thread reads
        // as a real full-screen conversation instead of a card embedded in
        // the usual shell. Desktop is untouched either way.
        $fullscreenChat = $fullscreenChat ?? false;

        // Computed once per page load — this queue isn't polled like the
        // notification bell, it just reflects state as of this request.
        $adminContactUnreadCount = $isAdmin ? \App\Models\ContactThread::where('admin_unread', true)->count() : 0;
        $adminReviewCounts = $isAdmin ? \App\Services\AdminReviewInbox::counts() : [];
        $adminAttentionCount = array_sum($adminReviewCounts) + $adminContactUnreadCount;
        $studentAttention = (! $isAdmin && $user) ? \App\Services\StudentAttention::counts($user) : ['requests' => 0, 'activities' => 0, 'contact' => 0];
        $studentAttentionCount = array_sum($studentAttention);

        // One list drives the desktop top bar and the mobile menu sheet.
        // An item with 'children' renders as a dropdown; 'active' is the
        // routeIs() pattern(s) that should highlight it.
        $navItems = $isAdmin
            ? [
                ['route' => 'admin.dashboard', 'label' => __('แดชบอร์ด'), 'active' => ['admin.dashboard']],
                ['route' => 'admin.activities.index', 'label' => __('กิจกรรม'), 'active' => ['admin.activities.*', 'admin.attendance.index', 'admin.attendance.qr-*']],
                ['label' => __('คำร้อง'), 'badge' => $adminAttentionCount, 'children' => [
                    ['route' => 'admin.attendance.flagged', 'label' => __('เช็คชื่อติดธงแดง'), 'active' => ['admin.attendance.flagged'], 'badge' => $adminReviewCounts['flagged'] ?? 0],
                    ['route' => 'admin.external-activities.index', 'label' => __('คำร้องกิจกรรมภายนอก'), 'active' => ['admin.external-activities.*'], 'badge' => $adminReviewCounts['external'] ?? 0],
                    ['route' => 'admin.credit-transfers.index', 'label' => __('เทียบโอนตำแหน่ง'), 'active' => ['admin.credit-transfers.*'], 'badge' => $adminReviewCounts['credit'] ?? 0],
                    ['route' => 'admin.late-checkins.index', 'label' => __('เช็คชื่อย้อนหลัง'), 'active' => ['admin.late-checkins.*'], 'badge' => $adminReviewCounts['late'] ?? 0],
                    ['route' => 'admin.contact.index', 'label' => __('ข้อความจากนักศึกษา'), 'active' => ['admin.contact.*'], 'badge' => $adminContactUnreadCount],
                ]],
                ['route' => 'admin.students.index', 'label' => __('นักศึกษา'), 'active' => ['admin.students.*']],
                ['route' => 'admin.reports.index', 'label' => __('รายงาน'), 'active' => ['admin.reports.*']],
                ['label' => __('ระบบ'), 'children' => array_values(array_filter([
                    ['route' => 'admin.announcements.create', 'label' => __('ส่งประกาศ'), 'active' => ['admin.announcements.*']],
                    ['route' => 'admin.audit-log.index', 'label' => __('ประวัติการตรวจสอบ'), 'active' => ['admin.audit-log.*']],
                    $isSuperAdmin ? ['route' => 'admin.users.index', 'label' => __('ผู้ใช้งานและสิทธิ์'), 'active' => ['admin.users.*']] : null,
                    $isSuperAdmin ? ['route' => 'admin.faculties.index', 'label' => __('คณะ/สาขา'), 'active' => ['admin.faculties.*', 'admin.majors.*']] : null,
                    $isSuperAdmin ? ['route' => 'admin.settings.index', 'label' => __('เกณฑ์การจบการศึกษา'), 'active' => ['admin.settings.*']] : null,
                    $isSuperAdmin ? ['route' => 'admin.credit-transfer-positions.index', 'label' => __('ตำแหน่งเทียบโอนชั่วโมง'), 'active' => ['admin.credit-transfer-positions.*']] : null,
                ]))],
            ]
            : [
                ['route' => 'dashboard', 'label' => __('วันนี้'), 'active' => ['dashboard', 'activity-history.*']],
                ['route' => 'activities.index', 'label' => __('กิจกรรม'), 'active' => ['activities.*', 'self-checkin.*', 'late-checkin.*'], 'badge' => $studentAttention['activities']],
                ['route' => 'checkin.show', 'label' => __('เช็คชื่อ'), 'active' => ['checkin.*']],
                ['route' => 'hour-requests.index', 'label' => __('คำร้อง'), 'active' => ['hour-requests.*', 'external-activities.*', 'credit-transfers.*'], 'badge' => $studentAttention['requests']],
                ['route' => 'contact.index', 'label' => __('ติดต่อเรา'), 'active' => ['contact.*'], 'badge' => $studentAttention['contact']],
                ['route' => 'checkin-guide', 'label' => __('คู่มือ'), 'active' => ['checkin-guide']],
            ];

        $isActive = fn (array $item) => isset($item['children'])
            ? collect($item['children'])->contains(fn ($child) => request()->routeIs(...$child['active']))
            : request()->routeIs(...$item['active']);
        $homeRoute = $isAdmin ? 'admin.dashboard' : 'dashboard';
    @endphp

    <header
        x-data="{ menuOpen: false }"
        @keydown.escape.window="menuOpen = false"
        class="{{ $fullscreenChat ? 'hidden md:block' : '' }} sticky top-0 z-40 border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950"
    >
        <div class="mx-auto flex h-16 max-w-[90rem] items-center gap-6 px-4 sm:px-6">
            <a href="{{ route($homeRoute) }}" class="flex shrink-0 items-center gap-2.5">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 object-contain">
                <span class="leading-tight">
                    <span class="block font-display text-[1.05rem] font-semibold text-slate-900 dark:text-white">SRRU Check</span>
                    <span class="hidden text-[0.7rem] text-slate-500 dark:text-slate-400 sm:block">{{ __('มหาวิทยาลัยราชภัฏสุรินทร์') }}</span>
                </span>
            </a>

            {{-- Desktop navigation --}}
            <nav aria-label="{{ __('เมนูหลัก') }}" class="hidden h-full items-stretch gap-1 lg:flex">
                @foreach ($navItems as $item)
                    @php $active = $isActive($item); @endphp
                    @isset($item['children'])
                        <div class="relative flex" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                            <button type="button" @click="open = ! open" :aria-expanded="open"
                                @class([
                                    'flex items-center gap-1 border-b-2 px-3 text-sm transition-colors',
                                    'border-brand-purple-700 font-semibold text-brand-purple-700 dark:border-brand-purple-400 dark:text-brand-purple-300' => $active,
                                    'border-transparent text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => ! $active,
                                ])>
                                {{ $item['label'] }}
                                @if (! empty($item['badge']))
                                    <span class="-mt-2.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-950" aria-label="{{ __('มีรายการใหม่') }}"></span>
                                @endif
                                <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                                class="absolute left-0 top-full z-50 mt-1 w-60 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-soft-lg dark:border-slate-800 dark:bg-slate-900">
                                @foreach ($item['children'] as $child)
                                    @php $childActive = request()->routeIs(...$child['active']); @endphp
                                    <a href="{{ route($child['route']) }}"
                                        @class([
                                            'flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition-colors',
                                            'bg-brand-purple-50 font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300' => $childActive,
                                            'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' => ! $childActive,
                                        ])>
                                        {{ $child['label'] }}
                                        @if (! empty($child['badge']))
                                            <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[0.65rem] font-semibold text-white">{{ $child['badge'] }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                            @class([
                                'flex items-center gap-1 border-b-2 px-3 text-sm transition-colors',
                                'border-brand-purple-700 font-semibold text-brand-purple-700 dark:border-brand-purple-400 dark:text-brand-purple-300' => $active,
                                'border-transparent text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => ! $active,
                            ])>
                            {{ $item['label'] }}
                            @if (! empty($item['badge']))
                                <span class="-mt-2.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-950" aria-label="{{ __('มีรายการใหม่') }}"></span>
                            @endif
                        </a>
                    @endisset
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-1.5">
                @include('partials.notification-bell')
                <div class="hidden items-center gap-1.5 lg:flex">
                    @include('partials.theme-toggle')
                    @include('partials.locale-switch')
                </div>

                {{-- Account menu (desktop) --}}
                <div class="relative hidden lg:block" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" @click="open = ! open" :aria-expanded="open" aria-label="{{ __('บัญชีของฉัน') }}"
                        class="ml-1 flex h-9 w-9 items-center justify-center rounded-full bg-brand-purple-50 text-xs font-semibold text-brand-purple-700 ring-1 ring-brand-purple-100 transition hover:ring-brand-purple-300 dark:bg-brand-purple-500/15 dark:text-brand-purple-300 dark:ring-brand-purple-500/20">
                        {{ $initials }}
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                        class="absolute right-0 top-full z-50 mt-2 w-64 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-soft-lg dark:border-slate-800 dark:bg-slate-900">
                        <div class="px-3 py-2.5">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $displayName }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user?->email }}</p>
                        </div>
                        @unless ($isAdmin)
                            <a href="{{ route('profile.show') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('โปรไฟล์') }}</a>
                        @endunless
                        @if ($isAdmin)
                        <a href="{{ route('checkin-guide') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('วิธีเช็คชื่อ') }}</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full rounded-xl px-3 py-2.5 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">{{ __('ออกจากระบบ') }}</button>
                        </form>
                    </div>
                </div>

                {{-- Mobile menu trigger --}}
                <button type="button" @click="menuOpen = true" class="relative flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden" aria-label="{{ __('เมนู') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    @if (($isAdmin ? $adminAttentionCount : $studentAttentionCount) > 0)
                        <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-950" aria-label="{{ __('มีรายการใหม่') }}"></span>
                    @endif
                </button>
            </div>
        </div>

        {{-- Mobile menu sheet: every destination, including the ones the
             bottom bar has no room for. --}}
        <template x-teleport="body">
            <div x-show="menuOpen" x-cloak class="fixed inset-0 z-50 lg:hidden">
                <div x-show="menuOpen" x-transition.opacity @click="menuOpen = false" class="absolute inset-0 bg-slate-950/40"></div>
                <div x-show="menuOpen"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                    class="absolute inset-y-0 right-0 flex w-[min(20rem,88vw)] flex-col bg-white shadow-soft-lg dark:bg-slate-900">
                    <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-4 dark:border-slate-800">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-sm font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">{{ $initials }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $displayName }}</span>
                            <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $user?->email }}</span>
                        </span>
                        <button type="button" @click="menuOpen = false" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="{{ __('ปิดเมนู') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <nav aria-label="{{ __('เมนูหลัก') }}" class="flex-1 space-y-1 overflow-y-auto p-3">
                        @foreach ($navItems as $item)
                            @isset($item['children'])
                                <p class="px-3 pb-1 pt-3 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $item['label'] }}</p>
                                @foreach ($item['children'] as $child)
                                    @php $childActive = request()->routeIs(...$child['active']); @endphp
                                    <a href="{{ route($child['route']) }}"
                                        @class([
                                            'flex items-center justify-between rounded-xl px-3 py-2.5 text-sm',
                                            'bg-brand-purple-50 font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300' => $childActive,
                                            'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' => ! $childActive,
                                        ])>
                                        {{ $child['label'] }}
                                        @if (! empty($child['badge']))
                                            <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[0.65rem] font-semibold text-white">{{ $child['badge'] }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            @else
                                @php $active = $isActive($item); @endphp
                                <a href="{{ route($item['route']) }}"
                                    @class([
                                        'flex items-center justify-between rounded-xl px-3 py-2.5 text-sm',
                                        'bg-brand-purple-50 font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300' => $active,
                                        'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' => ! $active,
                                    ])>
                                    {{ $item['label'] }}
                                    @if (! empty($item['badge']))
                                        <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[0.65rem] font-semibold text-white">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endisset
                        @endforeach
                        @unless ($isAdmin)
                            <a href="{{ route('profile.show') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('โปรไฟล์') }}</a>
                        @endunless
                        @if ($isAdmin)
                        <a href="{{ route('checkin-guide') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('วิธีเช็คชื่อ') }}</a>
                        @endif

                    </nav>
                    <div class="flex items-center justify-between gap-2 border-t border-slate-200 p-4 dark:border-slate-800">
                        <div class="flex items-center gap-1.5">
                            @include('partials.theme-toggle')
                            @include('partials.locale-switch')
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-xl px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">{{ __('ออกจากระบบ') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </header>

    <main class="mx-auto max-w-[90rem] {{ $fullscreenChat ? 'p-0 md:px-6 md:py-8' : 'px-4 py-6 pb-28 sm:px-6 sm:py-8 lg:pb-10' }}">
        @if (session('status'))
            <div role="status" class="mb-5 flex items-start gap-2.5 rounded-2xl border border-brand-green-100 bg-brand-green-50 px-4 py-3 text-sm text-brand-green-800 dark:border-brand-green-500/20 dark:bg-brand-green-500/10 dark:text-brand-green-300">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mb-5 flex items-start gap-2.5 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @unless ($fullscreenChat)
        @if ($isAdmin)
            @include('partials.admin-mobile-tab-bar')
        @else
            @include('partials.mobile-tab-bar')
            @if (config('services.srru.pwa_install_prompt_enabled'))
                @include('partials.pwa-install-banner')
            @endif
        @endif
    @endunless

    @include('partials.push-notification-banner')

    @stack('scripts')
</body>
</html>
