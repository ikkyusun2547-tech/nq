@extends('layouts.dashboard')

@section('content')
@php
    $statusLabel = ['auto_approved' => 'ผ่านอัตโนมัติ', 'flagged' => 'ติดธงแดง', 'rejected' => 'ปฏิเสธ'];
    $reasonLabel = [
        'GPS_OUT_OF_BOUNDS' => 'GPS เกินรัศมี',
        'DEVICE_SHARING_SUSPECTED' => 'ต้องสงสัยใช้เครื่องร่วมกัน',
    ];
    $badgeDot = [
        'auto_approved' => 'bg-brand-green-500',
        'flagged' => 'bg-red-500',
        'rejected' => 'bg-slate-400',
    ];
@endphp

<div
    class="mx-auto max-w-6xl"
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
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 text-white shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">Live Event Control</p>
            <h1 class="mt-1 text-xl font-bold sm:text-2xl">{{ $activity->title }}</h1>
            <p class="mt-1 text-sm text-violet-100/70">ผู้เช็กชื่อทั้งหมด {{ $attendances->count() }} คน</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.attendance.export', $activity) }}"
                class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
                Export Excel
            </a>
            <a href="{{ route('admin.activities.index') }}"
                class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
                &larr; กลับ
            </a>
        </div>
    </div>

    <!-- Bulk action bar -->
    <div class="mb-3 mt-4 flex flex-wrap items-center gap-3 rounded-2xl glass-card p-4 shadow-soft">
        <span class="text-sm text-slate-500">เลือกแล้ว <span class="font-semibold text-brand-purple-700" x-text="selected.length"></span> รายการ</span>
        <button @click="approveAllValid()" type="button"
            class="rounded-xl bg-brand-green-500 px-4 py-2 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
            Approve All Valid
        </button>
        <button @click="forceBypassSelected()" type="button"
            class="rounded-xl bg-red-500 px-4 py-2 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-lg">
            Force Bypass Selected
        </button>
        <span class="text-xs text-slate-400">ใช้ "Force Bypass" เมื่อพบปัญหาหน้างาน เช่น GPS คลาดเคลื่อนทั้งอาคาร</span>
    </div>

    <form method="POST" action="{{ route('admin.attendance.bulk-approve', $activity) }}" x-ref="bulkForm">
        @csrf
        <div class="ids-container"></div>
    </form>

    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100">
                    <th class="whitespace-nowrap px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500"></th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">รหัสนักศึกษา</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">ชื่อ-นามสกุล</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">คณะ/สาขา</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">ชั้นปี</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">เวลาเช็กชื่อ</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">ระยะห่าง</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">สถานะ</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $att)
                    <tr @class([
                        'border-b border-slate-100 transition-colors last:border-0 hover:bg-brand-purple-50/40',
                        'bg-red-50/60' => $att->status === 'flagged',
                        'bg-white' => $att->status !== 'flagged' && $loop->even,
                        'bg-slate-50/50' => $att->status !== 'flagged' && $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-3 py-3">
                            <input type="checkbox" class="row-checkbox rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500"
                                value="{{ $att->id }}" data-status="{{ $att->status }}"
                                x-model="selected">
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-900">{{ $att->user->student_id }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-700">{{ $att->user->name_thai ?? $att->user->name }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $att->user->faculty?->name_th }} / {{ $att->user->major?->name_th }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $att->user->current_year }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $att->checkin_time->format('H:i:s') }}</td>
                        <td class="whitespace-nowrap px-3 py-3 @class(['font-semibold text-red-600' => $att->status === 'flagged', 'text-slate-500' => $att->status !== 'flagged'])">
                            {{ $att->distance_meters }} m
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-brand-green-50 text-brand-green-700' => $att->status === 'auto_approved',
                                'bg-red-50 text-red-700' => $att->status === 'flagged',
                                'bg-slate-100 text-slate-600' => $att->status === 'rejected',
                            ])>
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $badgeDot[$att->status]])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $badgeDot[$att->status]])></span>
                                </span>
                                {{ $statusLabel[$att->status] }}
                            </span>
                            @if ($att->flag_reason)
                                <p class="mt-1 whitespace-normal text-xs text-red-500">
                                    {{ collect(explode(',', $att->flag_reason))->map(fn($r) => $reasonLabel[$r] ?? $r)->join(', ') }}
                                </p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">
                            <button @click="lightboxUrl = '{{ asset('storage/'.$att->photo_path) }}'" type="button" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800">รูปเซลฟี</button>
                            <a href="https://www.google.com/maps?q={{ $att->student_lat }},{{ $att->student_lng }}" target="_blank" class="ml-2 font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800">แผนที่</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">ยังไม่มีผู้เช็กชื่อ</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Selfie lightbox -->
    <div x-show="lightboxUrl" x-cloak @click="lightboxUrl = null" class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm">
        <img :src="lightboxUrl" class="max-h-[80vh] max-w-full rounded-2xl shadow-soft-lg">
    </div>
</div>
@endsection
