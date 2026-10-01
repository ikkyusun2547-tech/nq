@extends('layouts.dashboard')

@section('content')
@php
    $categoryLabels = [
        'culture' => __('ทำนุบำรุงศิลปวัฒนธรรม'),
        'academic' => __('วิชาการ'),
        'sports' => __('กีฬาและส่งเสริมสุขภาพ'),
        'volunteer' => __('จิตอาสา/บำเพ็ญประโยชน์'),
        'ethics' => __('คุณธรรมจริยธรรม'),
    ];
@endphp

<div class="mx-auto max-w-6xl">
    <x-brand-header :title="__('ความพึงพอใจต่อกิจกรรม')" :eyebrow="__('รายงาน')" :subtitle="__('ผลแบบประเมินหลังกิจกรรม เรียงจากคะแนนเฉลี่ยสูงไปต่ำ')" />

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="academic_year" onchange="this.form.submit()"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
            <option value="" @selected($academicYear === '')>{{ __('ทุกปีการศึกษา') }}</option>
            @foreach ($academicYears as $year)
                <option value="{{ $year }}" @selected((string) $year === $academicYear)>{{ __('ปีการศึกษา :year', ['year' => $year]) }}</option>
            @endforeach
        </select>
    </form>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="rounded-2xl glass-card p-4 shadow-soft lg:col-span-1">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ภาพรวม') }}</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ $overall ? number_format($overall['mean'], 2) : '–' }}</p>
            @if ($overall) @include('partials.survey-level-badge', ['level' => $overall['level']]) @endif
        </div>
        @foreach ($categoryLabels as $key => $label)
            <div class="rounded-2xl glass-card p-4 shadow-soft">
                <p class="truncate text-xs text-slate-500 dark:text-slate-400" title="{{ $label }}">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ isset($byCategory[$key]) ? number_format($byCategory[$key]['mean'], 2) : '–' }}</p>
                @isset($byCategory[$key]) @include('partials.survey-level-badge', ['level' => $byCategory[$key]['level']]) @endisset
            </div>
        @endforeach
    </div>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 dark:text-slate-500">{{ __('กิจกรรม') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">{{ __('ผู้ตอบ') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">x̄</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">S.D.</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">{{ __('ระดับ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr class="border-b border-slate-100 last:border-0 dark:border-slate-800">
                        <td class="min-w-[16rem] px-4 py-3">
                            <a href="{{ route('admin.activities.survey-results', $activity) }}" class="font-medium text-slate-900 hover:text-brand-purple-600 dark:text-slate-100 dark:hover:text-brand-purple-400">{{ $activity->title }}</a>
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ $activity->start_at->translatedFormat('d M Y') }} · {{ $categoryLabels[$activity->activity_category] ?? '' }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center tabular-nums text-slate-600 dark:text-slate-300">{{ $activity->respondents }} / {{ $activity->attendees_count }}</td>
                        <td class="px-4 py-3 text-center tabular-nums font-medium text-slate-800 dark:text-slate-100">{{ number_format($activity->survey['mean'], 2) }}</td>
                        <td class="px-4 py-3 text-center tabular-nums text-slate-500 dark:text-slate-400">{{ number_format($activity->survey['sd'], 2) }}</td>
                        <td class="px-4 py-3 text-center">@include('partials.survey-level-badge', ['level' => $activity->survey['level']])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีผลแบบประเมินในช่วงที่เลือก') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
