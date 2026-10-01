@extends('layouts.dashboard')

@section('content')
@php
    $categoryMeta = [
        'culture' => ['label' => __('ทำนุบำรุงศิลปวัฒนธรรม'), 'dot' => 'bg-sky-400'],
        'academic' => ['label' => __('วิชาการ'), 'dot' => 'bg-brand-green-500'],
        'sports' => ['label' => __('กีฬาและส่งเสริมสุขภาพ'), 'dot' => 'bg-amber-400'],
        'volunteer' => ['label' => __('จิตอาสา/บำเพ็ญประโยชน์'), 'dot' => 'bg-brand-purple-500'],
        'ethics' => ['label' => __('คุณธรรมจริยธรรม'), 'dot' => 'bg-fuchsia-400'],
    ];
    $statusBadge = [
        'open' => ['label' => __('เปิดรับสมัคร'), 'class' => 'bg-brand-green-500/90 text-white'],
        'ongoing' => ['label' => __('กำลังดำเนินการ'), 'class' => 'bg-brand-purple-500/90 text-white'],
        'full' => ['label' => __('เต็มแล้ว'), 'class' => 'bg-amber-500/90 text-white'],
        'draft' => ['label' => __('ยังไม่เปิด'), 'class' => 'bg-slate-500/90 text-white'],
        'closed' => ['label' => __('จบไปแล้ว'), 'class' => 'bg-slate-500/90 text-white'],
    ];
    $levelLabel = ['university' => __('ระดับมหาวิทยาลัย'), 'faculty' => __('ระดับคณะ')];
    $checkinMethodMeta = [
        'realtime' => ['label' => __('สแกน QR + GPS + เซลฟี')],
        'self_report' => ['label' => __('แนบรูปหลักฐาน (รายงานตนเอง)')],
    ];
    $statusGroupTabs = [
        'open' => __('เปิดรับ'),
        'upcoming' => __('ยังไม่เปิด'),
        'ended' => __('จบไปแล้ว'),
    ];
    $pageTitle = [
        'open' => __('กิจกรรมที่เปิดรับ'),
        'upcoming' => __('กิจกรรมที่ยังไม่เปิด'),
        'ended' => __('กิจกรรมที่จบไปแล้ว'),
    ][$statusGroup];

    // Drives the mobile filter-sheet trigger's badge — mirrors the mobile
    // app's activities screen (see mobile/lib/features/activities/activities_screen.dart),
    // which counts level+category the same way.
    $activeFilterCount = collect([
        request()->filled('activity_category'),
        request()->filled('activity_level'),
        request()->filled('faculty_id'),
        request()->filled('academic_year'),
    ])->filter()->count();
@endphp

