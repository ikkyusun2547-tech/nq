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
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $statusLabel = ['pending' => __('รอตรวจสอบ'), 'approved' => __('อนุมัติแล้ว'), 'rejected' => __('ถูกปฏิเสธ')];
    $inputClass = fn (string $field) => 'w-full rounded-2xl border bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:outline-none focus:ring-4 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 '
        .($errors->has($field)
            ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70'
            : 'border-slate-200 focus:border-brand-green-500 focus:ring-brand-green-500/10 dark:border-slate-600');
@endphp

<div class="mx-auto max-w-md" x-data="{ showForm: {{ $errors->any() ? 'true' : 'false' }} }">
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
            <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
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
                <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ชื่อกิจกรรม') }}</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="{{ $inputClass('title') }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('หน่วยงานผู้จัด') }}</label>
                <input type="text" name="organization" value="{{ old('organization') }}" required class="{{ $inputClass('organization') }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('วันที่จัดกิจกรรม') }}</label>
                <input type="date" name="activity_date" value="{{ old('activity_date') }}" max="{{ date('Y-m-d') }}" required class="{{ $inputClass('activity_date') }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('หมวดหมู่กิจกรรม') }}</label>
                <x-premium-select
                    name="activity_category" :options="$categoryLabels" :selected="old('activity_category')"
                    placeholder="{{ __('-- เลือกหมวดหมู่ --') }}"
                />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('จำนวนชั่วโมงที่ขอเทียบ') }}</label>
                <input type="number" name="hours_requested" value="{{ old('hours_requested') }}" min="1" max="200" required class="{{ $inputClass('hours_requested') }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ภาพหลักฐาน (เกียรติบัตร/ภาพเข้าร่วม, ไม่เกิน 2MB)') }}</label>
                <div
                    x-data="{ fileName: '', previewUrl: null }"
                    class="relative flex flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl border-2 border-dashed bg-slate-50/60 p-5 text-center transition-colors duration-200 dark:bg-slate-800/40 @error('proof_image') border-red-400 dark:border-red-500/70 @else border-slate-200 dark:border-slate-600 @enderror"
                    :class="previewUrl && 'border-brand-green-300 bg-brand-green-50/40 dark:border-brand-green-500/40 dark:bg-brand-green-500/5'"
                >
                    <template x-if="! previewUrl">
                        <div class="flex flex-col items-center gap-1.5 text-slate-400 dark:text-slate-500">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                            <p class="text-xs font-medium">{{ __('คลิกเพื่อเลือกรูปภาพ') }}</p>
                            <p class="text-[0.68rem] text-slate-350 dark:text-slate-600">PNG, JPG {{ __('ไม่เกิน') }} 2MB</p>
                        </div>
                    </template>
                    <template x-if="previewUrl">
                        <img :src="previewUrl" class="max-h-44 rounded-xl object-contain shadow-soft">
                    </template>
                    <p class="max-w-full truncate text-xs font-medium text-slate-600 dark:text-slate-300" x-show="fileName" x-text="fileName"></p>

                    <input
                        type="file" name="proof_image" accept="image/*" required
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        @change="
                            const file = $event.target.files[0];
                            fileName = file ? file.name : '';
                            previewUrl = file ? URL.createObjectURL(file) : null;
                        "
                    >
                </div>
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-green-500 px-4 py-3 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                {{ __('ส่งคำร้อง') }}
            </button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse ($requests as $req)
            <div class="rounded-2xl glass-card p-4 shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:shadow-soft-lg">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $req->title }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ $req->organization }} · {{ $req->activity_date->format('d/m/Y') }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">
                            {{ $categoryLabels[$req->activity_category] }} ·
                            @if ($req->status === 'approved' && $req->hours_approved !== null && $req->hours_approved != $req->hours_requested)
                                <span class="text-slate-400 line-through">{{ $req->hours_requested }}</span> <span class="font-medium text-brand-green-600 dark:text-brand-green-400">{{ $req->hours_credited }}</span> {{ __('ชม.') }}
                            @else
                                {{ $req->hours_requested }} {{ __('ชม.') }}
                            @endif
                        </p>
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
                    <p class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-xs text-red-600 dark:bg-red-500/10 dark:text-red-400">{{ __('เหตุผล:') }} {{ $req->reject_reason }}</p>
                @endif
                @if ($req->admin_comment)
                    <p class="mt-2 rounded-xl bg-brand-purple-50 px-3 py-2 text-xs text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">{{ __('ความเห็นกองพัฒนานักศึกษา:') }} {{ $req->admin_comment }}</p>
                @endif
            </div>
        @empty
            <p class="py-8 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีคำร้องกิจกรรมภายนอก') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
@endsection
