@props([
    'month',
    'weeks',
    // Route name for the month navigation (prev/next/today).
    'route',
    // fn (Activity $activity): string — where clicking an activity goes.
    'linkTo',
    'checkedInIds' => [],
    // Admin view: also reflect status (drafts faded, cancelled struck through).
    'showStatus' => false,
])

@php
    $categoryMeta = [
        'culture' => ['label' => __('ทำนุบำรุงศิลปวัฒนธรรม'), 'dot' => 'bg-sky-400'],
        'academic' => ['label' => __('วิชาการ'), 'dot' => 'bg-brand-green-500'],
        'sports' => ['label' => __('กีฬาและส่งเสริมสุขภาพ'), 'dot' => 'bg-amber-400'],
        'volunteer' => ['label' => __('จิตอาสา/บำเพ็ญประโยชน์'), 'dot' => 'bg-brand-purple-500'],
        'ethics' => ['label' => __('คุณธรรมจริยธรรม'), 'dot' => 'bg-fuchsia-400'],
    ];
    $statusLabel = [
        'draft' => __('ร่าง'), 'open' => __('เปิดลงทะเบียน'),
        'ongoing' => __('กำลังจัดอยู่'), 'closed' => __('ปิดกิจกรรม'), 'cancelled' => __('ถูกยกเลิก'),
    ];

    // Thai readers expect the Buddhist-era year.
    $yearOf = fn ($date) => app()->getLocale() === 'th' ? $date->year + 543 : $date->year;

    $days = collect($weeks)->flatten(1);
    $isCurrentMonth = $month->isSameMonth(now());

    // Day the list under the grid opens on: today when it's in view,
    // otherwise the month's first day that has anything on it.
    $initialDay = $isCurrentMonth
        ? now()->toDateString()
        : ($days->first(fn ($d) => $d['inMonth'] && $d['activities']->isNotEmpty())['key'] ?? $month->toDateString());

    $statusTone = fn ($activity) => $showStatus
        ? ($activity->status === 'cancelled' ? 'line-through opacity-50' : ($activity->status === 'draft' ? 'opacity-60' : ''))
        : '';
    $navBtn = 'flex h-9 items-center justify-center text-slate-600 transition hover:bg-white hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white';
@endphp

