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

        // Less-frequently-used admin tools live in the "เพิ่มเติม" dropdown
        // instead of the main row — adding each new admin capability as
        // another always-visible top-level tab would make the nav
        // unreadable well before this list is done growing.
        $moreItems = $isAdmin
            ? array_filter([
                ['route' => 'admin.reports.index', 'label' => __('รายงาน')],
                ['route' => 'admin.audit-log.index', 'label' => __('ประวัติการตรวจสอบ')],
                ['route' => 'admin.announcements.create', 'label' => __('ส่งประกาศ')],
                $isSuperAdmin ? ['route' => 'admin.faculties.index', 'label' => __('คณะ/สาขา')] : null,
                $isSuperAdmin ? ['route' => 'admin.users.index', 'label' => __('ผู้ใช้งานและสิทธิ์')] : null,
                $isSuperAdmin ? ['route' => 'admin.settings.edit', 'label' => __('เกณฑ์การจบการศึกษา')] : null,
            ])
            : [];
    @endphp

    <nav class="sticky top-0 z-40 bg-brand-purple-950 shadow-soft-lg" x-data="{ mobileOpen: false }">
        <div class="mx-auto max-w-[90rem] px-4 sm:px-6">
            <div class="flex h-16 items-center justify-between gap-4">
                <a href="{{ $isAdmin ? route('admin.dashboard') : route('dashboard') }}" class="flex shrink-0 items-center gap-2.5">
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

                    @if (count($moreItems))
                        @php $moreActive = collect($moreItems)->contains(fn ($item) => request()->routeIs($item['route'].'*')); @endphp
                        <div class="relative" x-data="{ moreOpen: false }" @click.outside="moreOpen = false">
                            <button @click="moreOpen = ! moreOpen" type="button"
                                @class([
                                    'flex items-center gap-1 whitespace-nowrap rounded-lg px-2.5 py-2 text-sm font-medium transition-all duration-200 lg:px-3.5',
                                    'bg-brand-green-500/15 text-brand-green-400' => $moreActive,
                                    'text-violet-200/70 hover:bg-white/5 hover:text-white' => ! $moreActive,
                                ])>
                                {{ __('เพิ่มเติม') }}
                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-200" :class="moreOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <div x-show="moreOpen" x-cloak x-transition
                                class="absolute left-0 top-full z-30 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-100 bg-white py-1.5 shadow-soft-lg dark:border-slate-700 dark:bg-slate-800">
                                @foreach ($moreItems as $item)
                                    <a href="{{ route($item['route']) }}"
                                        @class([
                                            'block px-4 py-2 text-sm font-medium transition-colors',
                                            'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400' => request()->routeIs($item['route'].'*'),
                                            'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/60' => ! request()->routeIs($item['route'].'*'),
                                        ])>
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
                @if ($isAdmin)
                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}"
                            @class([
                                'block rounded-lg px-3.5 py-2.5 text-sm font-medium',
                                'bg-brand-green-500/15 text-brand-green-400' => request()->routeIs($item['route'].'*'),
                                'text-violet-200/70 hover:bg-white/5' => ! request()->routeIs($item['route'].'*'),
                            ])>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    @if (count($moreItems))
                        <div class="my-2 border-t border-white/10"></div>
                        @foreach ($moreItems as $item)
                            <a href="{{ route($item['route']) }}"
                                @class([
                                    'block rounded-lg px-3.5 py-2.5 text-sm font-medium',
                                    'bg-brand-green-500/15 text-brand-green-400' => request()->routeIs($item['route'].'*'),
                                    'text-violet-200/70 hover:bg-white/5' => ! request()->routeIs($item['route'].'*'),
                                ])>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    @endif
                @endif
                <div class="{{ $isAdmin ? 'mt-2 border-t border-white/10 pt-3' : '' }} flex items-center justify-between">
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

    <main class="mx-auto max-w-[90rem] px-4 py-6 sm:px-6 sm:py-8 {{ ! $isAdmin ? 'pb-28 md:pb-8' : '' }}">
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

    @unless ($isAdmin)
        @include('partials.mobile-tab-bar')
        @include('partials.pwa-install-banner')
    @endunless

    @stack('scripts')
</body>
</html>
