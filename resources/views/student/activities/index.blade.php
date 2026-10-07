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
        'open' => ['label' => __('เปิดลงทะเบียน'), 'class' => 'bg-brand-green-500/90 text-white'],
        'ongoing' => ['label' => __('กำลังจัดอยู่'), 'class' => 'bg-brand-green-500/90 text-white'],
        'draft' => ['label' => __('ยังไม่เปิด'), 'class' => 'bg-slate-500/90 text-white'],
        'closed' => ['label' => __('จบไปแล้ว'), 'class' => 'bg-slate-500/90 text-white'],
    ];
    $levelLabel = ['university' => __('ระดับมหาวิทยาลัย'), 'faculty' => __('ระดับคณะ')];
    $checkinMethodMeta = [
        'realtime' => ['label' => __('สแกน QR + GPS + เซลฟี')],
        'self_report' => ['label' => __('แนบรูปหลักฐาน (รายงานตนเอง)')],
    ];
    $statusGroupTabs = [
        'open' => __('สำหรับคุณ'),
        'upcoming' => __('ยังไม่เปิด'),
        'ended' => __('จบไปแล้ว'),
    ];
    $pageTitle = [
        'open' => __('กิจกรรมสำหรับคุณ'),
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
            @include('partials.activity-view-toggle', ['listRoute' => 'activities.index', 'calendarRoute' => 'activities.calendar', 'active' => 'list'])
        </x-slot:actions>
    </x-brand-header>

    @php
        $facultyOptions = $faculties->pluck('name_th', 'id')->all();
        $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => __('ปีการศึกษา :year', ['year' => $y])])->all();
        $categoryOptions = collect($categoryMeta)->map(fn ($meta) => $meta['label'])->all();
        $searchClass = 'h-11 w-full rounded-full border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-900 transition placeholder:text-slate-400 focus:border-brand-purple-400 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500';
    @endphp

    {{--
        The mobile sheet and the desktop toolbar below both render the same
        four filters, so two real, same-named controls exist in the DOM at
        once — a plain GET submit would send both and let whichever sits
        last silently clobber the other. `isDesktop` (tracked via
        matchMedia, not just CSS display) disables whichever copy isn't the
        one actually visible, so exactly one of each is ever submitted.
    --}}
    <form id="activity-filters" method="GET" action="{{ route('activities.index') }}" class="mb-6"
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

        {{-- Status tabs (segmented) + result count --}}
        <div class="mb-3 flex flex-wrap items-center gap-3">
            <nav aria-label="{{ __('สถานะกิจกรรม') }}" class="inline-flex rounded-full bg-slate-100 p-1 text-sm dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
                @foreach ($statusGroupTabs as $value => $label)
                    <a href="{{ route('activities.index', array_merge(request()->only(['activity_level', 'activity_category', 'search', 'academic_year', 'faculty_id']), ['status_group' => $value])) }}"
                        @if ($statusGroup === $value) aria-current="page" @endif
                        @class([
                            'rounded-full px-4 py-1.5 transition-colors',
                            'bg-white font-semibold text-slate-900 shadow-soft dark:bg-slate-700 dark:text-white' => $statusGroup === $value,
                            'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => $statusGroup !== $value,
                        ])>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ __(':count กิจกรรม', ['count' => $activities->total()]) }}</span>
        </div>

        {{-- Mobile: search + filter-sheet trigger --}}
        <div class="flex gap-2 sm:hidden">
            <label class="relative flex-1">
                <span class="sr-only">{{ __('ค้นหากิจกรรม') }}</span>
                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="search" name="search" value="{{ request('search') }}" x-bind:disabled="isDesktop" placeholder="{{ __('ค้นหากิจกรรม') }}" class="{{ $searchClass }}">
            </label>
            <button type="button" @click="filtersOpen = true" aria-label="{{ __('ตัวกรอง') }}"
                @class([
                    'relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors',
                    'bg-brand-purple-700 text-white' => $activeFilterCount > 0,
                    'border border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => $activeFilterCount === 0,
                ])>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m9 12h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9-12H3.75m9 12H3.75m9-12H9m6 12v.007M12 6.75a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-6 6a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm0 0H3.75m3 0H12"/></svg>
                @if ($activeFilterCount > 0)
                    <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-[0.65rem] font-bold text-brand-purple-700 ring-2 ring-brand-purple-700">{{ $activeFilterCount }}</span>
                @endif
            </button>
        </div>

        {{-- Desktop/tablet: search + filter chips on one toolbar --}}
        <div class="hidden flex-wrap items-center gap-2 sm:flex">
            <label class="relative w-full lg:w-80">
                <span class="sr-only">{{ __('ค้นหากิจกรรม (ชื่อหรือหน่วยงานจัด)') }}</span>
                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="search" name="search" value="{{ request('search') }}" x-bind:disabled="! isDesktop" placeholder="{{ __('ค้นหากิจกรรม (ชื่อหรือหน่วยงานจัด)') }}" class="{{ $searchClass }}">
            </label>

            <x-premium-select variant="chip" name="activity_category" :options="$categoryOptions" :selected="request('activity_category')" placeholder="{{ __('ทุกหมวดหมู่') }}" autosubmit x-bind:disabled="! isDesktop" />
            <x-premium-select variant="chip" name="activity_level" :options="$levelLabel" :selected="request('activity_level')" placeholder="{{ __('ทุกระดับ') }}" autosubmit x-bind:disabled="! isDesktop" />
            <x-premium-select variant="chip" name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')" placeholder="{{ __('ทุกคณะ') }}" autosubmit x-bind:disabled="! isDesktop" />
            <x-premium-select variant="chip" name="academic_year" :options="$academicYearOptions" :selected="$academicYear" placeholder="{{ __('ทุกปีการศึกษา') }}" autosubmit x-bind:disabled="! isDesktop" />

            @if ($activeFilterCount > 0 || request()->filled('search'))
                <a href="{{ route('activities.index', ['status_group' => $statusGroup]) }}"
                    class="inline-flex h-9 items-center gap-1 rounded-full px-3 text-sm font-medium text-slate-500 hover:text-brand-purple-700 dark:text-slate-400 dark:hover:text-brand-purple-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ __('ล้างตัวกรอง') }}
                </a>
            @endif
        </div>

        {{-- Mobile: chip filter sheet (same 4 fields as the desktop toolbar). --}}
        <x-filter-sheet form="activity-filters" :clear-url="route('activities.index', array_merge(request()->only(['search']), ['status_group' => $statusGroup]))" :groups="[
            ['name' => 'activity_category', 'label' => __('หมวดหมู่'), 'all' => __('ทุกหมวดหมู่'), 'options' => $categoryOptions, 'selected' => request('activity_category'),
                'dots' => collect($categoryMeta)->map(fn ($m) => $m['dot'])->all()],
            ['name' => 'activity_level', 'label' => __('ระดับกิจกรรม'), 'all' => __('ทุกระดับ'), 'options' => $levelLabel, 'selected' => request('activity_level')],
            ['name' => 'faculty_id', 'label' => __('คณะ'), 'all' => __('ทุกคณะ'), 'options' => $facultyOptions, 'selected' => request('faculty_id')],
            ['name' => 'academic_year', 'label' => __('ปีการศึกษา'), 'all' => __('ทุกปี'), 'options' => $academicYears->mapWithKeys(fn ($y) => [$y => (string) $y])->all(), 'selected' => $academicYear],
        ]" />    </form>

    @if ($activities->isEmpty())
        <div class="rounded-3xl glass-card p-10 text-center text-slate-400 dark:text-slate-500">
            {{ __('ไม่พบกิจกรรมที่ตรงกับเงื่อนไข') }}
        </div>
    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($activities as $activity)
                {{-- Whole card opens the activity: the title link's ::after covers it,
                     and the action buttons sit above that layer (z-10). --}}
                <div class="group relative flex h-full flex-col overflow-hidden rounded-3xl glass-card transition-colors hover:border-brand-purple-300 dark:hover:border-brand-purple-500/40">
                    <div class="relative aspect-[16/9] w-full overflow-hidden bg-brand-purple-700">
                        @if ($activity->banner_url)
                            <img src="{{ asset('storage/'.$activity->banner_url) }}" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <svg class="h-10 w-10 text-white/30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 5.25h18M3 5.25v13.5A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V5.25M3 5.25A2.25 2.25 0 015.25 3h13.5A2.25 2.25 0 0121 5.25"/></svg>
                            </div>
                        @endif

                        <span class="absolute right-3 top-3 rounded-full px-2.5 py-1 text-xs font-medium shadow-soft backdrop-blur {{ $statusBadge[$activity->displayStatus()]['class'] ?? 'bg-slate-500/90 text-white' }}">
                            {{ $statusBadge[$activity->displayStatus()]['label'] ?? $activity->status }}
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
                    <div class="flex flex-1 flex-col p-4">
                        <span class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                            <span class="h-1.5 w-1.5 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                            {{ $categoryMeta[$activity->activity_category]['label'] ?? $activity->activity_category }}
                        </span>

                        @if ($activity->activity_code)
                            <p class="mb-0.5 font-mono text-[0.68rem] text-slate-400 dark:text-slate-500">{{ $activity->activity_code }}</p>
                        @endif
                        <h2 class="line-clamp-2 font-semibold text-slate-900 group-hover:text-brand-purple-700 dark:text-slate-100 dark:group-hover:text-brand-purple-300" style="text-wrap: balance;"><a href="{{ route('activities.show', $activity) }}" class="after:absolute after:inset-0 after:rounded-3xl focus:outline-none focus-visible:after:ring-2 focus-visible:after:ring-brand-purple-500">{{ $activity->title }}</a></h2>

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

                        {{-- One full-width action; the card itself is the "details" link.
                             Buttons are z-10 so they sit above the card-wide link. --}}
                        <div class="mt-3.5 flex items-center border-t border-slate-100 pt-3 dark:border-slate-800">
                            @if ($checkedInActivityIds->contains($activity->id))
                                <span class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-brand-green-50 px-3 py-2.5 text-center text-sm font-semibold text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    {{ __('เช็คชื่อแล้ว') }}
                                </span>
                            @elseif (in_array($activity->status, ['open', 'ongoing'], true))
                                <a href="{{ $activity->usesSelfReportCheckIn() ? route('self-checkin.show', $activity) : route('checkin.show') }}" class="relative z-10 flex-1 rounded-xl bg-brand-purple-700 px-3 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">
                                    {{ __('เช็คชื่อ') }}
                                </a>
                            @elseif ($activity->status === 'closed')
                                {{-- Missed it: offer a late check-in request (rules in LateCheckInController). --}}
                                @php $lateStatus = $lateRequestStatuses[$activity->id] ?? null; @endphp
                                @if ($lateStatus === 'pending')
                                    <a href="{{ route('late-checkin.show', $activity) }}" class="relative z-10 flex flex-1 items-center justify-center rounded-xl bg-amber-50 px-3 py-2.5 text-center text-sm font-semibold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                                        {{ __('รอตรวจคำร้อง') }}
                                    </a>
                                @else
                                    <a href="{{ route('late-checkin.show', $activity) }}" class="relative z-10 flex flex-1 items-center justify-center rounded-xl border border-brand-purple-200 px-3 py-2.5 text-center text-sm font-semibold text-brand-purple-700 transition-colors hover:bg-brand-purple-50 dark:border-brand-purple-500/30 dark:text-brand-purple-300 dark:hover:bg-brand-purple-500/10">
                                        {{ $lateStatus === 'rejected' ? __('ยื่นคำร้องใหม่') : __('ขอเช็คชื่อย้อนหลัง') }}
                                    </a>
                                @endif
                            @else
                                {{-- No action yet: plain text, so a tap here still opens the card. --}}
                                <span class="flex flex-1 items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ $levelLabel[$activity->activity_level] ?? '' }}</span>
                                    <span class="font-medium text-brand-purple-700 dark:text-brand-purple-300">{{ __('ดูรายละเอียด') }} ›</span>
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
