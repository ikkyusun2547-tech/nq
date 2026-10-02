@extends('layouts.dashboard')

@section('content')
@php
    $statusDot = [
        'draft' => 'bg-slate-400',
        'open' => 'bg-brand-green-500',
        'closed' => 'bg-slate-400',
        'cancelled' => 'bg-red-500',
    ];
    $statusBadge = [
        'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        'open' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'closed' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
        'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $statusLabel = [
        'draft' => __('ร่าง'), 'open' => __('เปิดลงทะเบียน'),
        'closed' => __('ปิดกิจกรรม'), 'cancelled' => __('ถูกยกเลิก'),
    ];
    $semesterShort = ['1' => __('เทอม 1'), '2' => __('เทอม 2'), '3' => __('ฤดูร้อน')];

    // Same color families as the status badges above, but as solid chip
    // treatments (icon-bg + border) matching the dashboard's KPI-card
    // language, so the two most-visited admin pages read as one system.
    $statusChipColor = [
        'draft' => ['bg' => 'bg-slate-50 dark:bg-slate-800/60', 'border' => 'border-slate-200 dark:border-slate-700', 'dot' => 'bg-slate-400'],
        'open' => ['bg' => 'bg-brand-green-50 dark:bg-brand-green-500/10', 'border' => 'border-brand-green-100 dark:border-brand-green-500/20', 'dot' => 'bg-brand-green-500'],
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
                    class="inline-flex items-center gap-1.5 rounded-xl bg-brand-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:bg-brand-purple-800">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    {{ __('สร้างกิจกรรม') }}
                </a>
            </div>
        </x-slot:actions>
    </x-brand-header>

    {{-- Status chips: at-a-glance counts, doubling as one-click filters.
         One horizontally-swipeable row on phones (wrapping wastes most of
         the screen before any activity shows); wraps normally on desktop. --}}
    <div class="flex snap-x gap-2 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible sm:pb-0">
        <a href="{{ route('admin.activities.index', array_filter(['academic_year' => $academicYear])) }}"
            @if (! request('status')) aria-current="page" @endif
            @class([
                'inline-flex h-9 shrink-0 snap-start items-center gap-2 rounded-full border px-3.5 text-sm transition-colors',
                'border-brand-purple-200 bg-brand-purple-50 font-semibold text-brand-purple-800 dark:border-brand-purple-500/30 dark:bg-brand-purple-500/15 dark:text-brand-purple-200' => ! request('status'),
                'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' => request('status'),
            ])>
            {{ __('ทั้งหมด') }} <span class="tabular-nums text-xs opacity-80">{{ number_format($totalActivityCount) }}</span>
        </a>
        @foreach ($statusLabel as $statusKey => $label)
            @php $count = $statusCounts[$statusKey] ?? 0; @endphp
            <a href="{{ route('admin.activities.index', array_filter(['academic_year' => $academicYear, 'status' => $statusKey])) }}"
                @if (request('status') === $statusKey) aria-current="page" @endif
                @class([
                    'inline-flex h-9 shrink-0 snap-start items-center gap-2 rounded-full border px-3.5 text-sm transition-colors',
                    'border-brand-purple-200 bg-brand-purple-50 font-semibold text-brand-purple-800 dark:border-brand-purple-500/30 dark:bg-brand-purple-500/15 dark:text-brand-purple-200' => request('status') === $statusKey,
                    'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' => request('status') !== $statusKey,
                ])>
                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $statusChipColor[$statusKey]['dot'] }}"></span>
                {{ $label }} <span class="tabular-nums text-xs opacity-80">{{ number_format($count) }}</span>
            </a>
        @endforeach
    </div>

    @php
        $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => __('ปีการศึกษา :year', ['year' => $y])])->all();

        // Drives the mobile filter-sheet trigger's badge.
        $activeFilterCount = collect([
            request()->filled('status'),
            request()->filled('academic_year'),
            request()->filled('semester'),
        ])->filter()->count();
        $searchClass = 'h-11 w-full rounded-full border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-900 transition placeholder:text-slate-400 focus:border-brand-purple-400 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500';
    @endphp

    {{-- The search box is shared by both layouts; the year/semester
         controls exist twice (desktop chips + mobile sheet), so `isDesktop`
         disables whichever copy isn't visible to avoid duplicate params.
         Status lives in the chip row above, so the sheet is its only
         other home. Pressing Enter in the search box submits. --}}
    <form
        id="activity-filters" method="GET" action="{{ route('admin.activities.index') }}" class="mb-5 mt-3"
        x-data="{
            filtersOpen: false,
            isDesktop: window.matchMedia('(min-width: 640px)').matches,
            init() {
                const mq = window.matchMedia('(min-width: 640px)');
                mq.addEventListener('change', (e) => { this.isDesktop = e.matches; });
            },
        }"
    >
        @if (request()->filled('status'))
            <input type="hidden" name="status" value="{{ request('status') }}" x-bind:disabled="! isDesktop">
        @endif

        <div class="flex flex-wrap items-center gap-2">
            <label class="relative min-w-0 flex-1 sm:w-80 sm:flex-none">
                <span class="sr-only">{{ __('ค้นหาชื่อกิจกรรม') }}</span>
                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อกิจกรรม') }}" class="{{ $searchClass }}">
            </label>

            {{-- Mobile: opens the filter sheet below. --}}
            <button type="button" @click="filtersOpen = true" aria-label="{{ __('ตัวกรอง') }}"
                @class([
                    'relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors sm:hidden',
                    'bg-brand-purple-700 text-white' => $activeFilterCount > 0,
                    'border border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => $activeFilterCount === 0,
                ])>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m9 12h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9-12H3.75m9 12H3.75m9-12H9m6 12v.007M12 6.75a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-6 6a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm0 0H3.75m3 0H12"/></svg>
                @if ($activeFilterCount > 0)
                    <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-[0.65rem] font-bold text-brand-purple-700 ring-2 ring-brand-purple-700">{{ $activeFilterCount }}</span>
                @endif
            </button>

            {{-- Desktop/tablet: filter chips beside the search box. --}}
            <div class="hidden flex-wrap items-center gap-2 sm:flex">
                <x-premium-select variant="chip" name="academic_year" :options="$academicYearOptions" :selected="$academicYear" placeholder="{{ __('ทุกปีการศึกษา') }}" autosubmit x-bind:disabled="! isDesktop" />
                <x-premium-select variant="chip" name="semester" :options="$semesterShort" :selected="request('semester')" placeholder="{{ __('ทุกภาคเรียน') }}" autosubmit x-bind:disabled="! isDesktop" />
                @if ($activeFilterCount > 0 || request()->filled('search'))
                    <a href="{{ route('admin.activities.index') }}" class="inline-flex h-9 items-center gap-1 rounded-full px-3 text-sm font-medium text-slate-500 hover:text-brand-purple-700 dark:text-slate-400 dark:hover:text-brand-purple-300">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ __('ล้างตัวกรอง') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Mobile: chip filter sheet. Status lives in the chip row above on
             desktop, so the sheet is its only other home. --}}
        <x-filter-sheet form="activity-filters" :clear-url="route('admin.activities.index', request()->only('search'))" :groups="[
            ['name' => 'status', 'label' => __('สถานะ'), 'all' => __('ทุกสถานะ'), 'options' => $statusLabel, 'selected' => request('status'),
                'dots' => collect($statusChipColor)->map(fn ($c) => $c['dot'])->all()],
            ['name' => 'academic_year', 'label' => __('ปีการศึกษา'), 'all' => __('ทุกปี'), 'options' => $academicYears->mapWithKeys(fn ($y) => [$y => (string) $y])->all(), 'selected' => $academicYear],
            ['name' => 'semester', 'label' => __('ภาคเรียน'), 'all' => __('ทุกภาคเรียน'), 'options' => $semesterShort, 'selected' => request('semester')],
        ]" />
    </form>

    <div class="mt-4 overflow-x-auto rounded-3xl glass-card">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800">
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
                @php
                    // Group headings for the default "needs attention first" order (see ActivityController::listGroup()).
                    $groupLabels = [1 => __('ต้องตรวจสอบ'), 2 => __('กำลังจัด / วันนี้'), 3 => __('ใกล้ถึง'), 4 => __('ร่าง · ยังไม่เผยแพร่'), 5 => __('จบแล้ว'), 6 => __('ถูกยกเลิก')];
                    $groupTone = [1 => 'text-amber-700 dark:text-amber-300', 2 => 'text-brand-purple-700 dark:text-brand-purple-300'];
                    $lastGroup = null;
                @endphp
                @forelse ($activities as $activity)
                    @if (isset($activity->list_group) && $activity->list_group !== $lastGroup)
                        @php $lastGroup = $activity->list_group; @endphp
                        <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-800/30">
                            <td colspan="7" class="px-4 py-2 text-xs font-semibold {{ $groupTone[$lastGroup] ?? 'text-slate-500 dark:text-slate-400' }}">{{ $groupLabels[$lastGroup] }}</td>
                        </tr>
                    @endif
                    <tr @class([
                        'border-b border-slate-100 transition-colors last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60',
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-brand-purple-600 dark:text-brand-purple-400">{{ $activity->activity_code ?? '-' }}</td>
                        <td class="min-w-[12rem] max-w-md whitespace-normal break-words px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $activity->title }}</td>
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
                                        // Open below the chip, or above it when the row is near the bottom of the screen.
                                        const r = this.$refs.trigger.getBoundingClientRect();
                                        const w = 176, h = Math.min(260, window.innerHeight - 24), m = 12;
                                        const left = Math.max(m, Math.min(r.left, window.innerWidth - w - m));
                                        this.panelStyle = (window.innerHeight - r.bottom > h + 16)
                                            ? `top:${r.bottom + 8}px; left:${left}px; max-height:${h}px;`
                                            : `bottom:${window.innerHeight - r.top + 8}px; left:${left}px; max-height:${h}px;`;
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
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-black/5 transition-all duration-150 hover:-translate-y-px hover:shadow-md focus:outline-none focus:ring-4 focus:ring-brand-purple-500/20 dark:ring-white/5"
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
                                        @click.outside="if (! $refs.trigger.contains($event.target)) open = false" @keydown.escape.window="open = false"
                                        @scroll.window="open = false" @resize.window="open = false"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="fixed z-50 w-44 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-soft-lg dark:border-slate-700 dark:bg-slate-800"
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
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
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
                                            class="w-full max-w-sm rounded-[2rem] bg-slate-200 p-px shadow-soft-lg dark:bg-slate-800"
                                        >
                                            <div class="rounded-[calc(2rem-1px)] bg-white p-7 text-center dark:bg-slate-900">
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
                                                        class="rounded-xl bg-brand-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-200 active:scale-[0.98] hover:bg-brand-purple-800">
                                                        {{ __('ยืนยัน') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            {{-- "Ongoing" isn't a status to pick any more; it's shown from the time. --}}
                            @if ($activity->displayStatus() === 'ongoing')
                                <span class="mt-1 flex items-center gap-1 text-[0.7rem] font-semibold text-brand-purple-700 dark:text-brand-purple-300">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-purple-500"></span>
                                    {{ __('กำลังจัดอยู่') }}
                                </span>
                            @endif
                        </td>
                        {{-- The two live-event actions stay visible, the rest go behind a "⋯" menu so the
                             table fits a desktop screen without scrolling. The menu
                             is teleported to <body> and positioned from the button,
                             since the table's overflow container would clip it. --}}
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center justify-end gap-1"
                                x-data="{
                                    open: false,
                                    style: {},
                                    place() {
                                        const r = this.$refs.more.getBoundingClientRect();
                                        const w = 208, m = 12;
                                        const left = Math.max(m, Math.min(r.right - w, window.innerWidth - w - m));
                                        this.style = (window.innerHeight - r.bottom > 230)
                                            ? { left: left + 'px', top: (r.bottom + 6) + 'px' }
                                            : { left: left + 'px', bottom: (window.innerHeight - r.top + 6) + 'px' };
                                    },
                                }"
                                @keydown.escape.window="open = false" @scroll.window="open = false" @resize.window="open = false">
                                <a href="{{ route('admin.attendance.qr-display', $activity) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-green-700 transition-colors hover:bg-brand-green-50 dark:text-brand-green-300 dark:hover:bg-brand-green-500/15">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg>
                                    {{ __('แสดง QR') }}
                                </a>
                                <a href="{{ route('admin.attendance.index', $activity) }}" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-purple-700 transition-colors hover:bg-brand-purple-50 dark:text-brand-purple-300 dark:hover:bg-brand-purple-500/15">{{ __('หน้างาน') }}</a>
                                <button type="button" x-ref="more" @click="place(); open = ! open" :aria-expanded="open" aria-haspopup="menu" aria-label="{{ __('ตัวเลือกเพิ่มเติม') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 12a1.75 1.75 0 11-3.5 0A1.75 1.75 0 016 12zm7.75 0a1.75 1.75 0 11-3.5 0 1.75 1.75 0 013.5 0zM21.5 12a1.75 1.75 0 11-3.5 0 1.75 1.75 0 013.5 0z"/></svg>
                                </button>
                                <template x-teleport="body">
                                    <div x-show="open" x-cloak role="menu" :style="style"
                                        @click.outside="if (! $refs.more.contains($event.target)) open = false"
                                        x-transition.opacity.duration.100ms
                                        class="fixed z-50 w-52 rounded-2xl border border-slate-200 bg-white p-1.5 text-left text-sm shadow-soft-lg dark:border-slate-800 dark:bg-slate-900">
                                        <a href="{{ route('admin.activities.edit', $activity) }}" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                            {{ __('แก้ไข') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.activities.duplicate', $activity) }}">
                                            @csrf
                                            <button type="submit" role="menuitem" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                                                {{ __('คัดลอก') }}
                                            </button>
                                        </form>
                                        <div class="my-1 h-px bg-slate-100 dark:bg-slate-800"></div>
                                        <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-confirm-submit tone="red" :message="__('ยืนยันลบกิจกรรม \':title\'? การลบไม่สามารถย้อนกลับได้', ['title' => $activity->title])" :label="__('ลบ')"
                                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                {{ __('ลบ') }}
                                            </x-confirm-submit>
                                        </form>
                                    </div>
                                </template>
                            </div>
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
