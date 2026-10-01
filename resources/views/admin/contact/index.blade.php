@extends('layouts.dashboard')

@section('content')
@php
    $tabs = ['open' => __('เปิดอยู่'), 'closed' => __('ปิดแล้ว'), 'all' => __('ทั้งหมด')];
    $statusBadge = [
        'open' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'closed' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    ];
    $statusLabel = ['open' => __('เปิดอยู่'), 'closed' => __('ปิดแล้ว')];
@endphp

<div class="mx-auto max-w-[90rem]">
    <x-brand-header :title="__('ข้อความจากนักศึกษา')" :eyebrow="__('กองพัฒนานักศึกษา')" />

    <div class="mb-4 mt-4 flex flex-wrap gap-2 text-sm">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('admin.contact.index', array_merge(request()->only(['search']), ['status' => $value])) }}"
                @class([
                    'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 font-medium transition-all duration-200',
                    'border-brand-purple-200 bg-brand-purple-50 font-semibold text-brand-purple-800 dark:border-brand-purple-500/30 dark:bg-brand-purple-500/15 dark:text-brand-purple-200' => $status === $value,
                    'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' => $status !== $value,
                ])>
                {{ $label }}
                <span @class([
                    'rounded-full px-1.5 py-0.5 text-[0.68rem] font-semibold tabular-nums',
                    'bg-brand-purple-100 text-brand-purple-800 dark:bg-brand-purple-500/25 dark:text-brand-purple-100' => $status === $value,
                    'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => $status !== $value,
                ])>{{ number_format($tabCounts[$value] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.contact.index') }}" class="mb-4">
        <input type="hidden" name="status" value="{{ $status }}">
        {{-- Search input + button on one row at every width (was stacking
             into two rows on phones) — matches
             admin/activities/index.blade.php's mobile layout. --}}
        <div class="flex gap-2">
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อนักศึกษาหรือรหัสนักศึกษา') }}"
                    class="h-11 w-full rounded-full border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-900 transition placeholder:text-slate-400 focus:border-brand-purple-400 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500"
                >
            </div>

            <button type="submit"
                class="flex h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-brand-purple-700 px-4 text-sm font-semibold text-white transition-all duration-300 active:scale-[0.99] sm:px-6 hover:bg-brand-purple-800">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <span class="hidden sm:inline">{{ __('ค้นหา') }}</span>
            </button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-3xl glass-card">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('นักศึกษา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('หัวข้อ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('อัปเดตล่าสุด') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ผู้ดูแล') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($threads as $thread)
                    <tr
                        onclick="window.location='{{ route('admin.contact.show', $thread) }}'"
                        @class([
                            'group cursor-pointer border-b border-slate-100 transition-colors duration-150 last:border-0 hover:bg-brand-purple-50/50 dark:border-slate-800 dark:hover:bg-slate-800/60',
                        ])
                    >
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                @if ($thread->admin_unread)
                                    <span class="h-2 w-2 shrink-0 rounded-full bg-brand-purple-500"></span>
                                @endif
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-purple-700 text-xs font-semibold text-white shadow-soft">
                                    {{ mb_substr($thread->student->name_thai ?? $thread->student->name, 0, 1) }}
                                </span>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-100">{{ $thread->student->name_thai ?? $thread->student->name }}</p>
                                    <p class="font-mono text-xs text-slate-400 dark:text-slate-500">{{ $thread->student->student_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="min-w-[16rem] max-w-xs whitespace-normal break-words px-4 py-3 font-medium text-slate-700 transition-colors group-hover:text-brand-purple-700 dark:text-slate-300 dark:group-hover:text-brand-purple-400">{{ $thread->subject }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $thread->last_message_at?->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($thread->assignedAdmin)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-purple-50 px-2.5 py-1 text-xs font-medium text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                                    {{ $thread->assignedAdmin->name_thai ?? $thread->assignedAdmin->name }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีใครรับเรื่อง') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$thread->status] }}">
                                {{ $statusLabel[$thread->status] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ไม่มีข้อความในหมวดนี้') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $threads->links() }}</div>
</div>
@endsection
