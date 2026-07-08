@extends('layouts.app')

@section('content')
@php
    $statusLabel = ['auto_approved' => 'ผ่านอัตโนมัติ', 'flagged' => 'ติดธงแดง', 'rejected' => 'ปฏิเสธ'];
    $reasonLabel = [
        'GPS_OUT_OF_BOUNDS' => 'GPS เกินรัศมี',
        'DEVICE_SHARING_SUSPECTED' => 'ต้องสงสัยใช้เครื่องร่วมกัน',
    ];
@endphp

<div
    class="mx-auto max-w-6xl px-4 py-8"
    x-data="{
        selected: [],
        lightboxUrl: null,
        toggleAll(checked) {
            this.selected = checked ? Array.from(document.querySelectorAll('.row-checkbox')).map(el => el.value) : [];
        },
        submitIds(ids, form) {
            if (! ids.length) { alert('กรุณาเลือกอย่างน้อย 1 รายการ'); return; }
            form.querySelector('.ids-container').innerHTML = ids.map(id => `<input type='hidden' name='attendance_ids[]' value='${id}'>`).join('');
            form.submit();
        },
        approveAllValid() {
            const ids = Array.from(document.querySelectorAll('.row-checkbox[data-status=auto_approved]')).map(el => el.value);
            this.submitIds(ids, this.$refs.bulkForm);
        },
        forceBypassSelected() {
            this.submitIds(this.selected, this.$refs.bulkForm);
        },
    }"
>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">แผงควบคุมหน้างาน: {{ $activity->title }}</h1>
            <p class="text-sm text-gray-500">ผู้เช็กชื่อทั้งหมด {{ $attendances->count() }} คน</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.attendance.export', $activity) }}"
                class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
                Export Excel
            </a>
            <a href="{{ route('admin.activities.index') }}" class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
                &larr; กลับ
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <!-- Bulk action bar -->
    <div class="mb-3 flex flex-wrap items-center gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-gray-200">
        <span class="text-sm text-gray-500">เลือกแล้ว <span x-text="selected.length"></span> รายการ</span>
        <button @click="approveAllValid()" type="button"
            class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
            Approve All Valid
        </button>
        <button @click="forceBypassSelected()" type="button"
            class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
            Force Bypass Selected
        </button>
        <span class="text-xs text-gray-400">ใช้ "Force Bypass" เมื่อพบปัญหาหน้างาน เช่น GPS คลาดเคลื่อนทั้งอาคาร</span>
    </div>

    <form method="POST" action="{{ route('admin.attendance.bulk-approve', $activity) }}" x-ref="bulkForm">
        @csrf
        <div class="ids-container"></div>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded text-blue-600"></th>
                    <th class="px-3 py-3 text-left font-medium text-gray-500">รหัสนักศึกษา</th>
                    <th class="px-3 py-3 text-left font-medium text-gray-500">ชื่อ-นามสกุล</th>
                    <th class="px-3 py-3 text-left font-medium text-gray-500">คณะ/สาขา</th>
                    <th class="px-3 py-3 text-left font-medium text-gray-500">ชั้นปี</th>
                    <th class="px-3 py-3 text-left font-medium text-gray-500">เวลาเช็กชื่อ</th>
                    <th class="px-3 py-3 text-left font-medium text-gray-500">ระยะห่าง</th>
                    <th class="px-3 py-3 text-left font-medium text-gray-500">สถานะ</th>
                    <th class="px-3 py-3 text-left font-medium text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($attendances as $att)
                    <tr @class(['bg-red-50' => $att->status === 'flagged'])>
                        <td class="px-3 py-3">
                            <input type="checkbox" class="row-checkbox rounded text-blue-600"
                                value="{{ $att->id }}" data-status="{{ $att->status }}"
                                x-model="selected">
                        </td>
                        <td class="px-3 py-3 font-medium text-gray-900">{{ $att->user->student_id }}</td>
                        <td class="px-3 py-3 text-gray-700">{{ $att->user->name_thai ?? $att->user->name }}</td>
                        <td class="px-3 py-3 text-gray-500">{{ $att->user->faculty?->name_th }} / {{ $att->user->major?->name_th }}</td>
                        <td class="px-3 py-3 text-gray-500">{{ $att->user->current_year }}</td>
                        <td class="px-3 py-3 text-gray-500">{{ $att->checkin_time->format('H:i:s') }}</td>
                        <td class="px-3 py-3 @class(['font-semibold text-red-600' => $att->status === 'flagged', 'text-gray-500' => $att->status !== 'flagged'])">
                            {{ $att->distance_meters }} m
                        </td>
                        <td class="px-3 py-3">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-green-100 text-green-700' => $att->status === 'auto_approved',
                                'bg-red-100 text-red-700' => $att->status === 'flagged',
                                'bg-gray-100 text-gray-600' => $att->status === 'rejected',
                            ])>
                                {{ $statusLabel[$att->status] }}
                            </span>
                            @if ($att->flag_reason)
                                <p class="mt-1 text-xs text-red-500">
                                    {{ collect(explode(',', $att->flag_reason))->map(fn($r) => $reasonLabel[$r] ?? $r)->join(', ') }}
                                </p>
                            @endif
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <button @click="lightboxUrl = '{{ asset('storage/'.$att->photo_path) }}'" type="button" class="text-blue-600 hover:underline">รูปเซลฟี</button>
                            <a href="https://www.google.com/maps?q={{ $att->student_lat }},{{ $att->student_lng }}" target="_blank" class="ml-2 text-blue-600 hover:underline">แผนที่</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">ยังไม่มีผู้เช็กชื่อ</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Selfie lightbox -->
    <div x-show="lightboxUrl" x-cloak @click="lightboxUrl = null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
        <img :src="lightboxUrl" class="max-h-[80vh] max-w-full rounded-xl">
    </div>
</div>
@endsection
