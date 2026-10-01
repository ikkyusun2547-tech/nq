@props([
    'month',
    'weeks',
    // Route name for the month navigation (prev/next/today).
    'route',
    // fn (Activity $activity): string — where clicking an activity goes.
    'linkTo',
    'checkedInIds' => [],
    // Admin view: tint chips by status too (drafts faded, cancelled struck through).
    'showStatus' => false,
])

@php
    $categoryMeta = [
        'culture' => ['label' => __('ทำนุบำรุงศิลปวัฒนธรรม'), 'dot' => 'bg-sky-400', 'chip' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300'],
        'academic' => ['label' => __('วิชาการ'), 'dot' => 'bg-brand-green-500', 'chip' => 'bg-brand-green-100 text-brand-green-800 dark:bg-brand-green-500/15 dark:text-brand-green-300'],
        'sports' => ['label' => __('กีฬาและส่งเสริมสุขภาพ'), 'dot' => 'bg-amber-400', 'chip' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'],
        'volunteer' => ['label' => __('จิตอาสา/บำเพ็ญประโยชน์'), 'dot' => 'bg-brand-purple-500', 'chip' => 'bg-brand-purple-100 text-brand-purple-800 dark:bg-brand-purple-500/20 dark:text-brand-purple-300'],
        'ethics' => ['label' => __('คุณธรรมจริยธรรม'), 'dot' => 'bg-fuchsia-400', 'chip' => 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-500/15 dark:text-fuchsia-300'],
    ];
    $statusLabel = [
        'draft' => __('ร่าง'), 'open' => __('เปิดรับสมัคร'), 'full' => __('เต็มแล้ว'),
        'ongoing' => __('กำลังดำเนินการ'), 'closed' => __('ปิดกิจกรรม'), 'cancelled' => __('ถูกยกเลิก'),
    ];

    // Thai readers expect the Buddhist-era year.
    $yearLabel = app()->getLocale() === 'th' ? $month->year + 543 : $month->year;
    $monthTitle = $month->translatedFormat('F').' '.$yearLabel;

    $days = collect($weeks)->flatten(1);
    $isCurrentMonth = $month->isSameMonth(now());

    // Day the list under the grid opens on: today when it's in view,
    // otherwise the month's first day that has anything on it.
    $initialDay = $isCurrentMonth
        ? now()->toDateString()
        : ($days->first(fn ($d) => $d['inMonth'] && $d['activities']->isNotEmpty())['key'] ?? $month->toDateString());

    $chipClass = function ($activity) use ($categoryMeta, $showStatus) {
        return trim(($categoryMeta[$activity->activity_category]['chip'] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300')
            .($showStatus && $activity->status === 'draft' ? ' opacity-60' : '')
            .($showStatus && $activity->status === 'cancelled' ? ' line-through opacity-50' : ''));
    };
@endphp

<div x-data="{ selected: @js($initialDay) }">
    {{-- Month navigation --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route($route, ['month' => $month->subMonth()->format('Y-m')]) }}"
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-500 shadow-soft ring-1 ring-slate-200 transition hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-brand-purple-400"
                aria-label="{{ __('เดือนก่อนหน้า') }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <h2 class="min-w-[9.5rem] text-center text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $monthTitle }}</h2>
            <a href="{{ route($route, ['month' => $month->addMonth()->format('Y-m')]) }}"
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-500 shadow-soft ring-1 ring-slate-200 transition hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-brand-purple-400"
                aria-label="{{ __('เดือนถัดไป') }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
            @unless ($isCurrentMonth)
                <a href="{{ route($route) }}"
                    class="rounded-xl bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-soft ring-1 ring-slate-200 transition hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:text-brand-purple-400">
                    {{ __('วันนี้') }}
                </a>
            @endunless
        </div>

        <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
            @foreach ($categoryMeta as $meta)
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>{{ $meta['label'] }}</span>
            @endforeach
        </div>
    </div>

    {{-- Month grid: chips on wide screens, dots on phones (tap a day to list it below). --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
        <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50 text-center text-xs font-medium text-slate-500 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
            @foreach ($weeks[0] as $day)
                <div class="py-2">{{ $day['date']->translatedFormat('D') }}</div>
            @endforeach
        </div>

        @foreach ($weeks as $week)
            <div class="grid grid-cols-7 border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                @foreach ($week as $day)
                    <div
                        @click="selected = @js($day['key'])"
                        :class="selected === @js($day['key']) ? 'bg-brand-purple-50/70 dark:bg-brand-purple-500/10' : ''"
                        @class([
                            'min-h-[3.5rem] cursor-pointer border-r border-slate-100 p-1 transition-colors last:border-r-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/40 sm:min-h-[7rem] sm:p-1.5',
                            'bg-slate-50/60 dark:bg-slate-950/30' => ! $day['inMonth'],
                        ])>
                        <div class="flex justify-center sm:justify-start">
                            <span @class([
                                'flex h-6 w-6 items-center justify-center rounded-full text-xs tabular-nums',
                                'bg-brand-purple-600 font-semibold text-white' => $day['isToday'],
                                'text-slate-700 dark:text-slate-200' => ! $day['isToday'] && $day['inMonth'],
                                'text-slate-300 dark:text-slate-600' => ! $day['isToday'] && ! $day['inMonth'],
                            ])>{{ $day['date']->day }}</span>
                        </div>

                        {{-- Phone: up to 3 category dots --}}
                        @if ($day['activities']->isNotEmpty())
                            <div class="mt-1 flex justify-center gap-0.5 sm:hidden">
                                @foreach ($day['activities']->take(3) as $activity)
                                    <span class="h-1.5 w-1.5 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Wider screens: up to 3 chips, then "+N" --}}
                        <div class="mt-1 hidden space-y-1 sm:block">
                            @foreach ($day['activities']->take(3) as $activity)
                                <a href="{{ $linkTo($activity) }}" @click.stop
                                    title="{{ $activity->title }}"
                                    class="flex items-center gap-1 truncate rounded-md px-1.5 py-0.5 text-[11px] font-medium leading-tight transition hover:brightness-95 {{ $chipClass($activity) }}">
                                    @if (in_array($activity->id, $checkedInIds, true))
                                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    @endif
                                    <span class="truncate">{{ $activity->title }}</span>
                                </a>
                            @endforeach
                            @if ($day['activities']->count() > 3)
                                <p class="px-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ __('+:count รายการ', ['count' => $day['activities']->count() - 3]) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Selected day's full list --}}
    @foreach ($days as $day)
        <div x-show="selected === @js($day['key'])" x-cloak class="mt-5">
            <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ $day['date']->translatedFormat('l j F') }} {{ app()->getLocale() === 'th' ? $day['date']->year + 543 : $day['date']->year }}
            </h3>

            @forelse ($day['activities'] as $activity)
                <a href="{{ $linkTo($activity) }}"
                    class="mb-2 flex items-stretch overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-slate-700">
                    <span class="w-1.5 shrink-0 {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                    <span class="flex-1 px-4 py-3">
                        <span @class(['block font-medium text-slate-900 dark:text-slate-100', 'line-through' => $showStatus && $activity->status === 'cancelled'])>{{ $activity->title }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">
                            @if ($activity->start_at->isSameDay($activity->end_at))
                                {{ $activity->start_at->format('H:i') }}–{{ $activity->end_at->format('H:i') }} {{ __('น.') }}
                            @else
                                {{ $activity->start_at->translatedFormat('j M H:i') }} – {{ $activity->end_at->translatedFormat('j M H:i') }}
                            @endif
                            @if ($activity->location_name) · {{ $activity->location_name }} @endif
                        </span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2 pr-4">
                        @if ($showStatus)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $statusLabel[$activity->status] ?? $activity->status }}</span>
                        @endif
                        @if (in_array($activity->id, $checkedInIds, true))
                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-green-50 px-2 py-0.5 text-[11px] font-medium text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                {{ __('เช็คชื่อแล้ว') }}
                            </span>
                        @endif
                    </span>
                </a>
            @empty
                <p class="rounded-2xl bg-white px-4 py-6 text-center text-sm text-slate-400 shadow-soft ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-500 dark:ring-slate-700">{{ __('ไม่มีกิจกรรมในวันนี้') }}</p>
            @endforelse
        </div>
    @endforeach
</div>
