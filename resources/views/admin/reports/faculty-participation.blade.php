@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-brand-header :title="__('สรุปการเข้าร่วมกิจกรรมรายคณะ')" :eyebrow="__('กองพัฒนานักศึกษา')" :back="route('admin.reports.index')">
        <x-slot:actions>
            <a href="{{ route('admin.reports.faculty-participation-excel') }}"
                class="flex shrink-0 items-center gap-1.5 rounded-xl bg-white/10 px-3 py-2 text-xs font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-colors hover:bg-white/15">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                {{ __('ดาวน์โหลด Excel') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('จำนวนนักศึกษา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ผ่านเกณฑ์แล้ว') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ร้อยละที่ผ่านเกณฑ์') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชั่วโมงเฉลี่ย/คน') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('กิจกรรมเฉลี่ย/คน') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr @class([
                        'border-b border-slate-100 last:border-0 dark:border-slate-800',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $row['faculty']->name_th }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($row['student_count']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($row['cleared_count']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-24 overflow-hidden rounded-full bg-brand-purple-50 dark:bg-brand-purple-500/10">
                                    <div class="h-full rounded-full bg-brand-green-500 glow-emerald" style="width: {{ min(100, $row['cleared_pct']) }}%"></div>
                                </div>
                                <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ $row['cleared_pct'] }}%</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-500 dark:text-slate-400">{{ $row['avg_hours'] }} {{ __('ชม.') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-500 dark:text-slate-400">{{ $row['avg_activities'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีข้อมูลคณะ') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
