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
    $typeMeta = [
        'core' => ['label' => __('บังคับแกน'), 'class' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400'],
        'elective' => ['label' => __('บังคับเลือก'), 'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'],
        'practice' => ['label' => __('ซ้อม/เตรียมงาน'), 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'],
    ];
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
@endphp

<div class="mx-auto max-w-6xl">
    <x-brand-header eyebrow="{{ __('กองพัฒนานักศึกษา') }}" :title="$pageTitle">
        <x-slot:actions>
            <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
                {{ __(':count กิจกรรม', ['count' => $activities->total()]) }}
            </span>
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

    <form method="GET" action="{{ route('activities.index') }}" class="mb-5 space-y-3">
        <input type="hidden" name="status_group" value="{{ $statusGroup }}">

        @php
            $facultyOptions = $faculties->pluck('name_th', 'id')->all();
            $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => __('ปีการศึกษา :year', ['year' => $y])])->all();
            $categoryOptions = collect($categoryMeta)->map(fn ($meta) => $meta['label'])->all();
        @endphp

        <div class="relative">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input
                type="search" name="search" value="{{ request('search') }}"
                placeholder="{{ __('ค้นหากิจกรรม (ชื่อหรือหน่วยงานจัด)') }}"
                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm shadow-soft transition-all duration-200 placeholder:text-slate-400 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
            >
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-premium-select
                name="activity_category" :options="$categoryOptions" :selected="request('activity_category')"
                placeholder="{{ __('-- ทุกหมวดหมู่ --') }}" autosubmit
            />

            <x-premium-select
                name="activity_level" :options="$levelLabel" :selected="request('activity_level')"
                placeholder="{{ __('-- ทุกระดับ (รวม) --') }}" autosubmit
            />

            <x-premium-select
                name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
                placeholder="{{ __('-- ทุกคณะ (แยกดูได้) --') }}" autosubmit
            />

            <x-premium-select
                name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
                placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit
            />
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
                        <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                                <span class="h-1.5 w-1.5 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                                {{ $categoryMeta[$activity->activity_category]['label'] ?? $activity->activity_category }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[0.68rem] font-medium {{ $typeMeta[$activity->activity_type]['class'] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                {{ $typeMeta[$activity->activity_type]['label'] ?? $activity->activity_type }}
                            </span>
                            @if ($activity->wasRecentlyUpdatedSignificantly())
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-0.5 text-[0.68rem] font-medium text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">
                                    <svg class="h-2.5 w-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                    {{ __('อัปเดตแล้ว') }}
                                </span>
                            @endif
                        </div>

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
                            @if ($activity->organizer_name)
                                <p class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                                    {{ $activity->organizer_name }}
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

                        <div class="mt-3.5 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                            <span class="text-xs font-medium text-brand-purple-600 dark:text-brand-purple-400">{{ __(':hours ชม.', ['hours' => $activity->credit_hours]) }}</span>
                            @if (in_array($activity->status, ['open', 'ongoing'], true))
                                @if ($activity->usesSelfReportCheckIn())
                                    <a href="{{ route('self-checkin.show', $activity) }}" class="text-sm font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('ไปเช็คชื่อ') }} &rarr;</a>
                                @else
                                    <a href="{{ route('checkin.show') }}" class="text-sm font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('ไปเช็คชื่อ') }} &rarr;</a>
                                @endif
                            @elseif ($activity->status === 'closed' && ! $checkedInActivityIds->contains($activity->id))
                                @php $lateStatus = $lateCheckInStatuses[$activity->id] ?? null; @endphp
                                <a href="{{ route('late-checkin.show', $activity) }}" class="text-sm font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">
                                    @if ($lateStatus === 'pending')
                                        {{ __('รอตรวจสอบคำร้องย้อนหลัง') }} &rarr;
                                    @elseif ($lateStatus === 'rejected')
                                        {{ __('ยื่นคำร้องใหม่') }} &rarr;
                                    @else
                                        {{ __('ขอเช็คชื่อย้อนหลัง') }} &rarr;
                                    @endif
                                </a>
                            @elseif ($activity->status === 'closed' && ($lateCheckInStatuses[$activity->id] ?? null) === 'approved')
                                <a href="{{ route('late-checkin.show', $activity) }}" class="text-sm font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">
                                    {{ __('ดูรายละเอียดคำร้อง') }} &rarr;
                                </a>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $levelLabel[$activity->activity_level] ?? '' }}</span>
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