<div x-data="{ selected: @js($initialDay) }">
    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-2xl text-slate-900 dark:text-white">
            {{ $month->translatedFormat('F') }} <span class="text-slate-400 dark:text-slate-500">{{ $yearOf($month) }}</span>
        </h2>
        <div class="inline-flex items-center rounded-full bg-slate-100 p-1 dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
            <a href="{{ route($route, ['month' => $month->subMonth()->format('Y-m')]) }}" aria-label="{{ __('เดือนก่อนหน้า') }}" class="{{ $navBtn }} w-9 rounded-full">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <a href="{{ route($route) }}" @if ($isCurrentMonth) aria-current="date" @endif
                class="{{ $navBtn }} rounded-full px-4 text-sm {{ $isCurrentMonth ? 'bg-white font-semibold text-brand-purple-700 shadow-soft dark:bg-slate-700 dark:text-white' : '' }}">
                {{ __('วันนี้') }}
            </a>
            <a href="{{ route($route, ['month' => $month->addMonth()->format('Y-m')]) }}" aria-label="{{ __('เดือนถัดไป') }}" class="{{ $navBtn }} w-9 rounded-full">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($categoryMeta as $meta)
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                <span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>{{ $meta['label'] }}
            </span>
        @endforeach
    </div>

    {{-- Month grid: event rows on wide screens, dots on phones (tap a day to list it below). --}}
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="grid grid-cols-7 border-b border-slate-100 dark:border-slate-800">
            @foreach ($weeks[0] as $day)
                <div class="py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400">{{ $day['date']->translatedFormat('D') }}</div>
            @endforeach
        </div>

        @foreach ($weeks as $week)
            <div class="grid grid-cols-7 divide-x divide-slate-100 border-b border-slate-100 last:border-b-0 dark:divide-slate-800 dark:border-slate-800">
                @foreach ($week as $day)
                    <div
                        @click="selected = @js($day['key'])"
                        class="group relative min-h-[3.75rem] cursor-pointer p-1.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:min-h-[7.25rem] sm:p-2"
                        :class="selected === @js($day['key']) ? 'bg-brand-purple-50/70 dark:bg-brand-purple-500/10' : ''">
                        <div class="flex justify-center sm:justify-start">
                            <span @class([
                                'flex h-7 w-7 items-center justify-center rounded-full text-[13px] tabular-nums',
                                'bg-brand-purple-700 font-semibold text-white dark:bg-brand-purple-600' => $day['isToday'],
                                'font-medium text-slate-800 dark:text-slate-100' => ! $day['isToday'] && $day['inMonth'],
                                'text-slate-300 dark:text-slate-600' => ! $day['isToday'] && ! $day['inMonth'],
                            ])>{{ $day['date']->day }}</span>
                        </div>

                        {{-- Phone: up to 3 category dots --}}
                        @if ($day['activities']->isNotEmpty())
                            <div class="mt-1 flex justify-center gap-1 sm:hidden">
                                @foreach ($day['activities']->take(3) as $activity)
                                    <span class="h-1.5 w-1.5 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Wider screens: up to 3 event rows, then "+N" --}}
                        <div class="mt-1 hidden space-y-0.5 sm:block">
                            @foreach ($day['activities']->take(3) as $activity)
                                <a href="{{ $linkTo($activity) }}" @click.stop title="{{ $activity->title }}"
                                    class="flex items-center gap-1.5 rounded-md px-1.5 py-1 text-[12px] leading-tight text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 {{ $statusTone($activity) }}">
                                    @if (in_array($activity->id, $checkedInIds, true))
                                        <svg class="h-3 w-3 shrink-0 text-brand-green-600 dark:text-brand-green-400" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-label="{{ __('เช็คชื่อแล้ว') }}"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    @else
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
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
        <section x-show="selected === @js($day['key'])" x-cloak class="mt-6" aria-live="polite">
            <h3 class="mb-3 flex items-baseline gap-2">
                <span class="font-display text-lg text-slate-900 dark:text-white">{{ $day['date']->translatedFormat('l j F') }} {{ $yearOf($day['date']) }}</span>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __(':count กิจกรรม', ['count' => $day['activities']->count()]) }}</span>
            </h3>

            <div class="space-y-2">
                @forelse ($day['activities'] as $activity)
                    <a href="{{ $linkTo($activity) }}"
                        class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-brand-purple-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-purple-500/40">
                        <span class="w-14 shrink-0 text-center">
                            <span class="block font-display text-base tabular-nums text-slate-900 dark:text-white">{{ $activity->start_at->isSameDay($day['date']) ? $activity->start_at->format('H:i') : __('ต่อเนื่อง') }}</span>
                            <span class="mx-auto mt-1 block h-1.5 w-1.5 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span @class(['block truncate font-medium text-slate-900 dark:text-white', 'line-through' => $showStatus && $activity->status === 'cancelled'])>{{ $activity->title }}</span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">
                                @if ($activity->start_at->isSameDay($activity->end_at))
                                    {{ $activity->start_at->format('H:i') }}–{{ $activity->end_at->format('H:i') }} {{ __('น.') }}
                                @else
                                    {{ $activity->start_at->translatedFormat('j M H:i') }} – {{ $activity->end_at->translatedFormat('j M H:i') }}
                                @endif
                                @if ($activity->location_name) · {{ $activity->location_name }} @endif
                            </span>
                        </span>
                        <span class="flex shrink-0 flex-col items-end gap-1 sm:flex-row sm:items-center sm:gap-2">
                            @if ($showStatus)
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $statusLabel[$activity->displayStatus()] ?? $activity->status }}</span>
                            @endif
                            @if (in_array($activity->id, $checkedInIds, true))
                                <span class="inline-flex items-center gap-1 rounded-full bg-brand-green-50 px-2.5 py-1 text-[11px] font-medium text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-300">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    {{ __('เช็คชื่อแล้ว') }}
                                </span>
                            @endif
                            <svg class="hidden h-4 w-4 text-slate-400 sm:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </span>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">{{ __('ไม่มีกิจกรรมในวันนี้') }}</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
