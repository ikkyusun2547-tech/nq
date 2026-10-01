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
    <x-brand-header :title="__('อัตราเข้าร่วมต่อกิจกรรม')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('เทียบจำนวนที่เช็คชื่อจริงกับจำนวนนักศึกษาที่มีสิทธิ์เข้าร่วมของแต่ละกิจกรรม')">
        <x-slot:actions>
            <a href="{{ route('admin.reports.activity-participation-excel') }}"
                class="flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-brand-purple-500/40 dark:hover:text-brand-purple-300">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                {{ __('ดาวน์โหลด Excel') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    <div class="overflow-x-auto rounded-3xl glass-card">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800">
                    <x-sortable-th field="title" :label="__('กิจกรรม')" />
                    <x-sortable-th field="start_at" :label="__('วันที่')" />
                    <x-sortable-th field="eligible_count" :label="__('มีสิทธิ์')" />
                    <x-sortable-th field="attendances_count" :label="__('เช็คชื่อแล้ว')" />
                    <x-sortable-th field="participation_pct" :label="__('อัตราเข้าร่วม')" />
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr @class([
                        'border-b border-slate-100 last:border-0 dark:border-slate-800',
                    ])>
                        <td class="max-w-xs px-4 py-3">
                            <p class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $activity->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">{{ $categoryLabels[$activity->activity_category] ?? $activity->activity_category }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $activity->start_at?->translatedFormat('d M Y') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($activity->eligible_count) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($activity->attendances_count) }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($activity->participation_pct === null)
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('ไม่จำกัดสิทธิ์') }}</span>
                            @else
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-24 overflow-hidden rounded-full bg-brand-purple-50 dark:bg-brand-purple-500/10">
                                        <div @class([
                                            'h-full rounded-full',
                                            'bg-brand-green-500' => $activity->participation_pct >= 50,
                                            'bg-amber-500' => $activity->participation_pct < 50,
                                        ]) style="width: {{ min(100, $activity->participation_pct) }}%"></div>
                                    </div>
                                    <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ $activity->participation_pct }}%</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีกิจกรรม') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
