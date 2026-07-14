@extends('layouts.dashboard')

@section('content')
@php
    $actionBadge = [
        'approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'created' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'updated' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400',
        'deleted' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'promoted' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'demoted' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'banned' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'unbanned' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
    ];
    $actionLabel = [
        'approved' => __('อนุมัติ'), 'rejected' => __('ปฏิเสธ'),
        'created' => __('เพิ่มใหม่'), 'updated' => __('แก้ไข'), 'deleted' => __('ลบ'),
        'promoted' => __('เลื่อนสิทธิ์'), 'demoted' => __('ลดสิทธิ์'),
        'banned' => __('ระงับบัญชี'), 'unbanned' => __('ปลดระงับ'),
    ];
@endphp

<div class="mx-auto max-w-6xl">
    <x-brand-header :title="__('ประวัติการตรวจสอบและการดำเนินการ')" :eyebrow="__('กองพัฒนานักศึกษา')">
        <x-slot:actions>
            <span class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-500/20 px-4 py-2 text-sm font-medium text-brand-green-100 shadow-soft ring-1 ring-brand-green-300/30 backdrop-blur">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                {{ __('อนุมัติ :count', ['count' => number_format($actionCounts['approved'])]) }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-xl bg-red-500/20 px-4 py-2 text-sm font-medium text-red-100 shadow-soft ring-1 ring-red-300/30 backdrop-blur">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ __('ปฏิเสธ :count', ['count' => number_format($actionCounts['rejected'])]) }}
            </span>
        </x-slot:actions>
    </x-brand-header>

    <form method="GET" action="{{ route('admin.audit-log.index') }}" class="mb-4 mt-4 max-w-xs">
        @php $reviewerOptions = $reviewers->mapWithKeys(fn ($u) => [$u->id => $u->name_thai ?? $u->name])->all(); @endphp
        <x-premium-select
            name="reviewer_id" :options="$reviewerOptions" :selected="request('reviewer_id')"
            placeholder="{{ __('-- ผู้ตรวจสอบทั้งหมด --') }}" autosubmit
        />
    </form>

    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('เวลา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ผู้ตรวจสอบ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('การกระทำ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ประเภท') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('บุคคลที่เกี่ยวข้อง') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('รายการ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr @class([
                        'border-b border-slate-100 dark:border-slate-800 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:hover:bg-slate-800/60',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $entry->reviewed_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-300">{{ $entry->reviewer_name ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $actionBadge[$entry->action] ?? 'bg-slate-100 text-slate-500' }}">
                                {{ $actionLabel[$entry->action] ?? $entry->action }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $entry->type_label }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-300">{{ $entry->student_name ?? '-' }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-slate-500 dark:text-slate-400">{{ $entry->title }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีประวัติการตรวจสอบ') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $entries->links() }}</div>
</div>
@endsection
