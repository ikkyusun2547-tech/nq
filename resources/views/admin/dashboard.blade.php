@extends('layouts.dashboard')

@section('content')
@php
    // A fixed 5-hue categorical order, validated with scripts/validate_palette.js
    // from the dataviz skill (both light and dark surfaces) — dark mode gets its
    // own darker step per hue rather than reusing the light-mode shade, since the
    // light shades sit above the dark-mode lightness band (read as washed out /
    // insufficiently distinct against the dark card surface).
    $categoryMeta = [
        'culture' => ['label' => __('ทำนุบำรุงศิลปวัฒนธรรม'), 'bar' => 'bg-sky-400 dark:bg-sky-600', 'dot' => 'bg-sky-400 dark:bg-sky-600'],
        'academic' => ['label' => __('วิชาการ'), 'bar' => 'bg-brand-green-500 dark:bg-brand-green-600', 'dot' => 'bg-brand-green-500 dark:bg-brand-green-600'],
        'sports' => ['label' => __('กีฬาและส่งเสริมสุขภาพ'), 'bar' => 'bg-amber-500 dark:bg-amber-600', 'dot' => 'bg-amber-500 dark:bg-amber-600'],
        'volunteer' => ['label' => __('จิตอาสา/บำเพ็ญประโยชน์'), 'bar' => 'bg-brand-purple-500', 'dot' => 'bg-brand-purple-500'],
        'ethics' => ['label' => __('คุณธรรมจริยธรรม'), 'bar' => 'bg-fuchsia-400 dark:bg-fuchsia-600', 'dot' => 'bg-fuchsia-400 dark:bg-fuchsia-600'],
    ];
    $statusBadge = [
        'open' => ['label' => __('เปิดลงทะเบียน'), 'class' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400'],
        'ongoing' => ['label' => __('กำลังจัดอยู่'), 'class' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-300'],
        'draft' => ['label' => __('ร่าง'), 'class' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'],
    ];
    $actionBadge = [
        'approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $actionLabel = ['approved' => __('อนุมัติ'), 'rejected' => __('ปฏิเสธ')];

    $maxCategoryHours = max(1, max($categoryHours));
    $totalCategoryHours = max(0, array_sum($categoryHours));
    $maxTrend = max(1, $monthlyTrend->max('count'));
    $maxFaculty = max(1, $facultyParticipation->max('total') ?? 1);
    $academicYearScopeLabel = $academicYear !== '' ? __('ปีการศึกษา :year', ['year' => $academicYear]) : __('ทุกปีการศึกษา');

    // Month-over-month delta for the "checkins this month" stat — the only
    // card with a clean, unambiguous prior-period comparison (it's always
    // "the actual current calendar month" regardless of the academic-year
    // filter, so comparing it to last calendar month is always apples-to-apples).
    $checkinDelta = null;
    if ($stats['checkins_last_month'] > 0) {
        $checkinDelta = round((($stats['checkins_this_month'] - $stats['checkins_last_month']) / $stats['checkins_last_month']) * 100);
    } elseif ($stats['checkins_this_month'] > 0) {
        $checkinDelta = 100;
    }

    $clearedPct = $stats['total_year4_students'] > 0
        ? round($stats['graduating_cleared'] / $stats['total_year4_students'] * 100)
        : 0;

    // Trend chart geometry — plain straight-segment SVG (no chart library):
    // a 600x160 viewBox scaled to 100% width, y mapped so the tallest point
    // sits with headroom at the top and the baseline has room for the dot +
    // hover hit-target at the bottom.
    $trendPoints = $monthlyTrend->values();
    $trendCount = max(1, $trendPoints->count() - 1);
    $chartW = 600;
    $chartTop = 14;
    $chartBottom = 132;
    $coords = $trendPoints->map(function ($point, $i) use ($trendCount, $chartW, $chartTop, $chartBottom, $maxTrend) {
        $x = $trendCount > 0 ? round($i / $trendCount * $chartW, 1) : 0;
        $y = round($chartBottom - ($point['count'] / $maxTrend) * ($chartBottom - $chartTop), 1);

        return ['x' => $x, 'y' => $y, 'count' => $point['count'], 'label' => $point['label']];
    })->values();
    $linePath = $coords->map(fn ($c, $i) => ($i === 0 ? 'M' : 'L').$c['x'].','.$c['y'])->implode(' ');
    $areaPath = $linePath.' L'.($coords->last()['x'] ?? 0).','.$chartBottom.' L'.($coords->first()['x'] ?? 0).','.$chartBottom.' Z';

    // Shared "plain neutral card" chrome for every non-KPI section below —
    // a bordered white/slate-900 surface instead of the tinted glass-card,
    // matching the sidebar shell's restrained, low-color-noise language.
    // Kept local to this page rather than changed on the shared
    // x-section-card component, which every other admin page still uses.
    $cardClass = 'rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900';
@endphp
<div class="mx-auto max-w-[90rem]" x-data="{ trendHover: null, inboxTab: 'all' }">
    @php
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? __('สวัสดีตอนเช้า') : ($hour < 17 ? __('สวัสดีตอนบ่าย') : __('สวัสดีตอนเย็น'));
        $inboxTotal = array_sum($inbox['counts']);
        // The inbox shows at most this many rows per tab so a busy day doesn't
        // push the rest of the dashboard far down; the full queues are a click away.
        $inboxLimit = 5;
        $inboxSeen = [];
        $inboxTypes = [
            'flagged' => ['label' => __('ติดธง'), 'long' => __('เช็คชื่อติดธงแดง'), 'url' => route('admin.attendance.flagged'), 'well' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300', 'icon' => 'M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5'],
            'external' => ['label' => __('กิจกรรมภายนอก'), 'long' => __('คำร้องกิจกรรมภายนอก'), 'url' => route('admin.external-activities.index'), 'well' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'icon' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418'],
            'late' => ['label' => __('ย้อนหลัง'), 'long' => __('เช็คชื่อย้อนหลัง'), 'url' => route('admin.late-checkins.index'), 'well' => 'bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            'credit' => ['label' => __('เทียบโอน'), 'long' => __('เทียบโอนตำแหน่ง'), 'url' => route('admin.credit-transfers.index'), 'well' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300', 'icon' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
        ];
        $timelineStatus = [
            'ongoing' => ['label' => __('กำลังจัดอยู่'), 'class' => 'text-brand-green-700 dark:text-brand-green-300'],
            'open' => ['label' => __('เปิดลงทะเบียน'), 'class' => 'text-brand-green-700 dark:text-brand-green-300'],
            'draft' => ['label' => __('ร่าง · ยังไม่เผยแพร่'), 'class' => 'text-slate-500 dark:text-slate-400'],
            'closed' => ['label' => __('ปิดกิจกรรม'), 'class' => 'text-slate-500 dark:text-slate-400'],
        ];
        $kpis = [
            ['label' => __('นักศึกษาในระบบ'), 'value' => number_format($stats['total_students']), 'href' => route('admin.students.index')],
            ['label' => __('กิจกรรมที่เปิดอยู่'), 'value' => number_format($stats['open_activities']), 'suffix' => __('/ :total ทั้งหมด', ['total' => $stats['total_activities']]), 'href' => route('admin.activities.index')],
            ['label' => __('เช็คชื่อเดือนนี้'), 'value' => number_format($stats['checkins_this_month']), 'delta' => $checkinDelta],
            ['label' => __('ปี 4 ผ่านเกณฑ์'), 'value' => $clearedPct.'%', 'suffix' => __(':cleared / :total คน', ['cleared' => $stats['graduating_cleared'], 'total' => $stats['total_year4_students']]), 'href' => route('admin.reports.clearance', ['year' => 4]), 'tone' => 'text-brand-green-700 dark:text-brand-green-300'],
        ];
    @endphp

    {{-- Greeting --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ now()->translatedFormat('l j F') }} {{ app()->getLocale() === 'th' ? now()->year + 543 : now()->year }}</p>
            @php $me = auth()->user(); @endphp
            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600 dark:text-slate-400">
                <span>{{ $greeting }}, <span class="font-display text-xl text-slate-900 dark:text-white">{{ $me->name_thai ?? $me->name }}</span></span>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    'bg-brand-purple-700 text-white dark:bg-brand-purple-600' => $me->role === 'super_admin',
                    'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300' => $me->role !== 'super_admin',
                ])>{{ $me->role === 'super_admin' ? __('Admin สูงสุด') : __('Admin') }}</span>
            </p>
            <h1 class="mt-1 font-display text-[1.65rem] leading-snug text-slate-900 dark:text-white sm:text-3xl">
                @if ($inboxTotal > 0)
                    {{ __('มี') }} <span class="text-brand-purple-700 dark:text-brand-purple-300">{{ __(':count รายการ', ['count' => $inboxTotal]) }}</span> {{ __('รอคุณตรวจ') }}
                @else
                    {{ __('ไม่มีงานค้างตรวจ') }}
                @endif
            </h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="w-56">
                @php $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => __('ปีการศึกษา :year', ['year' => $y])])->all(); @endphp
                <x-premium-select name="academic_year" :options="$academicYearOptions" :selected="$academicYear" placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit />
            </form>
            <a href="{{ route('admin.activities.create') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-purple-700 px-4 text-sm font-semibold text-white shadow-[0_8px_18px_rgb(109_40_217/0.22)] transition hover:bg-brand-purple-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('สร้างกิจกรรม') }}
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            @php $tag = isset($kpi['href']) ? 'a' : 'div'; @endphp
            <{{ $tag }} @if (isset($kpi['href'])) href="{{ $kpi['href'] }}" @endif
                class="rounded-3xl border border-slate-200 bg-white p-4 transition dark:border-slate-800 dark:bg-slate-900 sm:p-5 {{ isset($kpi['href']) ? 'hover:border-brand-purple-300 dark:hover:border-brand-purple-500/40' : '' }}">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs text-slate-500 dark:text-slate-400 sm:text-sm">{{ $kpi['label'] }}</p>
                    @if (isset($kpi['delta']) && $kpi['delta'] !== null)
                        <span @class([
                            'rounded-full px-1.5 py-0.5 text-[0.68rem] font-semibold tabular-nums',
                            'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-300' => $kpi['delta'] >= 0,
                            'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' => $kpi['delta'] < 0,
                        ])>{{ $kpi['delta'] >= 0 ? '+' : '' }}{{ $kpi['delta'] }}%</span>
                    @endif
                </div>
                <p class="mt-1.5 font-display text-2xl tabular-nums sm:text-[1.75rem] {{ $kpi['tone'] ?? 'text-slate-900 dark:text-white' }}">{{ $kpi['value'] }}</p>
                @isset($kpi['suffix'])
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $kpi['suffix'] }}</p>
                @endisset
            </{{ $tag }}>
        @endforeach
    </div>

    {{-- Inbox + today --}}
    <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-label="{{ __('รายการรอตรวจ') }}" class="rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="px-5 pt-5">
                <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('รายการรอตรวจ') }}</h2>
                <div role="tablist" class="mt-3 flex flex-wrap gap-x-5 gap-y-1 border-b border-slate-100 text-sm dark:border-slate-800">
                    <button type="button" role="tab" @click="inboxTab = 'all'" :aria-selected="inboxTab === 'all'"
                        class="-mb-px shrink-0 border-b-2 pb-2.5 transition-colors"
                        :class="inboxTab === 'all' ? 'border-brand-purple-700 font-semibold text-brand-purple-700 dark:border-brand-purple-400 dark:text-brand-purple-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'">
                        {{ __('ทั้งหมด') }} <span class="text-xs">{{ $inboxTotal }}</span>
                    </button>
                    @foreach ($inboxTypes as $key => $type)
                        <button type="button" role="tab" @click="inboxTab = '{{ $key }}'" :aria-selected="inboxTab === '{{ $key }}'"
                            class="-mb-px shrink-0 border-b-2 pb-2.5 transition-colors"
                            :class="inboxTab === '{{ $key }}' ? 'border-brand-purple-700 font-semibold text-brand-purple-700 dark:border-brand-purple-400 dark:text-brand-purple-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'">
                            {{ $type['label'] }} <span class="text-xs">{{ $inbox['counts'][$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="p-2">
                @forelse ($inbox['items'] as $item)
                    @php
                        $type = $inboxTypes[$item->type];
                        $typeIdx = $inboxSeen[$item->type] = ($inboxSeen[$item->type] ?? -1) + 1;
                        $inAll = $loop->index < $inboxLimit;
                        $inType = $typeIdx < $inboxLimit;
                    @endphp
                    <div x-show="{{ $inAll ? "inboxTab === 'all'" : 'false' }} || {{ $inType ? "inboxTab === '{$item->type}'" : 'false' }}" @if (! $inAll) x-cloak @endif
                        class="flex items-center gap-3 rounded-2xl px-3 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/60 sm:gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $type['well'] }}" title="{{ $type['long'] }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $type['icon'] }}"/></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm text-slate-900 dark:text-white">
                                <span class="font-semibold">{{ $item->student?->name_thai ?? $item->student?->name ?? '-' }}</span>
                                <span class="text-slate-500 dark:text-slate-400">· {{ $type['long'] }}</span>
                            </span>
                            <span class="block truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ $item->title }}@if ($item->detail) · {{ $item->detail }}@endif · {{ $item->at?->diffForHumans() }}
                            </span>
                        </span>
                        <a href="{{ $item->url }}" class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:text-slate-200 dark:hover:border-brand-purple-500/40 dark:hover:text-brand-purple-300">{{ __('ตรวจสอบ') }}</a>
                    </div>
                @empty
                    <div class="px-3 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('เคลียร์ครบแล้ว') }}</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('ไม่มีคำร้องหรือการเช็คชื่อที่รอตรวจสอบ') }}</p>
                    </div>
                @endforelse

                @if ($inboxTotal > $inboxLimit)
                    <div x-show="inboxTab === 'all'" class="mt-1 flex flex-wrap items-center gap-2 border-t border-slate-100 px-3 pb-1 pt-3 text-xs dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('แสดง :shown จาก :total รายการ · ตรวจต่อที่', ['shown' => $inboxLimit, 'total' => $inboxTotal]) }}</span>
                        @foreach ($inboxTypes as $key => $type)
                            @if ($inbox['counts'][$key] > 0)
                                <a href="{{ $type['url'] }}" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700 transition-colors hover:bg-brand-purple-50 hover:text-brand-purple-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-brand-purple-500/15 dark:hover:text-brand-purple-300">
                                    {{ $type['label'] }} <span class="text-slate-500 dark:text-slate-400">{{ $inbox['counts'][$key] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                @foreach ($inboxTypes as $key => $type)
                    @if ($inbox['counts'][$key] > 0)
                        <a x-show="inboxTab === '{{ $key }}'" x-cloak href="{{ $type['url'] }}" class="block px-3 py-2.5 text-sm font-semibold text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ดูทั้งหมด :count รายการ', ['count' => $inbox['counts'][$key]]) }} →</a>
                    @endif
                @endforeach
            </div>
        </section>

        <aside class="flex flex-col gap-6">
            <section aria-label="{{ __('วันนี้') }}" class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('วันนี้') }}</h2>
                    <a href="{{ route('admin.activities.calendar') }}" class="text-sm font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ปฏิทิน') }}</a>
                </div>
                <div class="mt-3 space-y-1">
                    @forelse ($todayActivities->take($inboxLimit) as $activity)
                        @php $live = $activity->start_at->isPast() && $activity->end_at->isFuture() && in_array($activity->status, ['open', 'ongoing'], true); @endphp
                        <a href="{{ route('admin.attendance.index', $activity) }}"
                            class="grid grid-cols-[4.25rem_minmax(0,1fr)] gap-3 rounded-2xl px-3 py-2.5 transition {{ $live ? 'bg-brand-green-50 dark:bg-brand-green-500/10' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                            <span class="whitespace-nowrap text-sm tabular-nums {{ $live ? 'font-semibold text-brand-green-700 dark:text-brand-green-300' : 'text-slate-500 dark:text-slate-400' }}">{{ $activity->start_at->isToday() ? $activity->start_at->format('H:i') : __('ต่อเนื่อง') }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-900 dark:text-white">{{ $activity->title }}</span>
                                <span class="block truncate text-xs {{ $timelineStatus[$activity->displayStatus()]['class'] ?? 'text-slate-500' }}">
                                    {{ $live ? '● ' : '' }}{{ $timelineStatus[$activity->displayStatus()]['label'] ?? $activity->status }} · {{ __('เช็คชื่อแล้ว :count คน', ['count' => $activity->attendances_count]) }}
                                </span>
                            </span>
                        </a>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('ไม่มีกิจกรรมวันนี้') }}</p>
                    @endforelse
                    @if ($todayActivities->count() > $inboxLimit)
                        <a href="{{ route('admin.activities.calendar') }}" class="block pt-2 text-center text-xs font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ดูอีก :count กิจกรรมในปฏิทิน', ['count' => $todayActivities->count() - $inboxLimit]) }} →</a>
                    @endif
                </div>
            </section>

            <section aria-label="{{ __('ทางลัด') }}" class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('ทางลัด') }}</h2>
                <div class="mt-2 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ([
                        [route('admin.announcements.create'), __('ส่งประกาศถึงนักศึกษา')],
                        [route('admin.reports.clearance', ['year' => 4]), __('รายชื่อปี 4 ผ่านเกณฑ์ (PDF)')],
                        [route('admin.students.import.create'), __('นำเข้ารายชื่อนักศึกษา')],
                        [route('admin.contact.index'), __('ข้อความจากนักศึกษา')],
                    ] as [$href, $label])
                        <a href="{{ $href }}" class="flex items-center justify-between py-3 text-sm text-slate-700 hover:text-brand-purple-700 dark:text-slate-300 dark:hover:text-brand-purple-300">
                            {{ $label }}
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </a>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>

    <h2 class="mb-3 mt-10 font-display text-lg text-slate-900 dark:text-white">{{ __('ภาพรวมกิจกรรม') }} <span class="text-sm font-normal text-slate-500 dark:text-slate-400">· {{ $academicYearScopeLabel }}</span></h2>

    <!-- Success overview: clearance ring + category distribution -->
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-5">
        <div class="{{ $cardClass }} lg:col-span-2">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ภาพรวมความสำเร็จนักศึกษาปี 4') }}</h2>
            </div>
            <div class="flex items-center gap-5">
                @php
                    $ringSize = 76;
                    $ringStroke = 7;
                    $ringRadius = ($ringSize - $ringStroke) / 2;
                    $ringCircumference = 2 * M_PI * $ringRadius;
                    $ringProgress = min(1, $clearedPct / 100);
                    $ringOffset = $ringCircumference * (1 - $ringProgress);
                @endphp
                <div class="flex flex-col items-center">
                    <div class="relative" style="width: {{ $ringSize }}px; height: {{ $ringSize }}px;">
                        <svg width="{{ $ringSize }}" height="{{ $ringSize }}" viewBox="0 0 {{ $ringSize }} {{ $ringSize }}" class="-rotate-90">
                            <circle cx="{{ $ringSize / 2 }}" cy="{{ $ringSize / 2 }}" r="{{ $ringRadius }}" fill="none" stroke="rgb(5 150 105 / 0.12)" stroke-width="{{ $ringStroke }}"/>
                            <circle cx="{{ $ringSize / 2 }}" cy="{{ $ringSize / 2 }}" r="{{ $ringRadius }}" fill="none" stroke="#059669" stroke-width="{{ $ringStroke }}"
                                stroke-linecap="round" stroke-dasharray="{{ $ringCircumference }}" stroke-dashoffset="{{ $ringOffset }}"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-base font-bold text-emerald-600 dark:text-emerald-400">{{ $clearedPct }}%</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('ผ่านเกณฑ์') }}</p>
                </div>
                <div class="flex-1 space-y-2.5">
                    <div>
                        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats['graduating_cleared']) }} <span class="text-sm font-normal text-slate-400 dark:text-slate-500">/ {{ number_format($stats['total_year4_students']) }} {{ __('คน') }}</span></p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('ผ่านเกณฑ์ครบ 100% พร้อมยื่นจบ') }}</p>
                    </div>
                    <a href="{{ route('admin.reports.clearance', ['year' => 4]) }}" class="inline-flex items-center gap-1 text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">
                        {{ __('ดาวน์โหลดรายชื่อ (PDF)') }} &rarr;
                    </a>
                </div>
            </div>
        </div>

        <div class="{{ $cardClass }} lg:col-span-3">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20V10M12 20V4M20 20V14"/></svg>
                </span>
                <h2 class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ชั่วโมงกิจกรรมแยกตามหมวดหมู่') }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $academicYearScopeLabel }}</span>
            </div>

            @if ($totalCategoryHours > 0)
                <!-- distribution strip: part-to-whole at a glance -->
                <div class="mb-1 flex h-3 gap-0.5 overflow-hidden rounded-full">
                    @foreach ($categoryHours as $category => $hours)
                        @continue($hours <= 0)
                        <div class="{{ $categoryMeta[$category]['bar'] ?? 'bg-slate-400' }} h-full first:rounded-l-full last:rounded-r-full" style="flex-grow: {{ $hours }}; flex-basis: 0;" title="{{ $categoryMeta[$category]['label'] ?? $category }}: {{ number_format($hours) }} {{ __('ชม.') }}"></div>
                    @endforeach
                </div>
                <p class="mb-3 text-[0.68rem] text-slate-400 dark:text-slate-500">{{ __('รวม :hours ชม. สะสมทั้งระบบ', ['hours' => number_format($totalCategoryHours)]) }}</p>
            @endif

            <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                @foreach ($categoryHours as $category => $hours)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 shrink-0 rounded-full {{ $categoryMeta[$category]['dot'] ?? 'bg-slate-400' }}"></span>{{ $categoryMeta[$category]['label'] ?? $category }}</span>
                            <span class="tabular-nums font-medium text-gray-700 dark:text-slate-200">{{ __(':hours ชม.', ['hours' => number_format($hours)]) }}</span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800">
                            <div class="h-full rounded-full {{ $categoryMeta[$category]['bar'] ?? 'bg-slate-400' }}" style="width: {{ max(3, round($hours / $maxCategoryHours * 100)) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Trend + faculty leaderboard -->
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="{{ $cardClass }} lg:col-span-2">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8M21 7v6h-6"/></svg>
                </span>
                <h2 class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $academicYear !== '' ? __('แนวโน้มการเช็คชื่อรายเดือน') : __('แนวโน้มการเช็คชื่อ 6 เดือนล่าสุด') }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $academicYearScopeLabel }}</span>
            </div>

            <div class="relative">
                <svg viewBox="0 0 {{ $chartW }} 146" class="w-full overflow-visible text-brand-purple-500 dark:text-brand-purple-400" preserveAspectRatio="none" style="height: 10.5rem;">
                    <defs>
                        <linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="currentColor" stop-opacity="0.18"/>
                            <stop offset="100%" stop-color="currentColor" stop-opacity="0"/>
                        </linearGradient>
                    </defs>

                    <!-- recessive gridlines -->
                    @foreach ([0, 0.5, 1] as $frac)
                        <line x1="0" x2="{{ $chartW }}" y1="{{ $chartTop + $frac * ($chartBottom - $chartTop) }}" y2="{{ $chartTop + $frac * ($chartBottom - $chartTop) }}" stroke="currentColor" stroke-opacity="0.12" stroke-width="1"/>
                    @endforeach

                    <path d="{{ $areaPath }}" fill="url(#trendFill)" stroke="none"/>
                    <path d="{{ $linePath }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>

                    @foreach ($coords as $i => $c)
                        <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="{{ $i === $coords->count() - 1 ? 4 : 3 }}" fill="currentColor" stroke="white" stroke-width="2" class="dark:[stroke:#0f172a]"/>
                        <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="14" fill="transparent" style="cursor: pointer;" @mouseenter="trendHover = {{ $i }}" @mouseleave="trendHover = null"/>
                    @endforeach
                </svg>

                @foreach ($coords as $i => $c)
                    <div x-show="trendHover === {{ $i }}" x-cloak x-transition.opacity.duration.100ms
                        class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-lg bg-slate-900 px-2.5 py-1.5 text-center text-xs font-medium text-white shadow-soft-lg dark:bg-slate-700"
                        style="left: {{ $chartW > 0 ? ($c['x'] / $chartW * 100) : 0 }}%; top: {{ max(0, ($c['y'] / 146) * 100 - 8) }}%;">
                        <span class="block font-semibold tabular-nums">{{ number_format($c['count']) }}</span>
                        <span class="block text-[0.65rem] text-white/70">{{ $c['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-1 flex justify-between px-0.5">
                @foreach ($coords as $c)
                    <span class="text-[0.65rem] text-gray-400 dark:text-slate-500">{{ $c['label'] }}</span>
                @endforeach
            </div>
        </div>

        <!-- Faculty participation -->
        <div class="{{ $cardClass }}">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                </span>
                <h2 class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('การเข้าร่วมแยกตามคณะ') }}</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $academicYearScopeLabel }}</span>
            </div>
            <div class="space-y-3.5">
                @forelse ($facultyParticipation as $i => $row)
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[0.65rem] font-bold {{ $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                                <span class="truncate pr-2">{{ $row->faculty }}</span>
                                <span class="shrink-0 tabular-nums font-medium text-gray-700 dark:text-slate-200">{{ number_format($row->total) }}</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-brand-green-500 dark:bg-brand-green-600" style="width: {{ max(3, round($row->total / $maxFaculty * 100)) }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-gray-400 dark:text-slate-500">{{ __('ยังไม่มีข้อมูลการเช็คชื่อ') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Upcoming activities + recent admin activity -->
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="{{ $cardClass }}">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                </span>
                <h2 class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('กิจกรรมที่กำลังจะถึง') }}</h2>
                <a href="{{ route('admin.activities.index') }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('ดูทั้งหมด') }} &rarr;</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-slate-800">
                @forelse ($upcomingActivities as $activity)
                    <a href="{{ route('admin.attendance.index', $activity) }}" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0 hover:opacity-80">
                        <span class="h-8 w-1.5 shrink-0 rounded-full {{ $categoryMeta[$activity->activity_category]['bar'] ?? 'bg-slate-300' }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">{{ $activity->title }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $activity->start_at->translatedFormat('d M Y H:i') }}</p>
                        </div>
                        <span class="shrink-0 text-xs tabular-nums text-gray-400 dark:text-slate-500">{{ $activity->attendances_count }}/{{ $activity->required_count }}</span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadge[$activity->displayStatus()]['class'] ?? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ $statusBadge[$activity->displayStatus()]['label'] ?? $activity->status }}
                        </span>
                    </a>
                @empty
                    <p class="py-6 text-center text-xs text-gray-400 dark:text-slate-500">{{ __('ไม่มีกิจกรรมที่กำลังจะถึง') }}</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $cardClass }}">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                </span>
                <h2 class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ประวัติการตรวจสอบล่าสุด') }}</h2>
                <a href="{{ route('admin.audit-log.index') }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('ดูทั้งหมด') }} &rarr;</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-slate-800">
                @forelse ($recentActivity as $entry)
                    <div class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-purple-500 text-xs font-semibold text-white">
                            {{ mb_substr($entry->reviewer->name_thai ?? $entry->reviewer->name ?? '-', 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-gray-900 dark:text-slate-100">
                                <span class="font-medium">{{ $entry->reviewer->name_thai ?? $entry->reviewer->name ?? '-' }}</span>
                                <span class="text-gray-400 dark:text-slate-500">{{ $actionLabel[$entry->action] ?? $entry->action }}</span>
                                {{ $entry->type_label }}
                            </p>
                            <p class="truncate text-xs text-gray-400 dark:text-slate-500">{{ $entry->student->name_thai ?? $entry->student->name ?? '-' }} &middot; {{ $entry->title }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $actionBadge[$entry->action] ?? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ $actionLabel[$entry->action] ?? $entry->action }}
                        </span>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-gray-400 dark:text-slate-500">{{ __('ยังไม่มีประวัติการตรวจสอบ') }}</p>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
