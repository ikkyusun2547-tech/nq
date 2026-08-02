@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-brand-header :title="__('นักศึกษาที่ผ่านเกณฑ์กิจกรรม')" :eyebrow="__('กองพัฒนานักศึกษา')">
        <x-slot:actions>
            <a href="{{ route('admin.reports.clearance-pdf', ['year' => $year]) }}"
                class="flex shrink-0 items-center gap-1.5 rounded-xl bg-white/10 px-3 py-2 text-xs font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-colors hover:bg-white/15">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                {{ __('ดาวน์โหลด PDF') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach ([1, 2, 3, 4] as $y)
            <a href="{{ route('admin.reports.clearance', ['year' => $y]) }}"
                @class([
                    'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 font-medium transition-all duration-200',
                    'bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 text-white shadow-soft' => $year === $y,
                    'bg-white text-slate-500 shadow-soft ring-1 ring-slate-200 hover:-translate-y-0.5 hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-brand-purple-400' => $year !== $y,
                ])>
                {{ __('ชั้นปีที่ :year', ['year' => $y]) }}
            </a>
        @endforeach
    </div>

    <div class="mb-4 rounded-xl border border-brand-green-200 bg-brand-green-50 p-4 dark:border-brand-green-500/20 dark:bg-brand-green-500/10">
        <p class="text-xs font-medium text-brand-green-700 dark:text-brand-green-400">{{ __('ผ่านเกณฑ์แล้ว ชั้นปีที่ :year', ['year' => $year]) }}</p>
        <p class="mt-0.5 text-2xl font-bold text-brand-green-800 dark:text-brand-green-300">{{ number_format($students->total()) }} <span class="text-sm font-normal text-brand-green-600 dark:text-brand-green-400">{{ __('คน') }}</span></p>
    </div>

    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <x-sortable-th field="name" :label="__('นักศึกษา')" />
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ/สาขา') }}</th>
                    <x-sortable-th field="total_activities" :label="__('กิจกรรมสะสม')" />
                    <x-sortable-th field="total_hours" :label="__('ชั่วโมงสะสม')" />
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $row)
                    @php $user = $row['user']; @endphp
                    <tr @class([
                        'border-b border-slate-100 last:border-0 dark:border-slate-800',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-purple-500 to-brand-purple-700 text-xs font-semibold text-white shadow-soft">
                                    {{ mb_substr($user->name_thai ?? $user->name, 0, 1) }}
                                </span>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-100">{{ $user->name_thai ?? $user->name }}</p>
                                    <p class="font-mono text-xs text-slate-400 dark:text-slate-500">{{ $user->student_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            {{ $user->faculty?->name_th ?? '-' }}
                            @if ($user->major)
                                <span class="text-slate-300 dark:text-slate-600">·</span> {{ $user->major->name_th }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($row['total_activities']) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums font-medium text-brand-green-700 dark:text-brand-green-400">{{ number_format($row['total_hours']) }} {{ __('ชม.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ไม่มีนักศึกษาที่ผ่านเกณฑ์ครบถ้วนในชั้นปีนี้') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $students->links() }}</div>
</div>
@endsection
