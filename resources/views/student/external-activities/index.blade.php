@extends('layouts.dashboard')

@section('content')
@php
    $categoryLabels = [
        'culture' => 'ทำนุบำรุงศิลปวัฒนธรรม',
        'academic' => 'วิชาการ',
        'sports' => 'กีฬาและส่งเสริมสุขภาพ',
        'volunteer' => 'จิตอาสา/บำเพ็ญประโยชน์',
        'ethics' => 'คุณธรรมจริยธรรม',
    ];
    $statusBadge = [
        'pending' => 'bg-amber-100 text-amber-700',
        'approved' => 'bg-brand-green-100 text-brand-green-700',
        'rejected' => 'bg-red-100 text-red-700',
    ];
    $statusLabel = ['pending' => 'รอตรวจสอบ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ถูกปฏิเสธ'];
@endphp

<div class="mx-auto max-w-md" x-data="{ showForm: false }">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">คำร้องกิจกรรมภายนอก</h1>
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600">&larr; กลับ</a>
    </div>

    <button @click="showForm = ! showForm"
        class="mb-4 w-full rounded-2xl bg-brand-green-600 p-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-green-700"
        x-text="showForm ? 'ปิดฟอร์ม' : '+ ยื่นคำร้องกิจกรรมภายนอก'">
    </button>

    <div x-show="showForm" x-cloak class="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('external-activities.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อกิจกรรม</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:border-brand-green-500 focus:ring-brand-green-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">หน่วยงานผู้จัด</label>
                <input type="text" name="organization" value="{{ old('organization') }}" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:border-brand-green-500 focus:ring-brand-green-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">วันที่จัดกิจกรรม</label>
                <input type="date" name="activity_date" value="{{ old('activity_date') }}" max="{{ date('Y-m-d') }}" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:border-brand-green-500 focus:ring-brand-green-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">หมวดหมู่กิจกรรม</label>
                <select name="activity_category" required class="w-full rounded-xl border-gray-300 text-sm focus:border-brand-green-500 focus:ring-brand-green-500">
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    @foreach ($categoryLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('activity_category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">จำนวนชั่วโมงที่ขอเทียบ</label>
                <input type="number" name="hours_requested" value="{{ old('hours_requested') }}" min="1" max="200" required
                    class="w-full rounded-xl border-gray-300 text-sm focus:border-brand-green-500 focus:ring-brand-green-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">ภาพหลักฐาน (เกียรติบัตร/ภาพเข้าร่วม, ไม่เกิน 2MB)</label>
                <input type="file" name="proof_image" accept="image/*" required
                    class="w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-green-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-green-700 hover:file:bg-brand-green-100">
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-green-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-green-700">
                ส่งคำร้อง
            </button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse ($requests as $req)
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $req->title }}</p>
                        <p class="text-xs text-gray-400">{{ $req->organization }} · {{ $req->activity_date->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $categoryLabels[$req->activity_category] }} · {{ $req->hours_requested }} ชม.</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$req->status] }}">
                        {{ $statusLabel[$req->status] }}
                    </span>
                </div>
                @if ($req->status === 'rejected' && $req->reject_reason)
                    <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600">เหตุผล: {{ $req->reject_reason }}</p>
                @endif
            </div>
        @empty
            <p class="py-8 text-center text-sm text-gray-400">ยังไม่มีคำร้องกิจกรรมภายนอก</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
@endsection
