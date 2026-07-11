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

    $positionLabels = [
        'student_council_president' => __('นายกองค์การบริหารนักศึกษา').' (60 '.__('ชม.').')',
        'student_club_president' => __('นายกสโมสรนักศึกษา').' (60 '.__('ชม.').')',
        'student_parliament_president' => __('ประธานสภานักศึกษา').' (60 '.__('ชม.').')',
        'club_president' => __('ประธานชมรม').' (50 '.__('ชม.').')',
        'dormitory_president' => __('ประธานหอพักมหาวิทยาลัย').' (50 '.__('ชม.').')',
        'class_leader' => __('หัวหน้าหมู่เรียน').' (50 '.__('ชม.').')',
        'class_representative' => __('ตัวแทนหมู่เรียน').' (50 '.__('ชม.').')',
    ];
    $positionLabelsPlain = [
        'student_council_president' => __('นายกองค์การบริหารนักศึกษา'),
        'student_club_president' => __('นายกสโมสรนักศึกษา'),
        'student_parliament_president' => __('ประธานสภานักศึกษา'),
        'club_president' => __('ประธานชมรม'),
        'dormitory_president' => __('ประธานหอพักมหาวิทยาลัย'),
        'class_leader' => __('หัวหน้าหมู่เรียน'),
        'class_representative' => __('ตัวแทนหมู่เรียน'),
    ];
@endphp

<div
    class="mx-auto max-w-md"
    x-data="{
        tab: '{{ $activeTab }}',
        showExternalForm: {{ $errors->any() ? 'true' : 'false' }},
        showCreditForm: {{ $errors->any() ? 'true' : 'false' }},
        creditPosition: '{{ old('position', '') }}',
        positionHours: @js(\App\Models\CreditTransferRequest::POSITION_HOURS),
    }"
    @change="if ($event.target.name === 'position') creditPosition = $event.target.value"
>
    <x-brand-header eyebrow="{{ __('เทียบชั่วโมงกิจกรรม') }}" :title="__('ขอชั่วโมงกิจกรรม')" :back="route('dashboard')" />

    <!-- Pill tab switch -->
    <div class="mb-4 flex gap-1 rounded-2xl bg-slate-100 p-1 dark:bg-slate-800">
        <button type="button" @click="tab = 'external'"
            class="flex-1 rounded-xl py-2 text-sm font-semibold transition-all duration-200"
            :class="tab === 'external' ? 'bg-white text-brand-purple-700 shadow-soft dark:bg-slate-900 dark:text-brand-purple-400' : 'text-slate-500 dark:text-slate-400'">
            {{ __('กิจกรรมภายนอก') }}
        </button>
        <button type="button" @click="tab = 'credit'"
            class="flex-1 rounded-xl py-2 text-sm font-semibold transition-all duration-200"
            :class="tab === 'credit' ? 'bg-white text-brand-purple-700 shadow-soft dark:bg-slate-900 dark:text-brand-purple-400' : 'text-slate-500 dark:text-slate-400'">
            {{ __('เทียบโอนตำแหน่ง') }}
        </button>
    </div>

    <!-- External activities tab -->
    <div x-show="tab === 'external'" x-cloak>
        <button @click="showExternalForm = ! showExternalForm"
            class="mb-4 w-full rounded-2xl bg-brand-green-500 p-4 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg"
            x-text="showExternalForm ? '{{ __('ปิดฟอร์ม') }}' : '+ {{ __('ยื่นคำร้องกิจกรรมภายนอก') }}'">
        </button>

        <div x-show="showExternalForm" x-cloak class="mb-6 rounded-3xl glass-card p-5 shadow-soft-lg">
            <p class="mb-4 rounded-xl bg-brand-purple-50 px-4 py-3 text-xs text-brand-purple-700 shadow-soft ring-1 ring-brand-purple-100 dark:bg-brand-purple-500/10 dark:text-brand-purple-400 dark:ring-brand-purple-500/20">
                {{ __('เหลือโควตาคำร้องกิจกรรมภายนอก') }}
                <span class="font-semibold">{{ $hoursRemaining }}</span> / {{ \App\Models\ExternalActivityRequest::ANNUAL_HOUR_CAP }}
                {{ __('ชั่วโมง สำหรับปีการศึกษา') }} {{ $currentAcademicYear }}
            </p>

            @if ($errors->any() && $activeTab === 'external')
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
                <input type="hidden" name="_tab" value="external">
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
                    <input type="number" name="hours_requested" value="{{ old('hours_requested') }}" min="1" max="{{ $hoursRemaining }}" required class="{{ $inputClass('hours_requested') }}">
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
            @forelse ($externalRequests as $req)
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

        <div class="mt-4">{{ $externalRequests->links() }}</div>
    </div>

    <!-- Credit transfer tab -->
    <div x-show="tab === 'credit'" x-cloak>
        <button @click="showCreditForm = ! showCreditForm"
            class="mb-4 w-full rounded-2xl bg-brand-green-500 p-4 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg"
            x-text="showCreditForm ? '{{ __('ปิดฟอร์ม') }}' : '+ {{ __('ยื่นคำร้องเทียบโอนชั่วโมง') }}'">
        </button>

        <div x-show="showCreditForm" x-cloak class="mb-6 rounded-3xl glass-card p-5 shadow-soft-lg">
            <p class="mb-4 rounded-xl bg-brand-purple-50 px-4 py-3 text-xs text-brand-purple-700 shadow-soft ring-1 ring-brand-purple-100 dark:bg-brand-purple-500/10 dark:text-brand-purple-400 dark:ring-brand-purple-500/20">
                {{ __('สำหรับนักศึกษาที่ดำรงตำแหน่งผู้นำนักศึกษาตามข้อ 14 ของประกาศฯ ขอเทียบโอนชั่วโมงกิจกรรมได้ 1 ครั้งต่อปีการศึกษา') }}
            </p>

            @if ($errors->any() && $activeTab === 'credit')
                <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('credit-transfers.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="_tab" value="credit">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ตำแหน่ง') }}</label>
                    <x-premium-select
                        name="position" :options="$positionLabels" :selected="old('position')"
                        placeholder="{{ __('-- เลือกตำแหน่ง --') }}"
                    />
                    <p class="mt-1.5 text-xs text-brand-green-600 dark:text-brand-green-400" x-show="creditPosition" x-text="'{{ __('ชั่วโมงที่จะได้รับ:') }} ' + (positionHours[creditPosition] || 0) + ' {{ __('ชม.') }}'"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('ปีการศึกษาที่ดำรงตำแหน่ง') }}</label>
                    <x-premium-select
                        name="academic_year" :options="$academicYearOptions" :selected="old('academic_year')"
                        placeholder="{{ __('-- เลือกปีการศึกษา --') }}"
                    />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('หลักฐานการดำรงตำแหน่ง (คำสั่งแต่งตั้ง/หนังสือรับรอง, ไม่เกิน 2MB)') }}</label>
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
            @forelse ($creditRequests as $req)
                <div class="rounded-2xl glass-card p-4 shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:shadow-soft-lg">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $positionLabelsPlain[$req->position] }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('ปีการศึกษา') }} {{ $req->academic_year }}
                                @if ($req->activity_category)
                                    · {{ $categoryLabels[$req->activity_category] }}
                                @endif
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">
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
                <p class="py-8 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีคำร้องเทียบโอนชั่วโมง') }}</p>
            @endforelse
        </div>

        <div class="mt-4">{{ $creditRequests->links() }}</div>
    </div>
</div>
@endsection
