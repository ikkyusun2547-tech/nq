@extends('layouts.dashboard')

@section('content')
@php
    $actionBadge = [
        'approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $actionLabel = ['approved' => __('อนุมัติ'), 'rejected' => __('ปฏิเสธ')];
@endphp

<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('กองพัฒนานักศึกษา') }}</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ __('ประวัติการอนุมัติ/ปฏิเสธ') }}</h1>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit-log.index') }}" class="mb-4 max-w-xs">
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
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('นักศึกษา') }}</th>
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
                        <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-300">{{ $entry->reviewer?->name_thai ?? $entry->reviewer?->name ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $actionBadge[$entry->action] ?? 'bg-slate-100 text-slate-500' }}">
                                {{ $actionLabel[$entry->action] ?? $entry->action }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $entry->type_label }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-300">{{ $entry->student->name_thai ?? $entry->student->name ?? '-' }}</td>
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
