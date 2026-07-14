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
        'open' => ['label' => __('เปิดรับสมัคร'), 'class' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400'],
        'ongoing' => ['label' => __('กำลังดำเนินการ'), 'class' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400'],
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
    $cardClass = 'rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900';
@endphp
<div class="mx-auto max-w-[90rem]" x-data="{ trendHover: null }">
    <x-brand-header :title="__('แผงควบคุมกองพัฒนานักศึกษา')" :subtitle="__('มหาวิทยาลัยราชภัฏสุรินทร์')" decorated />

    <form method="GET" action="{{ route('admin.dashboard') }}" class="mb-4 flex flex-wrap items-center gap-3 sm:mb-6">
        @php $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => __('ปีการศึกษา :year', ['year' => $y])])->all(); @endphp
        <div class="w-full max-w-xs">
            <x-premium-select
                name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
                placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit
            />
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-purple-50 px-3 py-1.5 text-xs font-medium text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ __('ข้อมูล ณ วันที่ :date', ['date' => now()->translatedFormat('d M Y H:i')]) }}
        </span>
    </form>

    <!-- KPI band -->
    @php
        // Each card's icon carries its own hue (validated with the dataviz
        // skill's scripts/validate_palette.js, --pairs all, since any two
        // cards in this grid can sit side by side) so cards stay tellable
        // apart at a glance — but the card chrome itself is neutral
        // (border + gray icon well) rather than a tinted fill, so the color
        // reads as a small identity accent instead of decoration.
        $overviewCards = [
            ['label' => __('นักศึกษาทั้งหมด'), 'value' => $stats['total_students'], 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'iconColor' => 'text-brand-purple-600 dark:text-brand-purple-400', 'wellColor' => 'bg-brand-purple-50 dark:bg-brand-purple-500/10'],
            ['label' => __('กิจกรรมที่เปิดอยู่'), 'value' => $stats['open_activities'], 'suffix' => __('/ :total ทั้งหมด', ['total' => $stats['total_activities']]), 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5', 'href' => route('admin.activities.index'), 'iconColor' => 'text-sky-600 dark:text-sky-400', 'wellColor' => 'bg-sky-50 dark:bg-sky-500/10'],
            ['label' => __('เช็คชื่อเดือนนี้'), 'value' => $stats['checkins_this_month'], 'delta' => $checkinDelta, 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'iconColor' => 'text-brand-green-600 dark:text-brand-green-400', 'wellColor' => 'bg-brand-green-50 dark:bg-brand-green-500/10'],
            ['label' => __('นักศึกษาปี 4 พร้อมจบ'), 'value' => $stats['graduating_cleared'], 'suffix' => __('/ :total คน', ['total' => $stats['total_year4_students']]), 'icon' => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5', 'href' => route('admin.reports.clearance', ['year' => 4]), 'iconColor' => 'text-teal-600 dark:text-teal-400', 'wellColor' => 'bg-teal-50 dark:bg-teal-500/10'],
        ];

        $actionCards = [
            ['label' => __('การเช็คชื่อติดธงแดง'), 'value' => $stats['flagged_attendances'], 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z', 'href' => route('admin.attendance.flagged'), 'urgent' => $stats['flagged_attendances'] > 0, 'iconColor' => 'text-red-600 dark:text-red-400', 'wellColor' => 'bg-red-50 dark:bg-red-500/10', 'accentBorder' => 'border-l-red-500', 'pulseDot' => 'bg-red-500'],
            ['label' => __('คำร้องภายนอกรออนุมัติ'), 'value' => $stats['pending_external_requests'], 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'href' => route('admin.external-activities.index'), 'urgent' => $stats['pending_external_requests'] > 0, 'iconColor' => 'text-amber-600 dark:text-amber-400', 'wellColor' => 'bg-amber-50 dark:bg-amber-500/10', 'accentBorder' => 'border-l-amber-500', 'pulseDot' => 'bg-amber-500'],
            ['label' => __('เทียบโอนตำแหน่งรออนุมัติ'), 'value' => $stats['pending_credit_transfers'], 'icon' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15', 'href' => route('admin.credit-transfers.index'), 'urgent' => $stats['pending_credit_transfers'] > 0, 'iconColor' => 'text-cyan-600 dark:text-cyan-400', 'wellColor' => 'bg-cyan-50 dark:bg-cyan-500/10', 'accentBorder' => 'border-l-cyan-600', 'pulseDot' => 'bg-cyan-600'],
        ];

        $totalPending = $stats['flagged_attendances'] + $stats['pending_external_requests'] + $stats['pending_credit_transfers'];
    @endphp

    <p class="mb-3 mt-6 px-1 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ภาพรวมระบบ') }}</p>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($overviewCards as $card)
            @php
                $tag = isset($card['href']) ? 'a' : 'div';
                $hrefAttr = isset($card['href']) ? 'href="'.$card['href'].'"' : '';
            @endphp
            <{{ $tag }} {!! $hrefAttr !!}
                class="rounded-xl border border-slate-200 bg-white p-4 transition dark:border-slate-800 dark:bg-slate-900 {{ isset($card['href']) ? 'hover:border-slate-300 dark:hover:border-slate-700' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $card['wellColor'] }}">
                        <svg class="h-[1.1rem] w-[1.1rem] {{ $card['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $card['icon'] }}"/></svg>
                    </span>
                    @if (isset($card['delta']))
                        <span @class([
                            'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[0.68rem] font-semibold tabular-nums',
                            'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400' => $card['delta'] >= 0,
                            'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' => $card['delta'] < 0,
                        ])>
                            <svg class="h-2.5 w-2.5 shrink-0 {{ $card['delta'] < 0 ? 'rotate-180' : '' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3l6 8h-4v6H8v-6H4l6-8z"/></svg>
                            {{ $card['delta'] >= 0 ? '+' : '' }}{{ $card['delta'] }}%
                        </span>
                    @endif
                </div>
                <p class="mt-3 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($card['value']) }}<span class="text-xs font-normal text-slate-400 dark:text-slate-500">{{ $card['suffix'] ?? '' }}</span></p>
                <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
            </{{ $tag }}>
        @endforeach
    </div>

    <div class="mb-3 mt-8 flex items-center justify-between px-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('รอดำเนินการ') }}</p>
        @if ($totalPending > 0)
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[0.68rem] font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                {{ __('รวม :count รายการ', ['count' => $totalPending]) }}
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-brand-green-50 px-2 py-0.5 text-[0.68rem] font-semibold text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                {{ __('ไม่มีรายการค้าง') }}
            </span>
        @endif
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($actionCards as $card)
            <a href="{{ $card['href'] }}"
                class="rounded-xl border border-l-2 border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 {{ ($card['urgent'] ?? false) ? $card['accentBorder'] : '' }}">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $card['wellColor'] }}">
                        <svg class="h-[1.1rem] w-[1.1rem] {{ $card['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $card['icon'] }}"/></svg>
                    </span>
                    @if ($card['urgent'] ?? false)
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $card['pulseDot'] }} opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full {{ $card['pulseDot'] }}"></span>
                        </span>
                    @endif
                </div>
                <p class="mt-3 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($card['value']) }}</p>
                <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
            </a>
        @endforeach
    </div>

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
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadge[$activity->status]['class'] ?? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ $statusBadge[$activity->status]['label'] ?? $activity->status }}
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

    @if ($pendingRequests->isNotEmpty())
        <div class="{{ $cardClass }} mt-6">
            <div class="mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
                <h2 class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('คำร้องกิจกรรมภายนอกล่าสุด') }}</h2>
                <a href="{{ route('admin.external-activities.index') }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('ดูทั้งหมด') }} &rarr;</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-slate-800">
                @foreach ($pendingRequests as $request)
                    <a href="{{ route('admin.external-activities.index') }}" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0 hover:opacity-80">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-xs font-semibold text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                            {{ mb_substr($request->user->name_thai ?? $request->user->name, 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">{{ $request->title }}</p>
                            <p class="truncate text-xs text-gray-400 dark:text-slate-500">{{ $request->user->name_thai ?? $request->user->name }} &middot; {{ $request->organization }}</p>
                        </div>
                        <span class="shrink-0 text-xs tabular-nums font-medium text-amber-600 dark:text-amber-400">{{ __(':hours ชม.', ['hours' => $request->hours_requested]) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Quick actions -->
    <p class="mb-3 mt-8 px-1 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ทางลัด') }}</p>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
        @php
            $quickActions = [
                ['route' => 'admin.activities.index', 'label' => __('จัดการกิจกรรม'), 'desc' => __('สร้าง/แก้ไขกิจกรรม กำหนดสิทธิ์ผู้เข้าร่วม'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'iconColor' => 'text-brand-green-600 dark:text-brand-green-400', 'wellColor' => 'bg-brand-green-50 dark:bg-brand-green-500/10'],
                ['route' => 'admin.external-activities.index', 'label' => __('คำร้องกิจกรรมภายนอก'), 'desc' => __('ตรวจสอบและอนุมัติ/ปฏิเสธคำร้อง'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'iconColor' => 'text-brand-purple-600 dark:text-brand-purple-400', 'wellColor' => 'bg-brand-purple-50 dark:bg-brand-purple-500/10'],
                ['route' => 'admin.credit-transfers.index', 'label' => __('เทียบโอนชั่วโมงจากตำแหน่ง'), 'desc' => __('ตรวจสอบ อนุมัติ และให้เครดิตชั่วโมง'), 'icon' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15', 'iconColor' => 'text-cyan-600 dark:text-cyan-400', 'wellColor' => 'bg-cyan-50 dark:bg-cyan-500/10'],
                ['route' => 'admin.late-checkins.index', 'label' => __('เช็คชื่อย้อนหลัง'), 'desc' => __('ตรวจสอบคำร้องขอเช็คชื่อย้อนหลัง'), 'icon' => 'M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'iconColor' => 'text-sky-600 dark:text-sky-400', 'wellColor' => 'bg-sky-50 dark:bg-sky-500/10'],
                ['route' => 'admin.students.index', 'label' => __('ข้อมูลนักศึกษา'), 'desc' => __('ค้นหา/กรอง ดูรายบุคคล'), 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'iconColor' => 'text-brand-purple-600 dark:text-brand-purple-400', 'wellColor' => 'bg-brand-purple-50 dark:bg-brand-purple-500/10'],
                ['route' => 'admin.announcements.create', 'label' => __('ส่งประกาศ'), 'desc' => __('แจ้งเตือนนักศึกษาทั้งหมดหรือกลุ่มที่เลือก'), 'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c2.31.192 4.594.591 6.81 1.17a48.11 48.11 0 003.65-8.35 48.11 48.11 0 00-3.65-8.35 48.51 48.51 0 00-6.81 1.17m0 6.42a48.517 48.517 0 010-6.42', 'iconColor' => 'text-amber-600 dark:text-amber-400', 'wellColor' => 'bg-amber-50 dark:bg-amber-500/10'],
                ['route' => 'admin.reports.index', 'label' => __('รายงาน'), 'desc' => __('PDF ยื่นจบ และสรุปรายคณะ (Excel)'), 'icon' => 'M12 4v16m8-8H4', 'iconColor' => 'text-brand-green-600 dark:text-brand-green-400', 'wellColor' => 'bg-brand-green-50 dark:bg-brand-green-500/10'],
            ];
            if (auth()->user()->role === 'super_admin') {
                $quickActions[] = ['route' => 'admin.users.index', 'label' => __('ผู้ใช้งานและสิทธิ์'), 'desc' => __('เลื่อน/ลดสิทธิ์แอดมิน ระงับบัญชี'), 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z', 'iconColor' => 'text-slate-600 dark:text-slate-300', 'wellColor' => 'bg-slate-100 dark:bg-slate-800'];
            }
        @endphp
        @foreach ($quickActions as $action)
            <a href="{{ route($action['route']) }}" class="group rounded-xl border border-slate-200 bg-white p-5 transition hover:border-slate-300 hover:shadow-soft dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg {{ $action['wellColor'] }} transition group-hover:scale-105">
                    <svg class="h-5 w-5 {{ $action['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $action['icon'] }}"/></svg>
                </span>
                <p class="mt-3 font-medium text-gray-900 dark:text-slate-100">{{ $action['label'] }}</p>
                <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">{{ $action['desc'] }}</p>
            </a>
        @endforeach
    </div>
</div>
@endsection
