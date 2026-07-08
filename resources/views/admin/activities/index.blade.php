@extends('layouts.dashboard')

@section('content')
@php
    $statusBadge = [
        'draft' => 'bg-gray-100 text-gray-600',
        'open' => 'bg-brand-green-100 text-brand-green-700',
        'full' => 'bg-amber-100 text-amber-700',
        'ongoing' => 'bg-brand-purple-100 text-brand-purple-700',
        'closed' => 'bg-gray-100 text-gray-500',
        'cancelled' => 'bg-red-100 text-red-700',
    ];
    $statusLabel = [
        'draft' => 'ร่าง', 'open' => 'เปิดรับสมัคร', 'full' => 'เต็มแล้ว',
        'ongoing' => 'กำลังดำเนินการ', 'closed' => 'ปิดกิจกรรม', 'cancelled' => 'ยกเลิก',
    ];
@endphp

<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900">รายการกิจกรรมทั้งหมด</h1>
        <a href="{{ route('admin.activities.create') }}"
            class="rounded-xl bg-brand-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-green-700">
            + สร้างกิจกรรม
        </a>
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">ชื่อกิจกรรม</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">วันที่จัด</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">ผู้เช็กชื่อแล้ว</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">สถานะ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($activities as $activity)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $activity->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $activity->start_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $activity->attendances_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$activity->status] }}">
                                {{ $statusLabel[$activity->status] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.attendance.qr-display', $activity) }}" class="text-brand-green-600 hover:underline">แสดง QR</a>
                            <a href="{{ route('admin.attendance.index', $activity) }}" class="text-brand-purple-600 hover:underline">หน้างาน</a>
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="text-gray-500 hover:underline">แก้ไข</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">ยังไม่มีกิจกรรม</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
