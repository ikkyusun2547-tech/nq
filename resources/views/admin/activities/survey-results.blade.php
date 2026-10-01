@extends('layouts.dashboard')

@section('content')
@php
    $responseRate = $summary['attendees'] > 0 ? round($summary['respondents'] / $summary['attendees'] * 100, 1) : 0;
@endphp

<div class="mx-auto max-w-5xl">
    <x-brand-header :title="__('ผลประเมินความพึงพอใจ')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="$activity->title">
        <x-slot:actions>
            @if ($summary['respondents'] > 0)
                <a href="{{ route('admin.activities.survey-results.export', $activity) }}"
                    class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
                    {{ __('Export Excel') }}
                </a>
            @endif
        </x-slot:actions>
    </x-brand-header>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl glass-card p-5 shadow-soft">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ผู้ตอบแบบประเมิน') }}</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ $summary['respondents'] }} <span class="text-sm font-medium text-slate-400">/ {{ $summary['attendees'] }}</span></p>
            <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('คิดเป็นร้อยละ :pct ของผู้เข้าร่วม', ['pct' => $responseRate]) }}</p>
        </div>
        <div class="rounded-2xl glass-card p-5 shadow-soft">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ค่าเฉลี่ยรวม (x̄)') }}</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ $summary['overall'] ? number_format($summary['overall']['mean'], 2) : '–' }}</p>
            <p class="text-xs text-slate-400 dark:text-slate-500">S.D. {{ $summary['overall'] ? number_format($summary['overall']['sd'], 2) : '–' }}</p>
        </div>
        <div class="rounded-2xl glass-card p-5 shadow-soft">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ระดับความพึงพอใจ') }}</p>
            <p class="mt-2">
                @if ($summary['overall'])
                    @include('partials.survey-level-badge', ['level' => $summary['overall']['level']])
                @else
                    <span class="text-sm text-slate-400">–</span>
                @endif
            </p>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 dark:text-slate-500">{{ __('รายการประเมิน') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">x̄</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">S.D.</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 dark:text-slate-500">{{ __('ระดับ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($summary['questions'] as $q)
                    <tr class="border-b border-slate-100 last:border-0 dark:border-slate-800">
                        <td class="min-w-[16rem] px-4 py-3 text-slate-800 dark:text-slate-100">{{ $loop->iteration }}. {{ $q['text'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($q['mean'], 2) }}</td>
                        <td class="px-4 py-3 text-center tabular-nums text-slate-500 dark:text-slate-400">{{ number_format($q['sd'], 2) }}</td>
                        <td class="px-4 py-3 text-center">@include('partials.survey-level-badge', ['level' => $q['level']])</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีผู้ตอบแบบประเมิน') }}</td></tr>
                @endforelse
                @if ($summary['overall'])
                    <tr class="bg-brand-purple-50/50 font-semibold dark:bg-brand-purple-500/10">
                        <td class="px-4 py-3 text-slate-900 dark:text-slate-100">{{ __('รวม') }}</td>
                        <td class="px-4 py-3 text-center tabular-nums text-slate-900 dark:text-slate-100">{{ number_format($summary['overall']['mean'], 2) }}</td>
                        <td class="px-4 py-3 text-center tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($summary['overall']['sd'], 2) }}</td>
                        <td class="px-4 py-3 text-center">@include('partials.survey-level-badge', ['level' => $summary['overall']['level']])</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">{{ __('เกณฑ์การแปลผล: 4.51–5.00 มากที่สุด, 3.51–4.50 มาก, 2.51–3.50 ปานกลาง, 1.51–2.50 น้อย, 1.00–1.50 น้อยที่สุด') }}</p>

    <div class="mt-6">
        <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('ข้อเสนอแนะ (:count)', ['count' => $summary['comments']->count()]) }}</h2>
        @forelse ($summary['comments'] as $response)
            <div class="mb-2 rounded-2xl bg-white px-4 py-3 text-sm text-slate-700 shadow-soft ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">
                <p class="whitespace-pre-line">{{ $response->comment }}</p>
            </div>
        @empty
            <p class="text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีข้อเสนอแนะ') }}</p>
        @endforelse
    </div>
</div>
@endsection
