@extends('layouts.dashboard')

@section('content')
@php
    $user = auth()->user();
    $categoryMeta = [
        'culture' => ['label' => __('ทำนุบำรุงศิลปวัฒนธรรม'), 'dot' => 'bg-sky-400'],
        'academic' => ['label' => __('วิชาการ'), 'dot' => 'bg-brand-green-500'],
        'sports' => ['label' => __('กีฬาและส่งเสริมสุขภาพ'), 'dot' => 'bg-amber-400'],
        'volunteer' => ['label' => __('จิตอาสา/บำเพ็ญประโยชน์'), 'dot' => 'bg-brand-purple-500'],
        'ethics' => ['label' => __('คุณธรรมจริยธรรม'), 'dot' => 'bg-fuchsia-400'],
    ];
    $sourceMeta = [
        'realtime' => ['label' => __('สแกน QR เช็คชื่อ'), 'dot' => 'bg-brand-purple-500'],
        'self_report' => ['label' => __('รายงานตนเอง'), 'dot' => 'bg-fuchsia-400'],
        'late_request' => ['label' => __('เช็คชื่อย้อนหลัง'), 'dot' => 'bg-sky-400'],
        'external' => ['label' => __('กิจกรรมภายนอก'), 'dot' => 'bg-brand-green-500'],
        'credit_transfer' => ['label' => __('เทียบโอนตำแหน่ง'), 'dot' => 'bg-amber-400'],
    ];
    $sourceTotal = array_sum($summary['hours_by_source']);

    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? __('สวัสดีตอนเช้า') : ($hour < 17 ? __('สวัสดีตอนบ่าย') : __('สวัสดีตอนเย็น'));
    $hoursPct = $summary['required_hours'] > 0 ? min(100, round($summary['total_hours'] / $summary['required_hours'] * 100)) : 0;
    $activitiesPct = $summary['required_activities'] > 0 ? min(100, round($summary['total_activities'] / $summary['required_activities'] * 100)) : 0;
    $thaiYear = fn ($date) => app()->getLocale() === 'th' ? $date->year + 543 : $date->year;

    $remaining = function ($closesAt) {
        $minutes = max(0, (int) now()->diffInMinutes($closesAt));
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h > 0 ? __('เหลือ :h ชม. :m นาที', ['h' => $h, 'm' => $m]) : __('เหลือ :m นาที', ['m' => $m]);
    };

    // With nothing to check in to right now, the next activity takes the
    // hero spot (and drops out of the "ถัดไป" list below it).
    $nextActivity = $nowActivities->isEmpty() ? $upcomingActivities->first() : null;
    $upcomingList = $nextActivity ? $upcomingActivities->slice(1)->values() : $upcomingActivities;
    $countdown = function ($startAt) {
        $days = (int) now()->startOfDay()->diffInDays($startAt->copy()->startOfDay());
        if ($days === 0) {
            $minutes = max(0, (int) now()->diffInMinutes($startAt));

            return $minutes >= 60
                ? __('อีก :h ชม. :m นาที', ['h' => intdiv($minutes, 60), 'm' => $minutes % 60])
                : __('อีก :m นาที', ['m' => $minutes]);
        }

        return $days === 1 ? __('พรุ่งนี้') : __('อีก :days วัน', ['days' => $days]);
    };
@endphp