<div class="mx-auto max-w-6xl">
    <x-brand-header eyebrow="{{ __('กองพัฒนานักศึกษา') }}" :title="$pageTitle">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @include('partials.activity-view-toggle', ['listRoute' => 'activities.index', 'calendarRoute' => 'activities.calendar', 'active' => 'list'])
                <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
                    {{ __(':count กิจกรรม', ['count' => $activities->total()]) }}
                </span>
            </div>
        </x-slot:actions>
    </x-brand-header>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach ($statusGroupTabs as $value => $label)
            <a href="{{ route('activities.index', array_merge(request()->only(['activity_level', 'activity_category', 'search', 'academic_year', 'faculty_id']), ['status_group' => $value])) }}"
                @class([
                    'rounded-full px-3.5 py-1.5 font-medium transition-all duration-200',
                    'bg-brand-purple-600 text-white shadow-soft' => $statusGroup === $value,
                    'bg-white text-slate-500 shadow-soft ring-1 ring-slate-200 hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-brand-purple-400' => $statusGroup !== $value,
                ])>
                {{ $label }}
            </a>
        @endforeach
    </div>

    @php
        $facultyOptions = $faculties->pluck('name_th', 'id')->all();
        $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => __('ปีการศึกษา :year', ['year' => $y])])->all();
        $categoryOptions = collect($categoryMeta)->map(fn ($meta) => $meta['label'])->all();
    @endphp

    {{--
        The mobile sheet and the desktop grid below both render the same
        four fields (search + 3 selects) so each layout can be styled
        independently, but that means two real, same-named form controls
        exist in the DOM at once — a plain GET submit would send both
        values and let whichever sits last in the DOM silently clobber the
        other. `isDesktop` (tracked via matchMedia, not just CSS display)
        disables whichever copy isn't the one actually visible/usable at
        the current viewport, so exactly one of each field is ever
        submitted.
    --}}
    <form method="GET" action="{{ route('activities.index') }}" class="mb-5"
        x-data="{
            filtersOpen: false,
            isDesktop: window.matchMedia('(min-width: 640px)').matches,
            init() {
                const mq = window.matchMedia('(min-width: 640px)');
                mq.addEventListener('change', (e) => { this.isDesktop = e.matches; });
            },
        }"
    >
        <input type="hidden" name="status_group" value="{{ $statusGroup }}">

        {{-- Mobile: search + a compact filter-sheet trigger on one row,
             matching the app's activities screen (search + tune-icon button
             that opens a bottom sheet) instead of stacking four full-width
             dropdowns before any activity is even visible. --}}
        <div class="flex gap-2 sm:hidden">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input
                    type="search" name="search" value="{{ request('search') }}" x-bind:disabled="isDesktop"
                    placeholder="{{ __('ค้นหากิจกรรม') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm shadow-soft transition-all duration-200 placeholder:text-slate-400 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>
            <button type="button" @click="filtersOpen = true" aria-label="{{ __('ตัวกรอง') }}"
                @class([
                    // Matches the search input's actual rendered height —
                    // py-2.5 + text-sm + a 1px border computes to 42px, not
                    // the 46px this button previously guessed, which is why
                    // it stood visibly taller than the input next to it.
                    'relative flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl shadow-soft transition-colors duration-200',
                    'bg-brand-purple-600 text-white' => $activeFilterCount > 0,
                    'border border-slate-200 bg-white text-slate-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400' => $activeFilterCount === 0,
                ])
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m9 12h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9-12H3.75m9 12H3.75m9-12H9m6 12v.007M12 6.75a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-6 6a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm0 0H3.75m3 0H12"/></svg>
                @if ($activeFilterCount > 0)
                    <span class="absolute -right-1.5 -top-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-brand-green-500 text-[0.65rem] font-bold text-brand-purple-950">{{ $activeFilterCount }}</span>
                @endif
            </button>
        </div>

        {{-- Desktop/tablet: unchanged from before — full search bar with a
             visible 2/4-column select grid, since there's room for it. --}}
        <div class="hidden sm:block sm:space-y-3">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input
                    type="search" name="search" value="{{ request('search') }}" x-bind:disabled="! isDesktop"
                    placeholder="{{ __('ค้นหากิจกรรม (ชื่อหรือหน่วยงานจัด)') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm shadow-soft transition-all duration-200 placeholder:text-slate-400 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-premium-select
                    name="activity_category" :options="$categoryOptions" :selected="request('activity_category')"
                    placeholder="{{ __('-- ทุกหมวดหมู่ --') }}" autosubmit x-bind:disabled="! isDesktop"
                />

                <x-premium-select
                    name="activity_level" :options="$levelLabel" :selected="request('activity_level')"
                    placeholder="{{ __('-- ทุกระดับ (รวม) --') }}" autosubmit x-bind:disabled="! isDesktop"
                />

                <x-premium-select
                    name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
                    placeholder="{{ __('-- ทุกคณะ (แยกดูได้) --') }}" autosubmit x-bind:disabled="! isDesktop"
                />

                <x-premium-select
                    name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
                    placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit x-bind:disabled="! isDesktop"
                />
            </div>
        </div>

        {{-- Mobile filter sheet — same 4 fields as the desktop grid above,
             just stacked in a bottom sheet instead of the page body, so
             picking one still auto-submits the same GET form (a fresh page
             load closes the sheet naturally, same as every other
             autosubmit filter in this app — no separate JS state to keep
             in sync). --}}
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
                        <a href="{{ route('activities.index', array_merge(request()->only(['search']), ['status_group' => $statusGroup])) }}"
                            class="text-xs font-medium text-brand-purple-600 dark:text-brand-purple-400">
                            {{ __('ล้างตัวกรอง') }}
                        </a>
                    @endif
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('หมวดหมู่') }}</label>
                        <x-premium-select
                            name="activity_category" :options="$categoryOptions" :selected="request('activity_category')"
                            placeholder="{{ __('-- ทุกหมวดหมู่ --') }}" autosubmit x-bind:disabled="isDesktop"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ระดับ') }}</label>
                        <x-premium-select
                            name="activity_level" :options="$levelLabel" :selected="request('activity_level')"
                            placeholder="{{ __('-- ทุกระดับ (รวม) --') }}" autosubmit x-bind:disabled="isDesktop"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ') }}</label>
                        <x-premium-select
                            name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
                            placeholder="{{ __('-- ทุกคณะ (แยกดูได้) --') }}" autosubmit x-bind:disabled="isDesktop"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ปีการศึกษา') }}</label>
                        <x-premium-select
                            name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
                            placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit x-bind:disabled="isDesktop"
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

    @if ($activities->isEmpty())
        <div class="rounded-2xl glass-card p-10 text-center text-slate-400 shadow-soft dark:text-slate-500">
            {{ __('ไม่พบกิจกรรมที่ตรงกับเงื่อนไข') }}
        </div>
    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($activities as $activity)
                <div class="flex h-full flex-col overflow-hidden rounded-2xl glass-card shadow-soft transition-transform duration-200 hover:-translate-y-1">
                    <div class="relative aspect-[16/9] w-full overflow-hidden bg-gradient-to-br from-brand-purple-600 to-brand-purple-900">
                        @if ($activity->banner_url)
                            <img src="{{ asset('storage/'.$activity->banner_url) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <svg class="h-10 w-10 text-white/30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 5.25h18M3 5.25v13.5A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V5.25M3 5.25A2.25 2.25 0 015.25 3h13.5A2.25 2.25 0 0121 5.25"/></svg>
                            </div>
                        @endif

                        <span class="absolute right-3 top-3 rounded-full px-2.5 py-1 text-xs font-medium shadow-soft backdrop-blur {{ $statusBadge[$activity->status]['class'] ?? 'bg-slate-500/90 text-white' }}">
                            {{ $statusBadge[$activity->status]['label'] ?? $activity->status }}
                        </span>

                        @if ($checkedInActivityIds->contains($activity->id))
                            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-xs font-medium text-brand-green-700 shadow-soft dark:bg-slate-900/90 dark:text-brand-green-400">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ __('เช็คชื่อแล้ว') }}
                            </span>
                        @elseif ($activity->status === 'closed')
                            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-red-500/95 px-2.5 py-1 text-xs font-medium text-white shadow-soft backdrop-blur">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                {{ __('พลาดกิจกรรมนี้') }}
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-1">
                        <div class="w-[5px] shrink-0 {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></div>
                    <div class="flex flex-1 flex-col p-4">
                        <span class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                            <span class="h-1.5 w-1.5 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                            {{ $categoryMeta[$activity->activity_category]['label'] ?? $activity->activity_category }}
                        </span>

                        @if ($activity->activity_code)
                            <p class="mb-0.5 font-mono text-[0.68rem] text-slate-400 dark:text-slate-500">{{ $activity->activity_code }}</p>
                        @endif
                        <h2 class="line-clamp-2 font-semibold text-slate-900 dark:text-slate-100" style="text-wrap: balance;">{{ $activity->title }}</h2>

                        <div class="mt-2.5 flex-1 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                            <p class="flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                {{ $activity->start_at->translatedFormat('d M Y H:i') }}
                            </p>
                            @if ($activity->location_name)
                                <p class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    <span class="truncate">{{ $activity->location_name }}</span>
                                </p>
                            @endif
                            <p class="flex items-center gap-1.5">
                                @if ($activity->usesSelfReportCheckIn())
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                                @else
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg>
                                @endif
                                {{ $checkinMethodMeta[$activity->checkin_method]['label'] ?? '' }}
                            </p>
                        </div>

                        <div class="mt-3.5 flex items-center gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                            <a href="{{ route('activities.show', $activity) }}" class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-center text-xs font-medium text-slate-600 transition-colors hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-brand-purple-500/40 dark:hover:text-brand-purple-400">
                                {{ __('รายละเอียด') }}
                            </a>
                            @if (in_array($activity->status, ['open', 'ongoing'], true))
                                <a href="{{ $activity->usesSelfReportCheckIn() ? route('self-checkin.show', $activity) : route('checkin.show') }}" class="flex-1 rounded-lg bg-brand-purple-600 px-3 py-2 text-center text-xs font-semibold text-white shadow-soft transition-colors hover:bg-brand-purple-700">
                                    {{ __('เช็คชื่อ') }}
                                </a>
                            @else
                                <span class="flex flex-1 items-center justify-center rounded-lg bg-slate-50 px-3 py-2 text-center text-xs text-slate-400 dark:bg-slate-800/60 dark:text-slate-500">
                                    {{ $levelLabel[$activity->activity_level] ?? '' }}
                                </span>
                            @endif
                        </div>
                    </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $activities->links() }}</div>
    @endif
</div>
@endsection
