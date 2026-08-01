@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-brand-header :title="__('ประวัติการส่งประกาศ')" :eyebrow="__('กองพัฒนานักศึกษา')">
        <x-slot:actions>
            <a href="{{ route('admin.announcements.create') }}"
                class="flex shrink-0 items-center gap-1.5 rounded-xl bg-white/10 px-3 py-2 text-xs font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-colors hover:bg-white/15">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('ส่งประกาศใหม่') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <x-sortable-th field="subject" :label="__('หัวข้อ')" />
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ส่งถึง') }}</th>
                    <x-sortable-th field="recipient_count" :label="__('จำนวนผู้รับ')" />
                    <x-sortable-th field="sender" :label="__('ผู้ส่ง')" />
                    <x-sortable-th field="created_at" :label="__('วันที่ส่ง')" />
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr @class([
                        'border-b border-slate-100 last:border-0 dark:border-slate-800',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="max-w-xs px-4 py-3">
                            <p class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $log->subject }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-slate-500">{{ $log->body }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            @if (! $log->faculty && ! $log->year_level)
                                {{ __('ทุกคน') }}
                            @else
                                {{ collect([$log->faculty?->name_th, $log->year_level ? __('ชั้นปีที่ :year', ['year' => $log->year_level]) : null])->filter()->implode(' · ') }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($log->recipient_count) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $log->sender->name_thai ?? $log->sender->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่เคยส่งประกาศ') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
