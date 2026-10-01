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
                ['label' => __('คู่มือ'), 'children' => [
                    ['route' => 'checkin-guide', 'label' => __('วิธีเช็คชื่อกิจกรรม'), 'active' => ['checkin-guide']],
                    ['route' => 'install-guide', 'label' => __('วิธีติดตั้งแอป'), 'active' => ['install-guide']],
                ]],
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
                    <span class="block text-[0.7rem] text-slate-500 dark:text-slate-400">{{ __('มหาวิทยาลัยราชภัฏสุรินทร์') }}</span>
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
                        class="relative ml-1 flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-brand-purple-50 text-xs font-semibold text-brand-purple-700 ring-1 ring-brand-purple-100 transition hover:ring-brand-purple-300 dark:bg-brand-purple-500/15 dark:text-brand-purple-300 dark:ring-brand-purple-500/20">
                        {{-- Google profile photo when there is one; the initials underneath show if it's missing or fails to load. --}}
                        {{ $initials }}
                        @if ($user?->avatar_url)<img src="{{ $user->avatar_url }}" alt="" referrerpolicy="no-referrer" onerror="this.remove()" class="absolute inset-0 h-full w-full object-cover">@endif
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
             bottom bar has no room for. Icons are looked up by route name so
             the shared $navItems list stays the single source of entries. --}}
        @php
            $sheetIcons = [
                'dashboard' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
                'activities.index' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
                'checkin.show' => 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z',
                'hour-requests.index' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
                'contact.index' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
                'checkin-guide' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
                'install-guide' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
                'admin.attendance.flagged' => 'M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5',
                'admin.external-activities.index' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918',
                'admin.credit-transfers.index' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
                'admin.late-checkins.index' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                'admin.students.index' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                'admin.reports.index' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                'admin.announcements.create' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46',
                'admin.audit-log.index' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
                'admin.users.index' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
                'admin.faculties.index' => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z',
                'admin.settings.index' => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342',
                'admin.credit-transfer-positions.index' => 'M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0',
            ];
            $sheetIcons['admin.dashboard'] = $sheetIcons['dashboard'];
            $sheetIcons['admin.activities.index'] = $sheetIcons['activities.index'];
            $sheetIcons['admin.contact.index'] = $sheetIcons['contact.index'];

            // Plain items go into one "เมนูหลัก" group; dropdown items keep their own group.
            $sheetGroups = [[__('เมนูหลัก'), []]];
            foreach ($navItems as $item) {
                if (isset($item['children'])) {
                    $sheetGroups[] = [$item['label'], $item['children']];
                } else {
                    $sheetGroups[0][1][] = $item;
                }
            }
            if ($isAdmin) {
                $sheetGroups[] = [__('คู่มือ'), [['route' => 'checkin-guide', 'label' => __('วิธีเช็คชื่อกิจกรรม'), 'active' => ['checkin-guide']]]];
            }
            $roleLabel = match ($user?->role) {
                'super_admin' => __('Admin สูงสุด'),
                'admin' => __('Admin'),
                default => $user?->year_level ? __('นักศึกษา · ชั้นปีที่ :year', ['year' => $user->year_level]) : __('นักศึกษา'),
            };
        @endphp
        <template x-teleport="body">
            <div x-show="menuOpen" x-cloak class="fixed inset-0 z-50 lg:hidden">
                <div x-show="menuOpen" x-transition.opacity @click="menuOpen = false" class="absolute inset-0 bg-slate-950/40"></div>
                <div x-show="menuOpen"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                    class="absolute inset-y-0 right-0 flex w-[min(21rem,90vw)] flex-col bg-slate-50 shadow-soft-lg dark:bg-slate-950">

                    {{-- Account card --}}
                    <div class="p-3 pb-0">
                        <div class="relative overflow-hidden rounded-3xl bg-brand-purple-700 p-4 text-white dark:bg-brand-purple-600/90">
                            <div class="pointer-events-none absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
                            <div class="relative flex items-start gap-3">
                                <span class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/20 text-sm font-semibold ring-2 ring-white/30">{{ $initials }}@if ($user?->avatar_url)<img src="{{ $user->avatar_url }}" alt="" referrerpolicy="no-referrer" onerror="this.remove()" class="absolute inset-0 h-full w-full object-cover">@endif</span>
                                <span class="min-w-0 flex-1 pt-0.5">
                                    <span class="block truncate font-display text-base">{{ $displayName }}</span>
                                    <span class="block truncate text-xs text-white/75">{{ $user?->email }}</span>
                                </span>
                                <button type="button" @click="menuOpen = false" class="-mr-1 -mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white/80 hover:bg-white/15 hover:text-white" aria-label="{{ __('ปิดเมนู') }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="relative mt-3 flex items-center justify-between gap-2">
                                <span class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium">{{ $roleLabel }}</span>
                                @unless ($isAdmin)
                                    <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-purple-800 hover:bg-brand-purple-50">
                                        {{ __('ดูโปรไฟล์') }}
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                @endunless
                            </div>
                        </div>
                    </div>

                    {{-- Destinations --}}
                    <nav aria-label="{{ __('เมนูหลัก') }}" class="flex-1 space-y-4 overflow-y-auto p-3 pt-4">
                        @foreach ($sheetGroups as [$groupLabel, $groupItems])
                            @continue(empty($groupItems))
                            <div>
                                <p class="px-2 pb-1.5 text-[0.7rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $groupLabel }}</p>
                                <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-800">
                                    @foreach ($groupItems as $entry)
                                        @php $on = request()->routeIs(...$entry['active']); @endphp
                                        <a href="{{ route($entry['route']) }}" @if ($on) aria-current="page" @endif
                                            @class([
                                                'flex items-center gap-3 px-3 py-2.5 text-sm transition-colors',
                                                'border-t border-slate-100 dark:border-slate-800' => ! $loop->first,
                                                'bg-brand-purple-50/70 font-semibold text-brand-purple-800 dark:bg-brand-purple-500/10 dark:text-brand-purple-200' => $on,
                                                'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60' => ! $on,
                                            ])>
                                            <span @class([
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl',
                                                'bg-brand-purple-700 text-white dark:bg-brand-purple-600' => $on,
                                                'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => ! $on,
                                            ])>
                                                <svg class="h-[1.05rem] w-[1.05rem]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sheetIcons[$entry['route']] ?? 'M8.25 4.5l7.5 7.5-7.5 7.5' }}"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1 truncate">{{ $entry['label'] }}</span>
                                            @if (! empty($entry['badge']))
                                                <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[0.65rem] font-semibold text-white">{{ $entry['badge'] }}</span>
                                            @else
                                                <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </nav>

                    {{-- Settings + sign out --}}
                    <div class="space-y-2.5 border-t border-slate-200 bg-white p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ธีมและภาษา') }}</span>
                            <span class="flex items-center gap-1.5">
                                @include('partials.theme-toggle')
                                @include('partials.locale-switch')
                            </span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="flex h-11 w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 text-sm font-semibold text-rose-600 transition-colors hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                                {{ __('ออกจากระบบ') }}
                            </button>
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
