@extends('layouts.dashboard')

@section('content')
@php
    $statusDot = [
        'draft' => 'bg-slate-400',
        'open' => 'bg-brand-green-500',
        'full' => 'bg-amber-500',
        'ongoing' => 'bg-brand-purple-500',
        'closed' => 'bg-slate-400',
        'cancelled' => 'bg-red-500',
    ];
    $statusBadge = [
        'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        'open' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'full' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'ongoing' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400',
        'closed' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
        'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $statusLabel = [
        'draft' => __('ร่าง'), 'open' => __('เปิดรับสมัคร'), 'full' => __('เต็มแล้ว'),
        'ongoing' => __('กำลังดำเนินการ'), 'closed' => __('ปิดกิจกรรม'), 'cancelled' => __('ถูกยกเลิก'),
    ];
    $semesterShort = ['1' => __('เทอม 1'), '2' => __('เทอม 2'), '3' => __('ฤดูร้อน')];
@endphp

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('กองพัฒนานักศึกษา') }}</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ __('รายการกิจกรรมทั้งหมด') }}</h1>
        </div>
        <a href="{{ route('admin.activities.create') }}"
            class="rounded-xl bg-brand-green-500 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
            + {{ __('สร้างกิจกรรม') }}
        </a>
    </div>

    <form method="GET" action="{{ route('admin.activities.index') }}" class="mt-4 space-y-3">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อกิจกรรม') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>

            <button type="submit"
                class="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-6 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:from-brand-purple-500 hover:to-brand-purple-400 hover:shadow-lg active:scale-[0.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                {{ __('ค้นหา') }}
            </button>
        </div>

        @php
            $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => $y])->all();
        @endphp

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-premium-select
                name="status" :options="$statusLabel" :selected="request('status')"
                placeholder="{{ __('-- ทุกสถานะ --') }}" autosubmit
            />

            <x-premium-select
                name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
                placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit
            />

            <x-premium-select
                name="semester" :options="$semesterShort" :selected="request('semester')"
                placeholder="{{ __('-- ทุกภาคเรียน --') }}" autosubmit
            />
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('รหัสกิจกรรม') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อกิจกรรม') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('วันที่จัด') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ปีการศึกษา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ผู้เช็คชื่อแล้ว') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr @class([
                        'border-b border-slate-100 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:border-slate-800 dark:hover:bg-slate-800/60',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-brand-purple-600 dark:text-brand-purple-400">{{ $activity->activity_code ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $activity->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $activity->start_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            @if ($activity->academic_year)
                                {{ $activity->academic_year }} · {{ $semesterShort[$activity->semester] ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            <div class="flex items-center gap-2">
                                <span>{{ $activity->attendances_count }}</span>
                                @if ($activity->flagged_count > 0)
                                    <a href="{{ route('admin.attendance.index', $activity) }}"
                                        class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 ring-1 ring-red-100 transition-colors hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/20"
                                        title="{{ __('มีรายการรอตรวจสอบ') }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                        {{ $activity->flagged_count }} {{ __('รอตรวจสอบ') }}
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$activity->status] }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$activity->status]])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$activity->status]])></span>
                                </span>
                                {{ $statusLabel[$activity->status] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.attendance.qr-display', $activity) }}" class="font-medium text-brand-green-600 transition-colors hover:text-brand-green-800 dark:text-brand-green-400 dark:hover:text-brand-green-300">{{ __('แสดง QR') }}</a>
                            <a href="{{ route('admin.attendance.index', $activity) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('หน้างาน') }}</a>
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">{{ __('แก้ไข') }}</a>
                            <form method="POST" action="{{ route('admin.activities.duplicate', $activity) }}" class="inline">
                                @csrf
                                <button type="submit" class="font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">{{ __('คัดลอก') }}</button>
                            </form>
                            <form
                                method="POST" action="{{ route('admin.activities.destroy', $activity) }}"
                                class="inline"
                                onsubmit="return confirm('{{ __('ยืนยันลบกิจกรรม \":title\"? การลบไม่สามารถย้อนกลับได้', ['title' => addslashes($activity->title)]) }}')"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-medium text-red-500 transition-colors hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">{{ __('ลบ') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีกิจกรรม') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
