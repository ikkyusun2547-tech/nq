@extends('layouts.dashboard')

@section('content')
@php
    $typeMeta = [
        'external' => ['label' => __('คำร้องกิจกรรมภายนอก'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'well' => 'bg-brand-purple-50 dark:bg-brand-purple-500/10', 'text' => 'text-brand-purple-600 dark:text-brand-purple-400'],
        'credit_transfer' => ['label' => __('คำร้องเทียบโอนตำแหน่ง'), 'icon' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15', 'well' => 'bg-cyan-50 dark:bg-cyan-500/10', 'text' => 'text-cyan-600 dark:text-cyan-400'],
        'late_checkin' => ['label' => __('คำร้องเช็คชื่อย้อนหลัง'), 'icon' => 'M12 6.75V12l3.75 1.875M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'well' => 'bg-amber-50 dark:bg-amber-500/10', 'text' => 'text-amber-600 dark:text-amber-400'],
    ];

    /**
     * A raw hour count like "312" doesn't read fast — pick whichever unit
     * (hours vs days) keeps the number small and legible, matching how an
     * admin would actually describe a turnaround time out loud.
     */
    $formatTurnaround = function (?float $hours) {
        if ($hours === null) {
            return __('ยังไม่มีข้อมูล');
        }

        return $hours >= 24
            ? __(':n วัน', ['n' => round($hours / 24, 1)])
            : __(':n ชม.', ['n' => round($hours, 1)]);
    };
@endphp

<div class="mx-auto max-w-5xl">
    <x-brand-header :title="__('สถิติคำร้อง')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('ปริมาณ อัตราอนุมัติ และเวลาตรวจสอบเฉลี่ยของคำร้องแต่ละประเภท')" :back="route('admin.reports.index')" />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @foreach ($typeMeta as $key => $meta)
            @php $s = $stats[$key]; @endphp
            <div class="rounded-2xl glass-card p-5 shadow-soft">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $meta['well'] }}">
                        <svg class="h-5 w-5 {{ $meta['text'] }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}"/></svg>
                    </span>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $meta['label'] }}</h2>
                </div>

                <p class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($s['total']) }}</p>
                <p class="mb-4 text-xs text-slate-400 dark:text-slate-500">{{ __('คำร้องทั้งหมด') }}</p>

                <div class="mb-4 grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-lg bg-amber-50 py-2 dark:bg-amber-500/10">
                        <p class="font-semibold text-amber-700 dark:text-amber-400">{{ number_format($s['pending']) }}</p>
                        <p class="text-amber-600/80 dark:text-amber-500/70">{{ __('รอตรวจสอบ') }}</p>
                    </div>
                    <div class="rounded-lg bg-brand-green-50 py-2 dark:bg-brand-green-500/10">
                        <p class="font-semibold text-brand-green-700 dark:text-brand-green-400">{{ number_format($s['approved']) }}</p>
                        <p class="text-brand-green-600/80 dark:text-brand-green-500/70">{{ __('อนุมัติ') }}</p>
                    </div>
                    <div class="rounded-lg bg-red-50 py-2 dark:bg-red-500/10">
                        <p class="font-semibold text-red-700 dark:text-red-400">{{ number_format($s['rejected']) }}</p>
                        <p class="text-red-600/80 dark:text-red-500/70">{{ __('ปฏิเสธ') }}</p>
                    </div>
                </div>

                <div class="space-y-2 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">{{ __('อัตราอนุมัติ') }}</span>
                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $s['approval_rate'] !== null ? $s['approval_rate'].'%' : __('ยังไม่มีข้อมูล') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 dark:text-slate-500">{{ __('เวลาตรวจสอบเฉลี่ย') }}</span>
                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $formatTurnaround($s['avg_turnaround_hours']) }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
