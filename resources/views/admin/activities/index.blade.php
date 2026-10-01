@extends('layouts.dashboard')

@section('content')
@php
    $statusDot = [
        'draft' => 'bg-slate-400',
        'open' => 'bg-brand-green-500',
        'full' => 'bg-amber-500',
        'ongoing' => 'bg-brand-purple-500',
        'closed' => 'bg-slate-400',
        'cancelled' => 'bg-red-500',
    ];
    $statusBadge = [
        'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        'open' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'full' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'ongoing' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400',
        'closed' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
        'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $statusLabel = [
        'draft' => __('ร่าง'), 'open' => __('เปิดรับสมัคร'), 'full' => __('เต็มแล้ว'),
        'ongoing' => __('กำลังดำเนินการ'), 'closed' => __('ปิดกิจกรรม'), 'cancelled' => __('ถูกยกเลิก'),
    ];
    $semesterShort = ['1' => __('เทอม 1'), '2' => __('เทอม 2'), '3' => __('ฤดูร้อน')];

    // Same color families as the status badges above, but as solid chip
    // treatments (icon-bg + border) matching the dashboard's KPI-card
    // language, so the two most-visited admin pages read as one system.
    $statusChipColor = [
        'draft' => ['bg' => 'bg-slate-50 dark:bg-slate-800/60', 'border' => 'border-slate-200 dark:border-slate-700', 'dot' => 'bg-slate-400'],
        'open' => ['bg' => 'bg-brand-green-50 dark:bg-brand-green-500/10', 'border' => 'border-brand-green-100 dark:border-brand-green-500/20', 'dot' => 'bg-brand-green-500'],
        'full' => ['bg' => 'bg-amber-50 dark:bg-amber-500/10', 'border' => 'border-amber-100 dark:border-amber-500/20', 'dot' => 'bg-amber-500'],
        'ongoing' => ['bg' => 'bg-brand-purple-50 dark:bg-brand-purple-500/10', 'border' => 'border-brand-purple-100 dark:border-brand-purple-500/20', 'dot' => 'bg-brand-purple-500'],
        'closed' => ['bg' => 'bg-slate-50 dark:bg-slate-800/60', 'border' => 'border-slate-200 dark:border-slate-700', 'dot' => 'bg-slate-400'],
        'cancelled' => ['bg' => 'bg-red-50 dark:bg-red-500/10', 'border' => 'border-red-100 dark:border-red-500/20', 'dot' => 'bg-red-500'],
    ];
    $totalActivityCount = $statusCounts->sum();
@endphp

