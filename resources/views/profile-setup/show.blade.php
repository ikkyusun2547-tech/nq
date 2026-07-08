@extends('layouts.app')

@section('content')
<div
    class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 brand-gradient"
    x-data="{
        facultyId: '{{ old('faculty_id') }}',
        majors: [],
        loadingMajors: false,
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
    <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -right-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>

    <div class="relative w-full max-w-xl">
        <div class="mb-6 flex items-center gap-3.5 px-1">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-sm font-bold tracking-wide text-white ring-1 ring-white/30 backdrop-blur">SR</span>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-white/70">ขั้นตอนสุดท้ายก่อนใช้งาน</p>
                <h1 class="text-lg font-semibold text-white sm:text-xl">กรอกข้อมูลโปรไฟล์นักศึกษา</h1>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm ring-1 ring-red-100">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile-setup.store') }}"
            class="space-y-7 rounded-3xl bg-white/95 p-6 shadow-2xl ring-1 ring-black/5 backdrop-blur sm:p-8">
            @csrf

            <!-- Section: personal info -->
            <div class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-purple-600">ข้อมูลส่วนตัว</p>

                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">คำนำหน้าชื่อ</label>
                        <select
                            name="title_prefix" required
                            class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm shadow-sm transition focus:border-brand-green-500 focus:bg-white focus:ring-brand-green-500"
                        >
                            <option value="">-- เลือก --</option>
                            @foreach (['นาย', 'นาง', 'นางสาว'] as $prefix)
                                <option value="{{ $prefix }}" @selected(old('title_prefix') === $prefix)>{{ $prefix }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">ชื่อ</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.964 0a9 9 0 10-11.964 0m11.964 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                            <input
                                type="text" name="first_name" value="{{ old('first_name') }}" required
                                class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 text-sm shadow-sm transition focus:border-brand-green-500 focus:bg-white focus:ring-brand-green-500"
                                placeholder="กรอกชื่อ"
                            >
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">นามสกุล</label>
                        <input
                            type="text" name="last_name" value="{{ old('last_name') }}" required
                            class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm shadow-sm transition focus:border-brand-green-500 focus:bg-white focus:ring-brand-green-500"
                            placeholder="กรอกนามสกุล"
                        >
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">รหัสนักศึกษา</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018.75 4.5H5.25A2.25 2.25 0 003 6.75v10.5A2.25 2.25 0 005.25 19.5z"/></svg>
                        </span>
                        <input
                            type="text" name="student_id" value="{{ old('student_id') }}" required
                            inputmode="numeric" pattern="\d{11}" maxlength="11"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 text-sm shadow-sm transition focus:border-brand-green-500 focus:bg-white focus:ring-brand-green-500"
                            placeholder="รหัส 11 หลัก"
                        >
                    </div>
                </div>
            </div>

            <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

            <!-- Section: academic info -->
            <div class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-purple-600">ข้อมูลการศึกษา</p>

                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">ปีที่เข้าศึกษา (พ.ศ.)</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            </span>
                            <input
                                type="number" name="enrollment_year" value="{{ old('enrollment_year') }}" required
                                min="2540" max="{{ date('Y') + 543 }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 text-sm shadow-sm transition focus:border-brand-green-500 focus:bg-white focus:ring-brand-green-500"
                                placeholder="เช่น {{ date('Y') + 543 }}"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">ประเภทหลักสูตร</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex cursor-pointer items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-2 py-2.5 text-sm shadow-sm transition has-[:checked]:border-brand-green-500 has-[:checked]:bg-brand-green-50 has-[:checked]:text-brand-green-700 has-[:checked]:shadow-none has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-green-500">
                                <input type="radio" name="program_type" value="normal" required class="sr-only">
                                ภาคปกติ
                            </label>
                            <label class="flex cursor-pointer items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-2 py-2.5 text-sm shadow-sm transition has-[:checked]:border-brand-green-500 has-[:checked]:bg-brand-green-50 has-[:checked]:text-brand-green-700 has-[:checked]:shadow-none has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-green-500">
                                <input type="radio" name="program_type" value="special" required class="sr-only">
                                กศ.บป.
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">คณะ</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21V9.75l8.25-4.5 8.25 4.5V21M8.25 21v-6h7.5v6M3 21h18"/></svg>
                        </span>
                        <select
                            name="faculty_id" x-model="facultyId" @change="loadMajors()" required
                            class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 text-sm shadow-sm transition focus:border-brand-green-500 focus:bg-white focus:ring-brand-green-500"
                        >
                            <option value="">-- เลือกคณะ --</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}">{{ $faculty->name_th }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">สาขาวิชา</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                        </span>
                        <select
                            name="major_id" required :disabled="! facultyId || loadingMajors"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 text-sm shadow-sm transition focus:border-brand-green-500 focus:bg-white focus:ring-brand-green-500 disabled:text-gray-400"
                        >
                            <option value="">-- <span x-text="loadingMajors ? 'กำลังโหลด...' : 'เลือกสาขาวิชา'"></span> --</option>
                            <template x-for="major in majors" :key="major.id">
                                <option :value="major.id" x-text="`${major.name_th} (${major.degree_abbr ?? '-'})`"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-green-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-green-600/20 transition hover:bg-brand-green-700 hover:shadow-xl hover:shadow-brand-green-600/25 active:scale-[0.99]"
            >
                บันทึกและเข้าใช้งานระบบ
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </button>
        </form>

        <p class="relative mt-5 text-center text-xs text-white/70">
            ข้อมูลของคุณจะถูกเก็บเป็นความลับตามนโยบายของมหาวิทยาลัย
        </p>
    </div>
</div>
@endsection
