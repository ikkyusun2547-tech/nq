<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ระบบเช็กชื่อกิจกรรมนักศึกษา SRRU' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    @php
        $isAdmin = auth()->user()?->isAdmin();
        $navItems = $isAdmin
            ? [
                ['route' => 'admin.dashboard', 'label' => 'แดชบอร์ด'],
                ['route' => 'admin.activities.index', 'label' => 'กิจกรรม'],
                ['route' => 'admin.external-activities.index', 'label' => 'คำร้องภายนอก'],
                ['route' => 'admin.students.index', 'label' => 'นักศึกษา'],
            ]
            : [
                ['route' => 'dashboard', 'label' => 'แดชบอร์ด'],
                ['route' => 'checkin.show', 'label' => 'เช็กชื่อ'],
                ['route' => 'external-activities.index', 'label' => 'คำร้องภายนอก'],
            ];
    @endphp

    <nav class="sticky top-0 z-40 bg-brand-purple-950 shadow-soft-lg" x-data="{ mobileOpen: false }">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex h-16 items-center justify-between gap-4">
                <a href="{{ $isAdmin ? route('admin.dashboard') : route('dashboard') }}" class="flex shrink-0 items-center gap-2.5">
                    <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="h-11 w-11 shrink-0 object-contain drop-shadow">
                    <span class="hidden whitespace-nowrap text-sm font-semibold leading-tight text-white sm:block">SRRU Check</span>
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
                    <span class="hidden whitespace-nowrap text-sm text-violet-200/70 lg:block">{{ auth()->user()->name_thai ?? auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium text-violet-200/70 transition-colors hover:bg-white/5 hover:text-white">
                            ออกจากระบบ
                        </button>
                    </form>
                </div>

                <button @click="mobileOpen = ! mobileOpen" class="rounded-lg p-2 text-violet-200/70 hover:bg-white/5 hover:text-white md:hidden" aria-label="เมนู">
                    <svg x-show="! mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div x-show="mobileOpen" x-cloak class="border-t border-white/10 md:hidden">
            <div class="space-y-1 px-4 py-3">
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
                <div class="mt-2 flex items-center justify-between border-t border-white/10 pt-3">
                    <span class="text-sm text-violet-200/70">{{ auth()->user()->name_thai ?? auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm font-medium text-violet-200/70 hover:text-white">ออกจากระบบ</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
        @if (session('status'))
            <div class="mb-4 rounded-xl bg-brand-green-50 px-4 py-3 text-sm text-brand-green-700 ring-1 ring-brand-green-100">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