<div class="mx-auto max-w-[90rem]">
    <x-brand-header :title="__('รายการกิจกรรมทั้งหมด')" :eyebrow="__('กองพัฒนานักศึกษา')">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @include('partials.activity-view-toggle', ['listRoute' => 'admin.activities.index', 'calendarRoute' => 'admin.activities.calendar', 'active' => 'list'])
                <a href="{{ route('admin.activities.create') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-500 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    {{ __('สร้างกิจกรรม') }}
                </a>
            </div>
        </x-slot:actions>
    </x-brand-header>

    {{-- Status chips: at-a-glance counts, doubling as one-click filters.
         7 chips wrapping onto several lines ate most of a phone screen
         before any activity was visible — a single horizontally-swipeable
         row reads as organized instead, same idea as a native app's
         segmented filter bar. Desktop still has the room, so it wraps
         normally there instead of scrolling for no reason.

         No extra inset here — sitting flush in the normal content column
         (same as this <div>'s own left edge) lines the first chip up with
         the search box below and the header card's edge above, instead of
         indenting it further in than everything else on the page. --}}
    <div class="flex snap-x gap-2 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible sm:pb-0">
        <a href="{{ route('admin.activities.index', array_filter(['academic_year' => $academicYear])) }}"
            @class([
                'inline-flex shrink-0 snap-start items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-medium shadow-soft transition hover:-translate-y-0.5',
                'border-brand-purple-300 bg-brand-purple-100 text-brand-purple-800 dark:border-brand-purple-500/40 dark:bg-brand-purple-500/20 dark:text-brand-purple-300' => ! request('status'),
                'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => request('status'),
            ])>
            {{ __('ทั้งหมด') }} <span class="tabular-nums font-semibold">{{ number_format($totalActivityCount) }}</span>
        </a>
        @foreach ($statusLabel as $statusKey => $label)
            @php $count = $statusCounts[$statusKey] ?? 0; @endphp
            <a href="{{ route('admin.activities.index', array_filter(['academic_year' => $academicYear, 'status' => $statusKey])) }}"
                @class([
                    'inline-flex shrink-0 snap-start items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-medium shadow-soft transition hover:-translate-y-0.5',
                    $statusChipColor[$statusKey]['bg'], $statusChipColor[$statusKey]['border'],
                    'text-slate-800 dark:text-slate-100 ring-1 ring-inset ring-black/5' => request('status') === $statusKey,
                    'text-slate-500 dark:text-slate-400' => request('status') !== $statusKey,
                ])>
                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $statusChipColor[$statusKey]['dot'] }}"></span>
                {{ $label }} <span class="tabular-nums font-semibold">{{ number_format($count) }}</span>
            </a>
        @endforeach
    </div>

    @php
        $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => $y])->all();

        // Drives the mobile filter-sheet trigger's badge — see
        // admin/attendance/index.blade.php for the same pattern.
        $activeFilterCount = collect([
            request()->filled('status'),
            request()->filled('academic_year'),
            request()->filled('semester'),
        ])->filter()->count();
    @endphp

    <form
        method="GET" action="{{ route('admin.activities.index') }}" class="mt-4 space-y-3"
        x-data="{
            filtersOpen: false,
            isDesktop: window.matchMedia('(min-width: 640px)').matches,
            init() {
                const mq = window.matchMedia('(min-width: 640px)');
                mq.addEventListener('change', (e) => { this.isDesktop = e.matches; });
            },
        }"
    >
        <div class="flex gap-2">
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อกิจกรรม') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>

            <button type="submit"
                class="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:from-brand-purple-500 hover:to-brand-purple-400 hover:shadow-lg active:scale-[0.99] sm:px-6">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <span class="hidden sm:inline">{{ __('ค้นหา') }}</span>
            </button>

            {{-- Mobile: opens the filter sheet below instead of showing the
                 3 selects inline (see admin/attendance/index.blade.php,
                 same reasoning). --}}
            <button type="button" @click="filtersOpen = true" aria-label="{{ __('ตัวกรอง') }}"
                class="relative flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl shadow-soft transition-colors duration-200 sm:hidden {{ $activeFilterCount > 0 ? 'bg-brand-purple-600 text-white' : 'border border-slate-200 bg-white text-slate-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400' }}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m9 12h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9-12H3.75m9 12H3.75m9-12H9m6 12v.007M12 6.75a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-6 6a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm0 0H3.75m3 0H12"/></svg>
                @if ($activeFilterCount > 0)
                    <span class="absolute -right-1.5 -top-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-brand-green-500 text-[0.65rem] font-bold text-brand-purple-950">{{ $activeFilterCount }}</span>
                @endif
            </button>
        </div>

        {{-- Desktop/tablet: unchanged 3-column grid. --}}
        <div class="hidden sm:grid sm:grid-cols-3 sm:gap-3">
            <x-premium-select
                name="status" :options="$statusLabel" :selected="request('status')"
                placeholder="{{ __('-- ทุกสถานะ --') }}" autosubmit x-bind:disabled="! isDesktop"
            />

            <x-premium-select
                name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
                placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit x-bind:disabled="! isDesktop"
            />

            <x-premium-select
                name="semester" :options="$semesterShort" :selected="request('semester')"
                placeholder="{{ __('-- ทุกภาคเรียน --') }}" autosubmit x-bind:disabled="! isDesktop"
            />
        </div>

        {{-- Mobile filter sheet — same 3 fields, stacked. --}}
        <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-50 sm:hidden">
            <div
                x-show="filtersOpen" x-cloak x-transition.opacity
                class="absolute inset-0 bg-slate-950/50"
                @click="filtersOpen = false"
            ></div>
            <div
                x-show="filtersOpen" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-3xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-soft-lg dark:bg-slate-900"
            >
                <div class="mx-auto mb-4 h-1.5 w-10 shrink-0 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ __('ตัวกรอง') }}</h3>
                    @if ($activeFilterCount > 0)
                        <a href="{{ route('admin.activities.index', request()->only('search')) }}"
                            class="text-xs font-medium text-brand-purple-600 dark:text-brand-purple-400">
                            {{ __('ล้างตัวกรอง') }}
                        </a>
                    @endif
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</label>
                        <x-premium-select
                            name="status" :options="$statusLabel" :selected="request('status')"
                            placeholder="{{ __('-- ทุกสถานะ --') }}" autosubmit x-bind:disabled="isDesktop"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ปีการศึกษา') }}</label>
                        <x-premium-select
                            name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
                            placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit x-bind:disabled="isDesktop"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ภาคเรียน') }}</label>
                        <x-premium-select
                            name="semester" :options="$semesterShort" :selected="request('semester')"
                            placeholder="{{ __('-- ทุกภาคเรียน --') }}" autosubmit x-bind:disabled="isDesktop"
                        />
                    </div>
                </div>

                <button type="button" @click="filtersOpen = false"
                    class="mt-5 w-full rounded-xl bg-brand-purple-600 px-4 py-3 text-sm font-semibold text-white shadow-soft transition-colors hover:bg-brand-purple-700">
                    {{ __('เสร็จสิ้น') }}
                </button>
            </div>
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <x-sortable-th field="activity_code" :label="__('รหัสกิจกรรม')" />
                    <x-sortable-th field="title" :label="__('ชื่อกิจกรรม')" />
                    <x-sortable-th field="start_at" :label="__('วันที่จัด')" />
                    <x-sortable-th field="academic_year" :label="__('ปีการศึกษา')" />
                    <x-sortable-th field="attendances_count" :label="__('ผู้เช็คชื่อแล้ว')" />
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr @class([
                        'border-b border-slate-100 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:border-slate-800 dark:hover:bg-slate-800/60',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-brand-purple-600 dark:text-brand-purple-400">{{ $activity->activity_code ?? '-' }}</td>
                        <td class="min-w-[20rem] max-w-md whitespace-normal break-words px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $activity->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $activity->start_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            @if ($activity->academic_year)
                                {{ $activity->academic_year }} · {{ $semesterShort[$activity->semester] ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                            @php
                                $checkinRatio = $activity->eligible_count > 0
                                    ? min(100, round($activity->attendances_count / $activity->eligible_count * 100))
                                    : 0;
                            @endphp
                            <div class="flex min-w-[7rem] items-center gap-2">
                                <div class="min-w-[3.5rem]">
                                    <span class="whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-200">{{ $activity->attendances_count }}</span><span class="text-slate-400 dark:text-slate-500">/{{ $activity->eligible_count }}</span>
                                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                        <div class="h-full rounded-full bg-brand-purple-500" style="width: {{ $checkinRatio }}%"></div>
                                    </div>
                                </div>
                                @if ($activity->flagged_count > 0)
                                    <a href="{{ route('admin.attendance.index', $activity) }}"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 ring-1 ring-red-100 transition-colors hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/20"
                                        title="{{ __('มีการเช็คชื่อรอตรวจสอบ') }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                        {{ $activity->flagged_count }} {{ __('รอตรวจสอบ') }}
                                    </a>
                                @endif
                                @if ($activity->pending_late_checkin_count > 0)
                                    <a href="{{ route('admin.late-checkins.index', ['activity_id' => $activity->id]) }}"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600 ring-1 ring-amber-100 transition-colors hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20 dark:hover:bg-amber-500/20"
                                        title="{{ __('มีคำร้องเช็คชื่อย้อนหลังรอตรวจสอบ') }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $activity->pending_late_checkin_count }} {{ __('ย้อนหลัง') }}
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <div
                                x-data="{
                                    pending: '{{ $activity->status }}',
                                    original: '{{ $activity->status }}',
                                    open: false,
                                    confirmOpen: false,
                                    panelStyle: '',
                                    labels: @js($statusLabel),
                                    badgeClass: @js($statusBadge),
                                    dotClass: @js($statusDot),
                                    toggle() {
                                        if (this.open) { this.open = false; return; }
                                        const r = this.$refs.trigger.getBoundingClientRect();
                                        this.panelStyle = `top:${r.bottom + 8}px; left:${r.left}px;`;
                                        this.open = true;
                                    },
                                    pick(value) {
                                        this.open = false;
                                        if (value === this.pending) return;
                                        this.pending = value;
                                        this.confirmOpen = true;
                                    },
                                    cancel() { this.pending = this.original; this.confirmOpen = false; },
                                    proceed() { this.original = this.pending; this.confirmOpen = false; this.$refs.statusForm.submit(); },
                                }"
                                class="relative inline-block"
                            >
                                <form method="POST" action="{{ route('admin.activities.update-status', $activity) }}" x-ref="statusForm" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" :value="pending">
                                </form>

                                <button
                                    type="button" x-ref="trigger" @click="toggle()" aria-haspopup="listbox" :aria-expanded="open"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium shadow-soft ring-1 ring-inset ring-black/5 transition-all duration-150 hover:-translate-y-px hover:shadow-md focus:outline-none focus:ring-4 focus:ring-brand-purple-500/20 dark:ring-white/5"
                                    :class="badgeClass[pending]"
                                >
                                    <span class="relative flex h-1.5 w-1.5 shrink-0">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60" :class="dotClass[pending]"></span>
                                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full" :class="dotClass[pending]"></span>
                                    </span>
                                    <span x-text="labels[pending]"></span>
                                    <svg class="h-3 w-3 shrink-0 opacity-60 transition-transform duration-150" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                                </button>

                                <template x-teleport="body">
                                    <div
                                        x-show="open" x-cloak role="listbox" :style="panelStyle"
                                        @click.outside="open = false" @keydown.escape.window="open = false"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="fixed z-30 w-44 overflow-auto rounded-2xl border border-slate-100 bg-white/95 p-1.5 shadow-soft-lg backdrop-blur-sm dark:border-slate-700 dark:bg-slate-800/95"
                                    >
                                        @foreach ($statusLabel as $statusKey => $label)
                                            <button
                                                type="button" @click="pick('{{ $statusKey }}')" role="option" :aria-selected="pending === '{{ $statusKey }}'"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-brand-purple-50 dark:hover:bg-slate-700/70"
                                                :class="pending === '{{ $statusKey }}' ? 'font-medium text-brand-purple-700 dark:text-brand-purple-400' : 'text-slate-600 dark:text-slate-300'"
                                            >
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $statusDot[$statusKey] }}"></span>
                                                <span class="flex-1 truncate">{{ $label }}</span>
                                                <svg x-show="pending === '{{ $statusKey }}'" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                </template>

                                <template x-teleport="body">
                                    <div x-show="confirmOpen" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm"
                                        @keydown.escape.window="cancel()">
                                        <div
                                            @click.outside="cancel()"
                                            x-show="confirmOpen"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="w-full max-w-sm rounded-[2rem] bg-gradient-to-br from-white/60 via-white/10 to-brand-purple-200/40 p-[1.5px] shadow-soft-lg dark:from-white/10 dark:via-white/5 dark:to-brand-purple-500/20"
                                        >
                                            <div class="rounded-[calc(2rem-1.5px)] bg-white p-7 text-center dark:bg-slate-900">
                                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-purple-50 ring-8 ring-brand-purple-50/50 dark:bg-brand-purple-500/10 dark:ring-brand-purple-500/5">
                                                    <svg class="h-8 w-8 shrink-0 text-brand-purple-600 dark:text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.362-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                                                    </svg>
                                                </div>
                                                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('ยืนยันการเปลี่ยนสถานะ') }}</h3>
                                                <p class="mt-2 break-words text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                                                    {{ __('ต้องการเปลี่ยนสถานะกิจกรรม') }}
                                                    <span class="font-medium text-slate-700 dark:text-slate-200">"{{ $activity->title }}"</span>
                                                    {{ __('เป็น') }}
                                                    "<span x-text="labels[pending]"></span>" {{ __('ใช่หรือไม่?') }}
                                                </p>
                                                <div class="mt-6 grid grid-cols-2 gap-3">
                                                    <button type="button" @click="cancel()"
                                                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-800">
                                                        {{ __('ยกเลิก') }}
                                                    </button>
                                                    <button type="button" @click="proceed()"
                                                        class="rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-200 hover:shadow-lg active:scale-[0.98]">
                                                        {{ __('ยืนยัน') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.attendance.qr-display', $activity) }}" class="font-medium text-brand-green-600 transition-colors hover:text-brand-green-800 dark:text-brand-green-400 dark:hover:text-brand-green-300">{{ __('แสดง QR') }}</a>
                            <a href="{{ route('admin.attendance.index', $activity) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('หน้างาน') }}</a>
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">{{ __('แก้ไข') }}</a>
                            <form method="POST" action="{{ route('admin.activities.duplicate', $activity) }}" class="inline">
                                @csrf
                                <button type="submit" class="font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">{{ __('คัดลอก') }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <x-confirm-submit tone="red" :message="__('ยืนยันลบกิจกรรม \':title\'? การลบไม่สามารถย้อนกลับได้', ['title' => $activity->title])" :label="__('ลบ')"
                                    class="font-medium text-red-500 transition-colors hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">{{ __('ลบ') }}</x-confirm-submit>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีกิจกรรม') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
