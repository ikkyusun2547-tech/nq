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
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('กองพัฒนานักศึกษา') }}</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ $pageTitle }}</h1>
        </div>
        <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
            {{ __(':count กิจกรรม', ['count' => $activities->count()]) }}
        </span>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach ($statusGroupTabs as $value => $label)
            <a href="{{ route('activities.index', array_merge(request()->only(['activity_level', 'academic_year', 'faculty_id']), ['status_group' => $value])) }}"
                @class([
                    'rounded-full px-3.5 py-1.5 font-medium transition-all duration-200',
                    'bg-brand-purple-600 text-white shadow-soft' => $statusGroup === $value,
                    'bg-white text-slate-500 shadow-soft ring-1 ring-slate-200 hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-brand-purple-400' => $statusGroup !== $value,
                ])>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('activities.index') }}" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <input type="hidden" name="status_group" value="{{ $statusGroup }}">

        @php $facultyOptions = $faculties->pluck('name_th', 'id')->all(); $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => __('ปีการศึกษา :year', ['year' => $y])])->all(); @endphp

        <x-premium-select
            name="activity_level" :options="$levelLabel" :selected="request('activity_level')"
            placeholder="{{ __('-- ทุกระดับ (รวม) --') }}" autosubmit
        />

        <x-premium-select
            name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
            placeholder="{{ __('-- ทุกคณะ (แยกดูได้) --') }}" autosubmit
        />

        <x-premium-select
            name="academic_year" :options="$academicYearOptions" :selected="request('academic_year')"
            placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit
        />
    </form>

    @if ($activities->isEmpty())
        <div class="rounded-2xl glass-card p-10 text-center text-slate-400 shadow-soft dark:text-slate-500">
            {{ __('ไม่พบกิจกรรมที่ตรงกับเงื่อนไข') }}
        </div>
    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($activities as $activity)
                <div class="overflow-hidden rounded-2xl glass-card shadow-soft transition-transform duration-200 hover:-translate-y-1">
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
                                ✓ {{ __('เช็กชื่อแล้ว') }}
                            </span>
                        @endif
                    </div>

                    <div class="p-4">
                        <span class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                            <span class="h-1.5 w-1.5 rounded-full {{ $categoryMeta[$activity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                            {{ $categoryMeta[$activity->activity_category]['label'] ?? $activity->activity_category }}
                        </span>

                        <h2 class="line-clamp-2 font-semibold text-slate-900 dark:text-slate-100" style="text-wrap: balance;">{{ $activity->title }}</h2>

                        <div class="mt-2.5 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                            <p class="flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                {{ $activity->start_at->translatedFormat('d M Y H:i') }}
                            </p>
                            @if ($activity->organizer_name)
                                <p class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                                    {{ $activity->organizer_name }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-3.5 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                            <span class="text-xs font-medium text-brand-purple-600 dark:text-brand-purple-400">{{ __(':hours ชม.', ['hours' => $activity->credit_hours]) }}</span>
                            @if (in_array($activity->status, ['open', 'ongoing'], true))
                                <a href="{{ route('checkin.show') }}" class="text-sm font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('ไปเช็กชื่อ') }} &rarr;</a>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $levelLabel[$activity->activity_level] ?? '' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
