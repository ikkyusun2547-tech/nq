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
        'draft' => 'bg-slate-100 text-slate-600',
        'open' => 'bg-brand-green-50 text-brand-green-700',
        'full' => 'bg-amber-50 text-amber-700',
        'ongoing' => 'bg-brand-purple-50 text-brand-purple-700',
        'closed' => 'bg-slate-100 text-slate-500',
        'cancelled' => 'bg-red-50 text-red-700',
    ];
    $statusLabel = [
        'draft' => 'ร่าง', 'open' => 'เปิดรับสมัคร', 'full' => 'เต็มแล้ว',
        'ongoing' => 'กำลังดำเนินการ', 'closed' => 'ปิดกิจกรรม', 'cancelled' => 'ยกเลิก',
    ];
@endphp

<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">กองพัฒนานักศึกษา</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">รายการกิจกรรมทั้งหมด</h1>
        </div>
        <a href="{{ route('admin.activities.create') }}"
            class="rounded-xl bg-brand-green-500 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
            + สร้างกิจกรรม
        </a>
    </div>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">ชื่อกิจกรรม</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">วันที่จัด</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">ผู้เช็กชื่อแล้ว</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">สถานะ</th>
                    <th class="whitespace-nowrap px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr @class([
                        'border-b border-slate-100 transition-colors last:border-0 hover:bg-brand-purple-50/40',
                        'bg-white' => $loop->even,
                        'bg-slate-50/50' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $activity->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $activity->start_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $activity->attendances_count }}</td>
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
                            <a href="{{ route('admin.attendance.qr-display', $activity) }}" class="font-medium text-brand-green-600 transition-colors hover:text-brand-green-800">แสดง QR</a>
                            <a href="{{ route('admin.attendance.index', $activity) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800">หน้างาน</a>
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="font-medium text-slate-500 transition-colors hover:text-slate-800">แก้ไข</a>
                            <form
                                method="POST" action="{{ route('admin.activities.destroy', $activity) }}"
                                class="inline"
                                onsubmit="return confirm('ยืนยันลบกิจกรรม &quot;{{ addslashes($activity->title) }}&quot;? การลบไม่สามารถย้อนกลับได้')"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-medium text-red-500 transition-colors hover:text-red-700">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">ยังไม่มีกิจกรรม</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
