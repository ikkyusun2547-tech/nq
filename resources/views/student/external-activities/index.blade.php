@extends('layouts.dashboard')

@section('content')
@php
    $categoryLabels = [
        'culture' => __('ทำนุบำรุงศิลปวัฒนธรรม'),
        'academic' => __('วิชาการ'),
        'sports' => __('กีฬาและส่งเสริมสุขภาพ'),
        'volunteer' => __('จิตอาสา/บำเพ็ญประโยชน์'),
        'ethics' => __('คุณธรรมจริยธรรม'),
    ];
    $statusDot = ['pending' => 'bg-amber-500', 'approved' => 'bg-brand-green-500', 'rejected' => 'bg-red-500'];
    $statusBadge = [
        'pending' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-brand-green-50 text-brand-green-700',
        'rejected' => 'bg-red-50 text-red-700',
    ];
    $statusLabel = ['pending' => __('รอตรวจสอบ'), 'approved' => __('อนุมัติแล้ว'), 'rejected' => __('ถูกปฏิเสธ')];
    $inputClass = 'w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-green-500 focus:outline-none focus:ring-4 focus:ring-brand-green-500/10';
@endphp

<div class="mx-auto max-w-md" x-data="{ showForm: false }">
    <div class="mb-4 flex items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('เทียบชั่วโมงกิจกรรม') }}</p>
            <h1 class="mt-1 text-lg font-bold text-white">{{ __('คำร้องกิจกรรมภายนอก') }}</h1>
        </div>
        <a href="{{ route('dashboard') }}"
            class="rounded-xl bg-white/10 px-3.5 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
            &larr; {{ __('กลับ') }}
        </a>
    </div>

    <button @click="showForm = ! showForm"
        class="mb-4 mt-4 w-full rounded-2xl bg-brand-green-500 p-4 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg"
        x-text="showForm ? '{{ __('ปิดฟอร์ม') }}' : '+ {{ __('ยื่นคำร้องกิจกรรมภายนอก') }}'">
    </button>

    <div x-show="showForm" x-cloak class="mb-6 rounded-3xl glass-card p-5 shadow-soft-lg">
        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100">
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
                <label class="mb-1.5 block text-sm font-medium text-slate-600">{{ __('ชื่อกิจกรรม') }}</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="{{ $inputClass }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">{{ __('หน่วยงานผู้จัด') }}</label>
                <input type="text" name="organization" value="{{ old('organization') }}" required class="{{ $inputClass }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">{{ __('วันที่จัดกิจกรรม') }}</label>
                <input type="date" name="activity_date" value="{{ old('activity_date') }}" max="{{ date('Y-m-d') }}" required class="{{ $inputClass }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">{{ __('หมวดหมู่กิจกรรม') }}</label>
                <select name="activity_category" required class="{{ $inputClass }}">
                    <option value="">-- {{ __('เลือกหมวดหมู่') }} --</option>
                    @foreach ($categoryLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('activity_category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">{{ __('จำนวนชั่วโมงที่ขอเทียบ') }}</label>
                <input type="number" name="hours_requested" value="{{ old('hours_requested') }}" min="1" max="200" required class="{{ $inputClass }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">{{ __('ภาพหลักฐาน (เกียรติบัตร/ภาพเข้าร่วม, ไม่เกิน 2MB)') }}</label>
                <input type="file" name="proof_image" accept="image/*" required
                    class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-green-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-green-700 hover:file:bg-brand-green-100">
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-green-500 px-4 py-3 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                {{ __('ส่งคำร้อง') }}
            </button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse ($requests as $req)
            <div class="rounded-2xl glass-card p-4 shadow-soft">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $req->title }}</p>
                        <p class="text-xs text-slate-400">{{ $req->organization }} · {{ $req->activity_date->format('d/m/Y') }}</p>
                        <p class="text-xs text-slate-400">{{ $categoryLabels[$req->activity_category] }} · {{ $req->hours_requested }} {{ __('ชม.') }}</p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$req->status] }}">
                        <span class="relative flex h-1.5 w-1.5">
                            <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$req->status]])></span>
                            <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$req->status]])></span>
                        </span>
                        {{ $statusLabel[$req->status] }}
                    </span>
                </div>
                @if ($req->status === 'rejected' && $req->reject_reason)
                    <p class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-xs text-red-600">{{ __('เหตุผล:') }} {{ $req->reject_reason }}</p>
                @endif
            </div>
        @empty
            <p class="py-8 text-center text-sm text-slate-400">{{ __('ยังไม่มีคำร้องกิจกรรมภายนอก') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
@endsection
