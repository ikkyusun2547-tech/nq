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

<div class="mx-auto max-w-5xl">
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

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อกิจกรรม') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('วันที่จัด') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ปีการศึกษา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ผู้เช็กชื่อแล้ว') }}</th>
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
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $activity->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $activity->start_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            @if ($activity->academic_year)
                                {{ $activity->academic_year }} · {{ $semesterShort[$activity->semester] ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $activity->attendances_count }}</td>
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
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีกิจกรรม') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
