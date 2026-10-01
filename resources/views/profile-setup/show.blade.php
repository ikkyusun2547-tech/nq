@extends('layouts.app')

@section('content')
@php
    $isEdit = $user->hasCompletedProfile();
    $features = [
        [__('เช็คชื่อด้วย QR'), 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z'],
        [__('ดูชั่วโมงสะสมได้ทันที'), 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
        [__('ยื่นคำร้องในระบบเดียว'), 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
    ];
    $input = fn (string $field) => 'h-11 w-full rounded-xl border bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 transition focus:outline-none focus:ring-4 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 '
        .($errors->has($field)
            ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-500/70'
            : 'border-slate-200 focus:border-brand-purple-500 focus:ring-brand-purple-500/10 dark:border-slate-700');
    // Tappable option chips (radio inside a label), shared by prefix / year / program.
    $chip = 'flex h-11 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 transition-colors hover:border-brand-purple-300 has-[:checked]:border-brand-purple-600 has-[:checked]:bg-brand-purple-600 has-[:checked]:font-semibold has-[:checked]:text-white has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-purple-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:has-[:checked]:border-brand-purple-500 dark:has-[:checked]:bg-brand-purple-600';
    $label = 'mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300';
    $section = 'rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-7';
    $prefix = old('title_prefix', $namePrefix);
    $yearLevel = (string) old('year_level', $user->year_level);
    $programType = old('program_type', $user->program_type);
@endphp

<div class="min-h-dvh bg-slate-50 dark:bg-slate-950"
    x-data="{
        facultyId: '{{ old('faculty_id', $user->faculty_id) }}',
        majorId: '{{ old('major_id', $user->major_id) }}',
        majors: [],
        loadingMajors: false,
        get majorOptions() {
            return this.majors.map(m => ({ heading: false, value: String(m.id), label: `${m.name_th} (${m.degree_abbr ?? '-'})` }));
        },
        async loadMajors() {
            if (! this.facultyId) { this.majors = []; return; }
            this.loadingMajors = true;
            const res = await fetch(`/api/faculties/${this.facultyId}/majors`);
            this.majors = await res.json();
            this.loadingMajors = false;
        },
    }"
    x-init="loadMajors()"
>
    {{-- Top bar --}}
    <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
        <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-4 sm:px-6">
            <span class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 object-contain">
                <span class="leading-tight">
                    <span class="block font-display text-[1.05rem] font-semibold text-slate-900 dark:text-white">SRRU Check</span>
                    <span class="block text-[0.7rem] text-slate-500 dark:text-slate-400">{{ __('มหาวิทยาลัยราชภัฏสุรินทร์') }}</span>
                </span>
            </span>
            <div class="flex items-center gap-1.5">
                @include('partials.theme-toggle')
                @include('partials.locale-switch')
            </div>
        </div>
    </header>

    <div class="mx-auto max-w-3xl px-4 pb-32 pt-8 sm:px-6 sm:pt-10">
        {{-- Heading --}}
        <p class="text-sm font-semibold text-brand-purple-700 dark:text-brand-purple-300">{{ $isEdit ? __('แก้ไขข้อมูลโปรไฟล์') : __('ขั้นตอนสุดท้ายก่อนใช้งาน') }}</p>
        <h1 class="mt-1 font-display text-3xl text-slate-900 dark:text-white sm:text-4xl">
            {{ $isEdit ? __('แก้ไขข้อมูลนักศึกษา') : __('ยินดีต้อนรับสู่ SRRU Check') }}
        </h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            @if ($isEdit)
                {{ __('ปรับข้อมูลให้ตรงกับปัจจุบัน ระบบใช้ข้อมูลนี้คำนวณสิทธิ์เข้าร่วมกิจกรรม') }}
            @else
                {{ __('กรอกข้อมูลอีกนิดเดียว เพื่อเริ่มเช็คชื่อและสะสมชั่วโมงกิจกรรม') }}
            @endif

        </p>

        {{-- Which Google account is signed in, so a student on a shared device notices a wrong account. --}}
        <div class="mt-4 inline-flex max-w-full items-center gap-3 rounded-2xl border border-slate-200 bg-white py-2.5 pl-3 pr-4 dark:border-slate-800 dark:bg-slate-900">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            </span>
            <span class="min-w-0">
                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ __('เข้าสู่ระบบด้วยอีเมล') }}</span>
                <span class="block truncate text-[0.95rem] font-semibold text-slate-900 dark:text-white">{{ $user->email }}</span>
            </span>
        </div>

        @unless ($isEdit)
            <ul class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-3">
                @foreach ($features as [$text, $icon])
                    <li class="flex items-center gap-2.5 whitespace-nowrap rounded-2xl bg-white px-3.5 py-3 text-sm text-slate-700 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </span>
                        {{ $text }}
                    </li>
                @endforeach
            </ul>
        @endunless

        @if ($errors->any())
            <div class="mt-6 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="profile-form" method="POST" action="{{ route('profile-setup.store') }}" class="mt-6 space-y-4">
            @csrf

            {{-- 1. Personal --}}
            <section class="{{ $section }}">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-purple-700 text-sm font-semibold text-white">1</span>
                    <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('ข้อมูลส่วนตัว') }}</h2>
                </div>

                <div class="space-y-4">
                    <fieldset>
                        <legend class="{{ $label }}">{{ __('คำนำหน้าชื่อ') }}</legend>
                        <div class="grid grid-cols-3 gap-2 @error('title_prefix') rounded-xl ring-2 ring-red-400 @enderror">
                            @foreach (['นาย', 'นาง', 'นางสาว'] as $option)
                                <label class="{{ $chip }}">
                                    <input type="radio" name="title_prefix" value="{{ $option }}" required class="sr-only" @checked($prefix === $option)>
                                    {{ $option }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="{{ $label }}">{{ __('ชื่อ') }}</label>
                            <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $firstName) }}" required autocomplete="given-name"
                                class="{{ $input('first_name') }}" placeholder="{{ __('กรอกชื่อ') }}">
                        </div>
                        <div>
                            <label for="last_name" class="{{ $label }}">{{ __('นามสกุล') }}</label>
                            <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $lastName) }}" required autocomplete="family-name"
                                class="{{ $input('last_name') }}" placeholder="{{ __('กรอกนามสกุล') }}">
                        </div>
                    </div>

                    <div>
                        <label for="student_id" class="{{ $label }}">{{ __('รหัสนักศึกษา') }}</label>
                        <input id="student_id" type="text" name="student_id" value="{{ old('student_id', $user->student_id) }}" required
                            inputmode="numeric" pattern="\d{11}" maxlength="11"
                            class="{{ $input('student_id') }} font-mono tracking-wider" placeholder="{{ __('รหัส 11 หลัก') }}">
                    </div>
                </div>
            </section>

            {{-- 2. Academic --}}
            <section class="{{ $section }}">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-purple-700 text-sm font-semibold text-white">2</span>
                    <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('ข้อมูลการศึกษา') }}</h2>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="{{ $label }}">{{ __('คณะ') }}</label>
                        <x-premium-select
                            name="faculty_id" required
                            :options="$faculties->pluck('name_th', 'id')->all()"
                            :selected="old('faculty_id', $user->faculty_id)"
                            placeholder="{{ __('เลือกคณะ') }}"
                            liveSelected="facultyId"
                            @change="majorId = ''; loadMajors()"
                        />
                    </div>

                    <div>
                        <label class="{{ $label }}">{{ __('สาขาวิชา') }}</label>
                        <x-premium-select
                            name="major_id" required
                            :selected="old('major_id', $user->major_id)"
                            placeholder="{{ __('เลือกคณะก่อน แล้วเลือกสาขาวิชา') }}"
                            liveOptions="majorOptions"
                            liveSelected="majorId"
                            disabled="! facultyId || loadingMajors"
                        />
                    </div>

                    <fieldset>
                        <legend class="{{ $label }}">{{ __('ชั้นปีปัจจุบัน') }}</legend>
                        <div class="grid grid-cols-4 gap-2 @error('year_level') rounded-xl ring-2 ring-red-400 @enderror">
                            @foreach ([1, 2, 3, 4] as $year)
                                <label class="{{ $chip }}">
                                    <input type="radio" name="year_level" value="{{ $year }}" required class="sr-only" @checked($yearLevel === (string) $year)>
                                    {{ __('ปี :year', ['year' => $year]) }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="enrollment_year" class="{{ $label }}">{{ __('ปีที่เข้าศึกษา (พ.ศ.)') }}</label>
                            <input id="enrollment_year" type="number" name="enrollment_year" value="{{ old('enrollment_year', $user->enrollment_year) }}" required
                                min="2540" max="{{ date('Y') + 543 }}"
                                class="{{ $input('enrollment_year') }}" placeholder="{{ __('เช่น :year', ['year' => date('Y') + 543]) }}">
                        </div>
                        <fieldset>
                            <legend class="{{ $label }}">{{ __('ประเภทหลักสูตร') }}</legend>
                            <div class="grid grid-cols-2 gap-2 @error('program_type') rounded-xl ring-2 ring-red-400 @enderror">
                                <label class="{{ $chip }}">
                                    <input type="radio" name="program_type" value="normal" required class="sr-only" @checked($programType === 'normal')>
                                    {{ __('ภาคปกติ') }}
                                </label>
                                <label class="{{ $chip }}">
                                    <input type="radio" name="program_type" value="special" required class="sr-only" @checked($programType === 'special')>
                                    {{ __('กศ.บป.') }}
                                </label>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </section>

            <p class="flex items-center justify-center gap-1.5 pt-2 text-center text-xs text-slate-500 dark:text-slate-400">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                {{ __('ข้อมูลของคุณจะถูกเก็บเป็นความลับตามนโยบายของมหาวิทยาลัย') }}
            </p>
        </form>
    </div>

    {{-- Sticky save bar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 dark:border-slate-800 dark:bg-slate-950/95">
        <div class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:px-6">
            @if ($isEdit)
                <a href="{{ route('profile.show') }}" class="flex h-12 flex-1 items-center justify-center rounded-2xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900 sm:flex-none sm:px-6">
                    {{ __('ยกเลิก') }}
                </a>
            @endif
            <button type="submit" form="profile-form"
                class="flex h-12 flex-[2] items-center justify-center gap-2 rounded-2xl bg-brand-purple-700 text-[0.95rem] font-semibold text-white transition-colors hover:bg-brand-purple-800 sm:ml-auto sm:flex-none sm:px-8">
                {{ $isEdit ? __('บันทึกการเปลี่ยนแปลง') : __('บันทึกและเริ่มใช้งาน') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </button>
        </div>
    </div>
</div>
@endsection
