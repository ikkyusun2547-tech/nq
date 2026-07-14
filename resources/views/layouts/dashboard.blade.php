<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ระบบเช็คชื่อกิจกรรมนักศึกษา SRRU') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @include('partials.pwa-head')
    <script>
        if (localStorage.theme === 'dark' || (! ('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    @php
        $isAdmin = auth()->user()?->isAdmin();
        $isSuperAdmin = auth()->user()?->role === 'super_admin';

        // Flat list for the student top nav (unchanged from before).
        $navItems = $isAdmin
            ? [
                ['route' => 'admin.dashboard', 'label' => __('แดชบอร์ด')],
                ['route' => 'admin.activities.index', 'label' => __('กิจกรรม')],
                ['route' => 'admin.attendance.flagged', 'label' => __('เช็คชื่อติดธงแดง')],
                ['route' => 'admin.external-activities.index', 'label' => __('คำร้องภายนอก')],
                ['route' => 'admin.credit-transfers.index', 'label' => __('เทียบโอนตำแหน่ง')],
                ['route' => 'admin.late-checkins.index', 'label' => __('เช็คชื่อย้อนหลัง')],
                ['route' => 'admin.students.index', 'label' => __('นักศึกษา')],
            ]
            : [
                ['route' => 'dashboard', 'label' => __('แดชบอร์ด')],
                ['route' => 'activities.index', 'label' => __('เช็คกิจกรรม')],
                ['route' => 'checkin.show', 'label' => __('เช็คด้วย QR Code')],
                ['route' => 'hour-requests.index', 'label' => __('ขอชั่วโมง')],
                ['route' => 'profile.show', 'label' => __('โปรไฟล์')],
            ];

        // Grouped, icon-labelled list for the admin sidebar — a sidebar has
        // room to just list everything, so (unlike the old top nav) nothing
        // needs to be tucked behind a "เพิ่มเติม" dropdown.
        $sidebarGroups = $isAdmin ? [
            [
                'label' => __('ภาพรวม'),
                'items' => [
                    ['route' => 'admin.dashboard', 'label' => __('แดชบอร์ด'), 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
                ],
            ],
            [
                'label' => __('การดำเนินงาน'),
                'items' => [
                    ['route' => 'admin.activities.index', 'label' => __('กิจกรรม'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['route' => 'admin.attendance.flagged', 'label' => __('เช็คชื่อติดธงแดง'), 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                    ['route' => 'admin.external-activities.index', 'label' => __('คำร้องภายนอก'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['route' => 'admin.credit-transfers.index', 'label' => __('เทียบโอนตำแหน่ง'), 'icon' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15'],
                    ['route' => 'admin.late-checkins.index', 'label' => __('เช็คชื่อย้อนหลัง'), 'icon' => 'M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['route' => 'admin.students.index', 'label' => __('นักศึกษา'), 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
                ],
            ],
            [
                'label' => __('ระบบ'),
                'items' => array_filter([
                    ['route' => 'admin.reports.index', 'label' => __('รายงาน'), 'icon' => 'M3 17l6-6 4 4 8-8M21 7v6h-6'],
                    ['route' => 'admin.audit-log.index', 'label' => __('ประวัติการตรวจสอบ'), 'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z'],
                    ['route' => 'admin.announcements.create', 'label' => __('ส่งประกาศ'), 'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c2.31.192 4.594.591 6.81 1.17a48.11 48.11 0 003.65-8.35 48.11 48.11 0 00-3.65-8.35 48.51 48.51 0 00-6.81 1.17m0 6.42a48.517 48.517 0 010-6.42'],
                    $isSuperAdmin ? ['route' => 'admin.faculties.index', 'label' => __('คณะ/สาขา'), 'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'] : null,
                    $isSuperAdmin ? ['route' => 'admin.users.index', 'label' => __('ผู้ใช้งานและสิทธิ์'), 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'] : null,
                    $isSuperAdmin ? ['route' => 'admin.settings.edit', 'label' => __('เกณฑ์การจบการศึกษา'), 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'] : null,
                ]),
            ],
        ] : [];
    @endphp

    @if ($isAdmin)
        <div x-data="{ sidebarOpen: false }">
            <!-- Mobile-only slim top bar: sidebar is an off-canvas drawer below lg -->
            <div class="sticky top-0 z-40 flex h-14 items-center justify-between bg-brand-purple-950 px-4 shadow-soft-lg lg:hidden">
                <button @click="sidebarOpen = true" class="rounded-lg p-2 text-violet-200/70 hover:bg-white/5 hover:text-white" aria-label="{{ __('เมนู') }}">
                    <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="h-7 w-7 object-contain">
                    <span class="text-sm font-semibold text-white">SRRU Check</span>
                </a>
                @include('partials.notification-bell')
            </div>

            <!-- Mobile drawer scrim -->
            <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
                class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden"></div>

            <!-- Always fixed (viewport-relative, never in document flow) at every
                 breakpoint, so it never scrolls with the page — position:sticky
                 was tried first but a sticky element only stays pinned for as
                 long as its own box is tall enough to still overlap the
                 viewport; once the page scrolled past this aside's own
                 (shorter-than-100vh) content height it started scrolling away
                 like a normal block. fixed has no such caveat. Always in the
                 DOM (not x-show'd) so lg:!translate-x-0 can force it on-screen
                 on desktop purely with CSS — an x-show tied to a JS
                 window-width check would need a resize listener to stay correct
                 and still flashes wrong on first paint. Brand purple-950 chrome
                 (unaffected by the light/dark toggle) matches the rest of the
                 app's nav — only the main content area follows the theme toggle. -->
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-50 flex h-screen w-64 shrink-0 flex-col overflow-hidden bg-brand-purple-950 shadow-soft-lg transition-transform duration-200 ease-out lg:!translate-x-0"
            >

                <div class="relative flex shrink-0 items-center justify-between gap-2 border-b border-white/10 px-4 py-5">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                        <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="h-12 w-12 shrink-0 object-contain drop-shadow">
                        <span class="min-w-0">
                            <span class="block text-xl font-bold leading-tight text-white">SRRU Check</span>
                            <span class="mt-1 block text-xs leading-snug text-violet-300/60">{{ __('ระบบเช็คกิจกรรมนักศึกษา มหาวิทยาลัยราชภัฏสุรินทร์') }}</span>
                        </span>
                    </a>
                    <button @click="sidebarOpen = false" class="shrink-0 rounded-lg p-1.5 text-violet-200/70 hover:bg-white/5 hover:text-white lg:hidden" aria-label="{{ __('ปิดเมนู') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <nav class="relative flex-1 space-y-5 overflow-y-auto px-3 py-4">
                    @foreach ($sidebarGroups as $group)
                        <div>
                            <p class="mb-1.5 px-2.5 text-[0.68rem] font-semibold uppercase tracking-wide text-violet-300/50">{{ $group['label'] }}</p>
                            <div class="space-y-0.5">
                                @foreach ($group['items'] as $item)
                                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                                    <a href="{{ route($item['route']) }}"
                                        @class([
                                            'flex items-center gap-2.5 rounded-lg border-l-2 px-2.5 py-2 text-sm font-medium transition-all duration-200',
                                            'border-brand-green-400 bg-brand-green-500/15 text-brand-green-400' => $active,
                                            'border-transparent text-violet-200/70 hover:translate-x-0.5 hover:bg-white/5 hover:text-white' => ! $active,
                                        ])>
                                        <svg class="h-[1.1rem] w-[1.1rem] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/></svg>
                                        <span class="truncate">{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <div class="relative shrink-0 border-t border-white/10 p-3">
                    <div class="flex items-center gap-2 px-1 pb-2">
                        @include('partials.notification-bell')
                        @include('partials.theme-toggle')
                        @include('partials.locale-switch')
                    </div>
                    <div class="flex items-center gap-2.5 rounded-lg px-2 py-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-purple-400 to-brand-green-500 text-xs font-semibold text-white">
                            {{ mb_substr(auth()->user()->name_thai ?? auth()->user()->name, 0, 1) }}
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm text-violet-200/70">{{ auth()->user()->name_thai ?? auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-lg p-1.5 text-violet-200/70 transition-colors hover:bg-white/5 hover:text-white" title="{{ __('ออกจากระบบ') }}">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- lg:pl-64 compensates for the sidebar now being fixed (out of
                 flow) instead of participating in a flex layout — without it,
                 content would render underneath the sidebar at desktop widths. -->
            <div class="min-w-0 lg:pl-64">
                <main class="mx-auto max-w-[90rem] px-4 py-6 sm:px-6 sm:py-8">
                    @if (session('status'))
                        <div class="mb-4 rounded-xl bg-brand-green-50 px-4 py-3 text-sm text-brand-green-700 ring-1 ring-brand-green-100 dark:bg-brand-green-500/10 dark:text-brand-green-400 dark:ring-brand-green-500/20">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <nav class="sticky top-0 z-40 bg-brand-purple-950 shadow-soft-lg" x-data="{ mobileOpen: false }">
            <div class="mx-auto max-w-[90rem] px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between gap-4">
                    <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2.5">
                        <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="h-11 w-11 shrink-0 object-contain drop-shadow">
                        <span class="whitespace-nowrap text-sm font-semibold leading-tight text-white">SRRU Check</span>
                    </a>

                    <div class="hidden flex-1 items-center justify-center gap-1 md:flex">
                        @foreach ($navItems as $item)
                            <a href="{{ route($item['route']) }}"
                                @class([
                                    'whitespace-nowrap rounded-lg px-2.5 py-2 text-sm font-medium transition-all duration-200 lg:px-3.5',
                                    'bg-brand-green-500/15 text-brand-green-400' => request()->routeIs($item['route'].'*'),
                                    'text-violet-200/70 hover:bg-white/5 hover:text-white' => ! request()->routeIs($item['route'].'*'),
                                ])>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div class="hidden shrink-0 items-center gap-3 md:flex">
                        @include('partials.notification-bell')
                        @include('partials.theme-toggle')
                        @include('partials.locale-switch')
                        <span class="hidden whitespace-nowrap text-sm text-violet-200/70 lg:block">{{ auth()->user()->name_thai ?? auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium text-violet-200/70 transition-colors hover:bg-white/5 hover:text-white">
                                {{ __('ออกจากระบบ') }}
                            </button>
                        </form>
                    </div>

                    <div class="flex items-center gap-1 md:hidden">
                        @include('partials.notification-bell')
                    </div>

                    <button @click="mobileOpen = ! mobileOpen" class="rounded-lg p-2 text-violet-200/70 hover:bg-white/5 hover:text-white md:hidden" aria-label="{{ __('เมนู') }}">
                        <svg x-show="! mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div x-show="mobileOpen" x-cloak class="border-t border-white/10 md:hidden">
                <div class="space-y-1 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-violet-200/70">{{ auth()->user()->name_thai ?? auth()->user()->name }}</span>
                        <div class="flex items-center gap-3">
                            @include('partials.theme-toggle')
                            @include('partials.locale-switch')
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="text-sm font-medium text-violet-200/70 hover:text-white">{{ __('ออกจากระบบ') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-[90rem] px-4 py-6 pb-28 sm:px-6 sm:py-8 md:pb-8">
            @if (session('status'))
                <div class="mb-4 rounded-xl bg-brand-green-50 px-4 py-3 text-sm text-brand-green-700 ring-1 ring-brand-green-100 dark:bg-brand-green-500/10 dark:text-brand-green-400 dark:ring-brand-green-500/20">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

        @include('partials.mobile-tab-bar')
        @include('partials.pwa-install-banner')
    @endif

    @stack('scripts')
</body>
</html>