<div class="mx-auto max-w-6xl" x-data="{ showDetail: false, detail: null, historyTab: 'approved' }">

    {{-- Greeting + headline --}}
    <section class="mb-6">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600 dark:text-slate-400">
            <span>{{ $greeting }}, <span class="font-display text-xl text-slate-900 dark:text-white">{{ $user->name_thai }}</span></span>
            {{-- Beside the name from sm up; on phones it sits under the student line instead. --}}
            @if ($currentPositionLabel)
                <span class="hidden rounded-full bg-brand-purple-700 px-2.5 py-0.5 text-xs font-semibold text-white dark:bg-brand-purple-600 sm:inline-flex">{{ __('ดำรงตำแหน่ง') }}: {{ $currentPositionLabel }}</span>
            @endif
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('ชั้นปีที่ :year', ['year' => $summary['current_year'] ?? '-']) }} · {{ $user->program_type === 'special' ? __('ภาคพิเศษ (กศ.บป.)') : __('ภาคปกติ') }} · {{ __('รหัส :id', ['id' => $user->student_id]) }}
        </p>
        @if ($currentPositionLabel)
            <span class="mt-2 inline-flex rounded-full bg-brand-purple-700 px-2.5 py-0.5 text-xs font-semibold text-white dark:bg-brand-purple-600 sm:hidden">{{ __('ดำรงตำแหน่ง') }}: {{ $currentPositionLabel }}</span>
        @endif
        <h1 class="mt-3 font-display text-[1.65rem] leading-snug text-slate-900 dark:text-white sm:text-3xl" style="text-wrap: balance;">
            @if ($nowActivities->isNotEmpty())
                {{ __('วันนี้คุณมี') }} <span class="text-brand-purple-700 dark:text-brand-purple-300">{{ __(':count กิจกรรม', ['count' => $nowActivities->count()]) }}</span> {{ __('ที่เช็คชื่อได้ตอนนี้') }}
            @elseif ($upcomingActivities->isNotEmpty())
                {{ __('ยังไม่มีกิจกรรมให้เช็คชื่อตอนนี้') }}
            @else
                {{ __('ยังไม่มีกิจกรรมที่กำลังจะมาถึง') }}
            @endif
        </h1>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Main column --}}
        <div class="flex min-w-0 flex-col gap-6">

            {{-- This week --}}
            <section aria-label="{{ __('สัปดาห์นี้') }}">
                <div class="mb-2 flex items-center justify-between">
                    {{-- Today's month, not the Monday's — the strip can start in the previous month. --}}
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ now()->translatedFormat('F') }} {{ $thaiYear(now()) }}</h2>
                    <a href="{{ route('activities.calendar') }}" class="text-sm font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ดูปฏิทิน') }}</a>
                </div>
                <div class="grid grid-cols-7 gap-1.5">
                    @foreach ($week as $day)
                        @php $isToday = $day['date']->isToday(); @endphp
                        <a href="{{ route('activities.calendar', ['month' => $day['date']->format('Y-m')]) }}" @if ($isToday) aria-current="date" @endif
                            @class([
                                'flex h-16 flex-col items-center justify-center gap-0.5 rounded-2xl transition',
                                'bg-brand-purple-700 text-white shadow-[0_8px_18px_rgb(109_40_217/0.25)] dark:bg-brand-purple-600' => $isToday,
                                'text-slate-400 hover:bg-white dark:text-slate-500 dark:hover:bg-slate-900' => ! $isToday && $day['date']->isPast(),
                                'text-slate-900 hover:bg-white dark:text-slate-100 dark:hover:bg-slate-900' => ! $isToday && ! $day['date']->isPast(),
                            ])>
                            <span class="text-[11px] {{ $isToday ? 'text-white/80' : 'text-slate-500 dark:text-slate-400' }}">{{ $day['date']->translatedFormat('D') }}</span>
                            <span class="text-base font-semibold leading-none">{{ $day['date']->day }}</span>
                            <span @class([
                                'h-1 w-1 rounded-full',
                                'bg-white' => $isToday && $day['has_activity'],
                                'bg-brand-purple-600 dark:bg-brand-purple-400' => ! $isToday && $day['has_activity'],
                                'bg-transparent' => ! $day['has_activity'],
                            ])></span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Open for check-in right now --}}
            @forelse ($nowActivities as $now)
                @php
                    $activity = $now['activity'];
                    $span = max(1, $now['opens_at']->diffInMinutes($now['closes_at']));
                    $elapsedPct = min(100, max(0, round($now['opens_at']->diffInMinutes(now()) / $span * 100)));
                    $checkInUrl = $activity->usesSelfReportCheckIn() ? route('self-checkin.show', $activity) : route('checkin.show');
                @endphp
                <section aria-label="{{ __('เช็คชื่อได้ตอนนี้') }}" @class([
                    'group relative isolate overflow-hidden rounded-3xl p-5 text-white sm:p-6',
                    'bg-slate-900 shadow-soft-lg' => $activity->banner_url,
                    'bg-brand-purple-700 shadow-[0_16px_36px_rgb(109_40_217/0.22)] dark:bg-brand-purple-600/90' => ! $activity->banner_url,
                ])>
                    @if ($activity->banner_url)
                        {{-- The activity's cover photo is the card's background; the
                             scrim keeps white text readable on any photo. --}}
                        <img src="{{ asset('storage/'.$activity->banner_url) }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/85 via-black/55 to-black/30"></div>
                    @endif
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">
                            {{-- Green = "live / open now", the usual convention. --}}
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                            </span>
                            {{ __('เปิดเช็คชื่ออยู่') }}
                        </span>
                        <span class="text-xs text-white/85">{{ $remaining($now['closes_at']) }}</span>
                    </div>
                    {{-- The title link's ::after covers the whole card, so tapping anywhere opens the activity; the check-in button sits above it (z-10). --}}
                    <a href="{{ route('activities.show', $activity) }}" class="mt-4 block font-display text-xl leading-snug group-hover:underline after:absolute after:inset-0 after:rounded-3xl sm:text-2xl">{{ $activity->title }}</a>
                    <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-white/85">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $activity->start_at->format('H:i') }}–{{ $activity->end_at->format('H:i') }}
                        </span>
                        @if ($activity->location_name)
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                {{ $activity->location_name }}
                            </span>
                        @endif
                        <span>{{ __(':hours ชม.', ['hours' => $activity->credit_hours]) }}</span>
                    </p>
                    <div role="img" aria-label="{{ __('ผ่านไปแล้วร้อยละ :pct ของเวลาเปิดเช็คชื่อ', ['pct' => $elapsedPct]) }}" class="mt-4 h-1 overflow-hidden rounded-full bg-white/20">
                        <div class="h-full rounded-full bg-white" style="width: {{ $elapsedPct }}%"></div>
                    </div>
                    <a href="{{ $checkInUrl }}" class="relative z-10 mt-5 flex h-12 items-center justify-center gap-2 rounded-2xl bg-white text-[0.95rem] font-semibold text-brand-purple-800 transition hover:bg-brand-purple-50 active:scale-[0.99]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg>
                        {{ $activity->usesSelfReportCheckIn() ? __('ส่งหลักฐานเช็คชื่อ') : __('เช็คชื่อเลย') }}
                    </a>
                </section>
            @empty
                {{-- Hero priority when nothing is open for check-in right now:
                     up next → most recent missed → most recent attended → overview. --}}
                @if ($nextActivity)
                    @include('student.partials.hero-activity', [
                        'heroActivity' => $nextActivity,
                        'heroLabel' => __('กิจกรรมต่อไป'),
                        'heroNote' => $countdown($nextActivity->start_at),
                        'noteTone' => 'text-brand-purple-700 dark:text-brand-purple-300',
                        'heroAction' => null,
                    ])
                @elseif ($missedActivity)
                    @include('student.partials.hero-activity', [
                        'heroActivity' => $missedActivity,
                        'heroLabel' => __('พลาดกิจกรรมนี้'),
                        'heroNote' => $missedLateStatus === 'pending' ? __('ยื่นคำร้องแล้ว รอตรวจ') : __('จบไปเมื่อ :ago', ['ago' => $missedActivity->end_at->diffForHumans()]),
                        'noteTone' => $missedLateStatus === 'pending' ? 'text-amber-700 dark:text-amber-300' : 'text-rose-600 dark:text-rose-400',
                        'heroAction' => [
                            'url' => route('late-checkin.show', $missedActivity),
                            'label' => match ($missedLateStatus) { 'pending' => __('ดูคำร้อง'), 'rejected' => __('ยื่นคำร้องใหม่'), default => __('ขอเช็คชื่อย้อนหลัง') },
                            'primary' => $missedLateStatus !== 'pending',
                        ],
                    ])
                @elseif ($latestAttendance)
                    @php
                        $latestHours = $latestAttendance->credited_hours ?? $latestAttendance->activity->credit_hours;
                        [$latestNote, $latestTone] = match ($latestAttendance->status) {
                            'auto_approved' => [__('+:hours ชม.', ['hours' => $latestHours]), 'text-brand-green-700 dark:text-brand-green-400'],
                            'flagged' => [__('รอตรวจสอบ'), 'text-amber-700 dark:text-amber-300'],
                            default => [__('ไม่อนุมัติ'), 'text-rose-600 dark:text-rose-400'],
                        };
                    @endphp
                    @include('student.partials.hero-activity', [
                        'heroActivity' => $latestAttendance->activity,
                        'heroLabel' => __('เข้าร่วมล่าสุด'),
                        'heroNote' => $latestNote,
                        'noteTone' => $latestTone,
                        'heroAction' => null,
                    ])
                @else
                {{-- Nothing to check in to and nothing coming up: fill the hero
                     with where the student stands and what they can do next. --}}
                @php $hoursLeft = max(0, $summary['required_hours'] - $summary['total_hours']); @endphp
                <section aria-label="{{ __('ภาพรวม') }}" class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-display text-lg text-slate-900 dark:text-white">{{ __('ยังไม่มีกิจกรรมที่กำลังจะมาถึง') }}</p>
                            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ __('เมื่อมีกิจกรรมใหม่ จะแจ้งเตือนและขึ้นที่นี่') }}</p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('ชั่วโมงสะสม') }}</p>
                            <p class="text-sm">
                                <span class="font-display text-xl text-slate-900 dark:text-white">{{ $summary['total_hours'] }}</span>
                                <span class="text-slate-500 dark:text-slate-400">/ {{ $summary['required_hours'] }} {{ __('ชม.') }}</span>
                            </p>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div class="h-full rounded-full {{ $hoursLeft > 0 ? 'bg-brand-purple-600' : 'bg-brand-green-500' }}" style="width: {{ $hoursPct }}%"></div>
                        </div>
                        <p class="mt-2 text-xs {{ $hoursLeft > 0 ? 'text-slate-500 dark:text-slate-400' : 'font-semibold text-brand-green-700 dark:text-brand-green-400' }}">
                            {{ $hoursLeft > 0 ? __('ขาดอีก :hours ชั่วโมง ลองหาชั่วโมงเพิ่มจากช่องทางด้านล่าง', ['hours' => $hoursLeft]) : __('ชั่วโมงครบตามเกณฑ์แล้ว') }}
                        </p>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ([
                            [route('activities.index'), __('ดูกิจกรรมทั้งหมด'), 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                            [route('activities.calendar'), __('ปฏิทินกิจกรรม'), 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
                            [route('hour-requests.index', ['tab' => 'external']), __('ขอชั่วโมงกิจกรรมภายนอก'), 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253'],
                            [route('activities.index', ['status_group' => 'ended']), __('ขอเช็คชื่อย้อนหลัง'), 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ] as [$href, $label, $icon])
                            <a href="{{ $href }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-slate-200 px-2 py-3 text-center text-xs font-medium text-slate-700 transition-colors hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:text-slate-200 dark:hover:text-brand-purple-300">
                                <svg class="h-5 w-5 text-brand-purple-600 dark:text-brand-purple-300" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </section>
                @endif
            @endforelse

            {{-- Coming up --}}
            @if ($upcomingList->isNotEmpty())
            <section aria-label="{{ __('ถัดไป') }}">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('ถัดไป') }}</h2>
                    <a href="{{ route('activities.index') }}" class="text-sm font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ดูทั้งหมด') }}</a>
                </div>
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    @forelse ($upcomingList as $activity)
                        <a href="{{ route('activities.show', $activity) }}" class="flex items-center gap-4 px-4 py-3.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/60 {{ $loop->first ? '' : 'border-t border-slate-100 dark:border-slate-800' }}">
                            <span class="flex h-14 w-12 shrink-0 flex-col items-center justify-center rounded-2xl bg-slate-100 leading-tight dark:bg-slate-800">
                                <span class="text-lg font-semibold text-slate-900 dark:text-white">{{ $activity->start_at->day }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ $activity->start_at->translatedFormat('M') }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[0.95rem] font-medium text-slate-900 dark:text-white">{{ $activity->title }}</span>
                                <span class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                                    <span class="truncate">{{ $categoryMeta[$activity->activity_category]['label'] ?? '' }} · {{ $activity->start_at->format('H:i') }}{{ $activity->status === 'draft' ? ' · '.__('ยังไม่เปิด') : '' }}</span>
                                </span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </a>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('ยังไม่มีกิจกรรมที่กำลังจะมาถึง') }}</p>
                    @endforelse
                </div>
            </section>
            @endif

            {{-- History --}}
            <section aria-label="{{ __('ประวัติล่าสุด') }}" class="rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-end justify-between gap-3 px-5 pt-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('ประวัติล่าสุด') }}</h2>
                </div>
                <div role="tablist" class="mt-3 flex gap-5 border-b border-slate-100 px-5 text-sm dark:border-slate-800">
                    @foreach ([
                        'approved' => __('อนุมัติแล้ว'),
                        'pending' => __('รอตรวจสอบ').($pendingActivities->isNotEmpty() ? ' ('.$pendingActivities->count().($hasMorePending ? '+' : '').')' : ''),
                        'rejected' => __('ถูกปฏิเสธ'),
                    ] as $key => $label)
                        <button type="button" role="tab" @click="historyTab = '{{ $key }}'" :aria-selected="historyTab === '{{ $key }}'"
                            class="-mb-px border-b-2 pb-2.5 transition-colors"
                            :class="historyTab === '{{ $key }}' ? 'border-brand-purple-700 font-semibold text-brand-purple-700 dark:border-brand-purple-400 dark:text-brand-purple-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Approved --}}
                <div x-show="historyTab === 'approved'" class="p-2">
                    @forelse ($approvedActivities as $item)
                        @php $rowClass = 'flex w-full items-center justify-between gap-3 rounded-2xl px-3 py-3 text-left transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60'; @endphp
                        @if ($item->type === 'external')
                            <a href="{{ route('hour-requests.index', ['tab' => 'external']) }}" class="{{ $rowClass }}">
                        @elseif ($item->type === 'credit_transfer')
                            <a href="{{ route('hour-requests.index', ['tab' => 'credit']) }}" class="{{ $rowClass }}">
                        @elseif ($item->checkin_method === 'late_request')
                            <a href="{{ route('late-checkin.show', $item->activity_id) }}" class="{{ $rowClass }}">
                        @else
                            <button type="button" @click="detail = {{ Illuminate\Support\Js::from([
                                'title' => $item->title,
                                'date' => $item->date->translatedFormat('d M Y H:i'),
                                'hours' => $item->hours,
                                'location' => $item->location_name,
                                'photo' => $item->photo_url,
                            ]) }}; showDetail = true" class="{{ $rowClass }}">
                        @endif
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->title }}</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                    {{ $item->date->translatedFormat('d M Y') }}
                                    @if ($item->type === 'external') · {{ __('กิจกรรมเทียบชั่วโมง') }}
                                    @elseif ($item->type === 'credit_transfer') · {{ __('เทียบโอนตำแหน่ง') }}
                                    @elseif ($item->checkin_method === 'late_request') · {{ __('เช็คชื่อย้อนหลัง') }}
                                    @endif
                                </span>
                            </span>
                            <span class="shrink-0 rounded-full bg-brand-green-50 px-2.5 py-1 text-xs font-semibold text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-300">+{{ __(':hours ชม.', ['hours' => $item->hours]) }}</span>
                        @if ($item->type === 'external' || $item->type === 'credit_transfer' || $item->checkin_method === 'late_request')
                            </a>
                        @else
                            </button>
                        @endif
                    @empty
                        <p class="px-3 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('ยังไม่มีกิจกรรมที่ได้รับการอนุมัติ') }}</p>
                    @endforelse
                    @if ($hasMoreApproved)
                        <a href="{{ route('activity-history.index', ['status' => 'approved']) }}" class="block px-3 py-2.5 text-sm font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ดูทั้งหมด') }} →</a>
                    @endif
                </div>

                {{-- Pending --}}
                <div x-show="historyTab === 'pending'" x-cloak class="p-2">
                    @forelse ($pendingActivities as $item)
                        <div class="flex items-start justify-between gap-3 rounded-2xl px-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->title }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $item->date->translatedFormat('d M Y') }} · {{ __('รอเจ้าหน้าที่ตรวจสอบ') }}
                                    @if ($item->type === 'external') · {{ __('กิจกรรมเทียบชั่วโมง') }}
                                    @elseif ($item->type === 'credit_transfer') · {{ __('เทียบโอนตำแหน่ง') }}
                                    @endif
                                </p>
                                @if (! empty($item->flag_reason))
                                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">{{ __('เหตุผลที่ต้องตรวจสอบ:') }} {{ $item->flag_reason }}</p>
                                    <a href="{{ route('contact.create', ['subject' => __('สอบถามเรื่องการเช็คชื่อติดธงแดง: :title', ['title' => $item->title]), 'context_type' => $item->type, 'context_id' => $item->activity_id]) }}"
                                        class="mt-1 inline-flex text-xs font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ติดต่อเจ้าหน้าที่') }}</a>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ __(':hours ชม.', ['hours' => $item->hours]) }}</span>
                        </div>
                    @empty
                        <p class="px-3 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('ไม่มีรายการที่รอตรวจสอบ') }}</p>
                    @endforelse
                    @if ($hasMorePending)
                        <a href="{{ route('activity-history.index', ['status' => 'pending']) }}" class="block px-3 py-2.5 text-sm font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ดูทั้งหมด') }} →</a>
                    @endif
                </div>

                {{-- Rejected --}}
                <div x-show="historyTab === 'rejected'" x-cloak class="p-2">
                    @forelse ($rejectedActivities as $item)
                        @php
                            // A rejected external/credit request can be resubmitted and a
                            // rejected late check-in has its own page; a rejected
                            // realtime/self-report check-in has no follow-up, so it
                            // stays a plain row.
                            $rejectedHref = match (true) {
                                $item->type === 'external' => route('hour-requests.index', ['tab' => 'external']),
                                $item->type === 'credit_transfer' => route('hour-requests.index', ['tab' => 'credit']),
                                ($item->checkin_method ?? null) === 'late_request' => route('late-checkin.show', $item->activity_id),
                                default => null,
                            };
                        @endphp
                        <div class="rounded-2xl px-3 py-3 {{ $rejectedHref ? 'transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60' : '' }}">
                            @if ($rejectedHref)
                                <a href="{{ $rejectedHref }}" class="block truncate text-sm font-medium text-slate-900 hover:underline dark:text-slate-100">{{ $item->title }}</a>
                            @else
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->title }}</p>
                            @endif
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $item->date->translatedFormat('d M Y') }}
                                @if ($item->type === 'external') · {{ __('กิจกรรมเทียบชั่วโมง') }}
                                @elseif ($item->type === 'credit_transfer') · {{ __('เทียบโอนตำแหน่ง') }}
                                @elseif (($item->checkin_method ?? null) === 'late_request') · {{ __('เช็คชื่อย้อนหลัง') }}
                                @endif
                            </p>
                            @if ($item->reject_reason)
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ __('เหตุผล:') }} {{ $item->reject_reason }}</p>
                                <a href="{{ route('contact.create', ['subject' => __('สอบถามเรื่องคำร้องที่ถูกปฏิเสธ: :title', ['title' => $item->title]), 'context_type' => $item->type, 'context_id' => $item->activity_id ?? null]) }}"
                                    class="mt-1 inline-flex text-xs font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ติดต่อเจ้าหน้าที่') }}</a>
                            @endif
                        </div>
                    @empty
                        <p class="px-3 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('ไม่มีกิจกรรมที่ถูกปฏิเสธ') }}</p>
                    @endforelse
                    @if ($hasMoreRejected)
                        <a href="{{ route('activity-history.index', ['status' => 'rejected']) }}" class="block px-3 py-2.5 text-sm font-medium text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ดูทั้งหมด') }} →</a>
                    @endif
                </div>
            </section>
        </div>

        {{-- Side column --}}
        <aside class="flex min-w-0 flex-col gap-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ชั่วโมงสะสม') }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><span class="font-display text-2xl text-slate-900 dark:text-white">{{ $summary['total_hours'] }}</span> / {{ $summary['required_hours'] }}</p>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-brand-purple-50 dark:bg-brand-purple-500/15" role="img" aria-label="{{ __('ร้อยละ :pct', ['pct' => $hoursPct]) }}">
                        <div class="h-full rounded-full bg-brand-purple-700 dark:bg-brand-purple-400" style="width: {{ $hoursPct }}%"></div>
                    </div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('กิจกรรม') }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><span class="font-display text-2xl text-slate-900 dark:text-white">{{ $summary['total_activities'] }}</span> / {{ $summary['required_activities'] }}</p>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-brand-purple-50 dark:bg-brand-purple-500/15" role="img" aria-label="{{ __('ร้อยละ :pct', ['pct' => $activitiesPct]) }}">
                        <div class="h-full rounded-full bg-brand-purple-700 dark:bg-brand-purple-400" style="width: {{ $activitiesPct }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Clearance status: icon + label carry the meaning, not color alone --}}
            <div @class([
                'rounded-3xl border p-4',
                'border-brand-green-100 bg-brand-green-50 dark:border-brand-green-500/20 dark:bg-brand-green-500/10' => $summary['is_cleared'],
                'border-amber-200 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10' => ! $summary['is_cleared'],
            ])>
                <div class="flex items-start gap-3">
                    <span @class([
                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white',
                        'bg-brand-green-600' => $summary['is_cleared'],
                        'bg-amber-500' => ! $summary['is_cleared'],
                    ])>
                        @if ($summary['is_cleared'])
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        @if ($summary['is_cleared'])
                            <p class="text-sm font-semibold text-brand-green-800 dark:text-brand-green-300">{{ __('ผ่านเกณฑ์รับใบรับรองกิจกรรมแล้ว') }}</p>
                            <p class="text-xs text-brand-green-700 dark:text-brand-green-300">{{ __('สะสมครบ :activities กิจกรรม / :hours ชั่วโมง', ['activities' => $summary['total_activities'], 'hours' => $summary['total_hours']]) }}</p>
                        @else
                            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('ยังไม่ผ่านเกณฑ์') }}</p>
                            <p class="text-xs text-amber-700 dark:text-amber-300">{{ __('ขาดอีก :activities กิจกรรม และ :hours ชั่วโมง', [
                                'activities' => max(0, $summary['required_activities'] - $summary['total_activities']),
                                'hours' => max(0, $summary['required_hours'] - $summary['total_hours']),
                            ]) }}</p>
                        @endif
                        @if ($summary['yearly_target_hours'])
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('เป้าหมายชั่วโมงกิจกรรมของชั้นปีที่ :year คือ :hours ชั่วโมง/ปี', ['year' => $summary['current_year'], 'hours' => $summary['yearly_target_hours']]) }}</p>
                        @endif
                    </div>
                </div>
                <a href="{{ route('transcript.download') }}" class="mt-3 flex h-10 items-center justify-center gap-1.5 rounded-xl bg-white text-sm font-semibold text-slate-800 ring-1 ring-slate-200 transition hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700 dark:hover:bg-slate-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    {{ __('ใบสรุปกิจกรรม (PDF)') }}
                </a>
            </div>

            {{-- Quick requests --}}
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('hour-requests.index', ['tab' => 'external']) }}" class="flex flex-col gap-2 rounded-3xl border border-slate-200 bg-white p-4 transition hover:border-brand-purple-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-purple-500/40">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-300"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg></span>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('ยื่นกิจกรรมภายนอก') }}</span>
                </a>
                <a href="{{ route('hour-requests.index', ['tab' => 'credit']) }}" class="flex flex-col gap-2 rounded-3xl border border-slate-200 bg-white p-4 transition hover:border-brand-purple-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-purple-500/40">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg></span>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('เทียบโอนชั่วโมง') }}</span>
                </a>
            </div>

            {{-- Category breakdown (5 ด้าน) --}}
            <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('ชั่วโมงตามหมวด (5 ด้าน)') }}</h2>
                <div class="mt-3 space-y-3">
                    @foreach ($categoryMeta as $key => $meta)
                        @php
                            $hours = $summary['category_hours'][$key] ?? 0;
                            $pct = min(100, $summary['required_hours'] > 0 ? round($hours / $summary['required_hours'] * 100) : 0);
                        @endphp
                        <div>
                            <div class="mb-1 flex items-baseline justify-between text-xs">
                                <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300"><span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>{{ $meta['label'] }}</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ $hours }} {{ __('ชม.') }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full {{ $meta['dot'] }}" style="width: {{ $pct }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Where the hours came from --}}
            <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('ที่มาของชั่วโมงสะสม') }}</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($sourceMeta as $key => $meta)
                        @php $hours = $summary['hours_by_source'][$key] ?? 0; @endphp
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300"><span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>{{ $meta['label'] }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ $hours }} {{ __('ชม.') }}{{ $sourceTotal > 0 ? ' · '.round($hours / $sourceTotal * 100).'%' : '' }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>

    {{-- Check-in detail popup (realtime/self-report only — external and late-request rows link out instead) --}}
    <template x-teleport="body">
        <div x-show="showDetail" x-cloak @keydown.escape.window="showDetail = false" x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
            <div @click.outside="showDetail = false" x-show="detail" class="w-full max-w-sm rounded-3xl bg-white p-5 shadow-soft-lg dark:bg-slate-900">
                <template x-if="detail">
                    <div>
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <p class="font-semibold leading-snug text-slate-900 dark:text-white" x-text="detail.title"></p>
                            <button type="button" @click="showDetail = false" aria-label="{{ __('ปิด') }}" class="shrink-0 rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mb-3 overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
                            <img :src="detail.photo" alt="" class="max-h-72 w-full object-contain">
                        </div>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500 dark:text-slate-400">{{ __('เวลาเช็คชื่อ') }}</dt><dd class="font-medium" x-text="detail.date"></dd></div>
                            <div class="flex justify-between gap-3" x-show="detail.location"><dt class="text-slate-500 dark:text-slate-400">{{ __('สถานที่') }}</dt><dd class="font-medium" x-text="detail.location"></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500 dark:text-slate-400">{{ __('ชั่วโมงที่ได้รับ') }}</dt><dd class="font-semibold text-brand-green-700 dark:text-brand-green-300" x-text="detail.hours + ' {{ __('ชม.') }}'"></dd></div>
                        </dl>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
@endsection
