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
        $orderOptions = [
            'start_at:asc' => __('วันที่จัด เก่า → ใหม่'),
            'start_at:desc' => __('วันที่จัด ใหม่ → เก่า'),
            'attendances_count:desc' => __('ผู้เช็คชื่อมากสุด'),
            'title:asc' => __('ชื่อกิจกรรม ก–ฮ'),
        ];
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
                <x-premium-select variant="chip" name="order" :options="$orderOptions" :selected="request('order')" placeholder="{{ __('เรียง: ต้องจัดการก่อน') }}" autosubmit x-bind:disabled="! isDesktop" />
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
            ['name' => 'order', 'label' => __('เรียงตาม'), 'all' => __('ต้องจัดการก่อน'), 'options' => $orderOptions, 'selected' => request('order')],
        ]" />
    </form>

    @php
        // Group headings for the default "needs attention first" order (see ActivityController::listGroup()).
        $groupLabels = [1 => __('ต้องตรวจสอบ'), 2 => __('กำลังจัด / วันนี้'), 3 => __('ใกล้ถึง'), 4 => __('ร่าง · ยังไม่เผยแพร่'), 5 => __('จบแล้ว'), 6 => __('ถูกยกเลิก')];
        $groupTone = [1 => 'text-amber-700 dark:text-amber-300', 2 => 'text-brand-green-700 dark:text-brand-green-300'];
        // Month sub-headings only make sense while the list runs in date order.
        $byDate = in_array(request('sort'), [null, '', 'start_at'], true);
        $yearOf = fn ($date) => app()->getLocale() === 'th' ? $date->year + 543 : $date->year;
        $dateTile = [
            'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            'open' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-300',
            'ongoing' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-300',
            'closed' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
            'cancelled' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300',
        ];
        $lastGroup = null;
        $lastMonth = null;
    @endphp

    <div class="mt-2 space-y-2">
        @forelse ($activities as $activity)
            @php
                $status = $activity->displayStatus() === 'ongoing' ? 'ongoing' : ($activity->status === 'full' ? 'open' : $activity->status);
                $month = $activity->start_at->format('Y-m');
                $newGroup = isset($activity->list_group) && $activity->list_group !== $lastGroup;
            @endphp

            @if ($newGroup)
                @php $lastGroup = $activity->list_group; $lastMonth = null; @endphp
                <h2 class="px-1 pt-4 text-sm font-semibold {{ $groupTone[$lastGroup] ?? 'text-slate-700 dark:text-slate-200' }}">{{ $groupLabels[$lastGroup] }}</h2>
            @endif
            @if ($byDate && $month !== $lastMonth)
                @php $lastMonth = $month; @endphp
                <p class="px-1 pt-1 text-xs font-medium text-slate-400 dark:text-slate-500">{{ $activity->start_at->translatedFormat('F') }} {{ $yearOf($activity->start_at) }}</p>
            @endif

            <article class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-3 transition-colors hover:border-brand-purple-200 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-purple-500/30 sm:flex-row sm:items-center sm:p-4">
                <div class="flex min-w-0 flex-1 items-start gap-3 sm:items-center">
                    {{-- Date tile --}}
                    <div class="flex w-14 shrink-0 flex-col items-center rounded-2xl py-2 leading-none {{ $dateTile[$status] ?? $dateTile['draft'] }}">
                        <span class="text-[0.65rem] font-medium">{{ $activity->start_at->translatedFormat('D') }}</span>
                        <span class="mt-1 font-display text-xl tabular-nums">{{ $activity->start_at->format('j') }}</span>
                        <span class="mt-1 text-[0.65rem] font-medium">{{ $activity->start_at->translatedFormat('M') }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.attendance.index', $activity) }}" class="line-clamp-2 font-medium text-slate-900 hover:text-brand-purple-700 dark:text-slate-100 dark:hover:text-brand-purple-300">{{ $activity->title }}</a>
                        <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            @if ($activity->activity_code)
                                <span class="font-mono text-brand-purple-600 dark:text-brand-purple-400">{{ $activity->activity_code }}</span>
                                <span class="text-slate-300 dark:text-slate-600">·</span>
                            @endif
                            <span class="tabular-nums">{{ $activity->start_at->format('H:i') }}–{{ $activity->end_at->isSameDay($activity->start_at) ? $activity->end_at->format('H:i') : $activity->end_at->translatedFormat('j M H:i') }}</span>
                            @if ($activity->location_name)
                                <span class="text-slate-300 dark:text-slate-600">·</span>
                                <span class="max-w-[16rem] truncate">{{ $activity->location_name }}</span>
                            @endif
                            @if ($activity->displayStatus() === 'ongoing')
                                <span class="inline-flex items-center gap-1 font-semibold text-brand-green-700 dark:text-brand-green-300">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-green-500"></span>{{ __('กำลังจัดอยู่') }}
                                </span>
                            @endif
                        </p>
                        @if ($activity->flagged_count > 0 || $activity->pending_late_checkin_count > 0)
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @if ($activity->flagged_count > 0)
                                    <a href="{{ route('admin.attendance.index', $activity) }}" class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 ring-1 ring-red-100 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                                        {{ $activity->flagged_count }} {{ __('รอตรวจสอบ') }}
                                    </a>
                                @endif
                                @if ($activity->pending_late_checkin_count > 0)
                                    <a href="{{ route('admin.late-checkins.index', ['activity_id' => $activity->id]) }}" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600 ring-1 ring-amber-100 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                                        {{ $activity->pending_late_checkin_count }} {{ __('ย้อนหลัง') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-t border-slate-100 pt-3 dark:border-slate-800 sm:flex-nowrap sm:justify-end sm:border-0 sm:pt-0">
                    {{-- Check-in progress only means something once the activity is (or was) live. --}}
                    @if (in_array($activity->status, ['open', 'ongoing', 'full', 'closed'], true))
                        @php $checkinRatio = $activity->eligible_count > 0 ? min(100, round($activity->attendances_count / $activity->eligible_count * 100)) : 0; @endphp
                        <div class="w-24 shrink-0">
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('เช็คชื่อ') }} <span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ $activity->attendances_count }}</span><span class="tabular-nums">/{{ $activity->eligible_count }}</span></p>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-brand-purple-500" style="width: {{ $checkinRatio }}%"></div></div>
                        </div>
                    @else
                        <p class="w-24 shrink-0 text-xs text-slate-400 dark:text-slate-500">{{ __(':count คนมีสิทธิ์', ['count' => $activity->eligible_count]) }}</p>
                    @endif

                    <div class="ml-auto flex items-center gap-1">
                        @include('admin.activities._status-picker')

                        {{-- Only the actions that make sense for the status are shown; the rest live in "⋯". --}}
                        @if (in_array($activity->status, ['open', 'ongoing', 'full'], true))
                            <a href="{{ route('admin.attendance.qr-display', $activity) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-brand-green-700 transition-colors hover:bg-brand-green-50 dark:text-brand-green-300 dark:hover:bg-brand-green-500/15">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg>
                                QR
                            </a>
                        @endif
                        @if ($activity->status === 'draft')
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="rounded-lg px-2.5 py-1.5 text-sm font-semibold text-brand-purple-700 transition-colors hover:bg-brand-purple-50 dark:text-brand-purple-300 dark:hover:bg-brand-purple-500/15">{{ __('แก้ไข') }}</a>
                        @elseif ($activity->status !== 'cancelled')
                            <a href="{{ route('admin.attendance.index', $activity) }}" class="rounded-lg px-2.5 py-1.5 text-sm font-semibold text-brand-purple-700 transition-colors hover:bg-brand-purple-50 dark:text-brand-purple-300 dark:hover:bg-brand-purple-500/15">{{ __('หน้างาน') }}</a>
                        @endif
                        @include('admin.activities._row-menu')
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500">{{ __('ยังไม่มีกิจกรรม') }}</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
