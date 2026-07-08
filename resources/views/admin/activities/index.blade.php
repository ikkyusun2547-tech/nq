@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">รายการกิจกรรมทั้งหมด</h1>
        <a href="{{ route('admin.activities.create') }}"
            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + สร้างกิจกรรม
        </a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
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
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $activity->title }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $activity->start_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $activity->attendances_count }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $activity->status }}</td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.attendance.qr-display', $activity) }}" class="text-green-600 hover:underline">แสดง QR</a>
                            <a href="{{ route('admin.attendance.index', $activity) }}" class="text-indigo-600 hover:underline">หน้างาน</a>
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="text-blue-600 hover:underline">แก้ไข</a>
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
