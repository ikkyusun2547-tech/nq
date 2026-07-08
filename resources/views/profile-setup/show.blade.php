@extends('layouts.app')

@section('content')
<div
    class="grid min-h-screen grid-cols-1 lg:grid-cols-5"
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
    <!-- Left: branding panel (desktop only) -->
    <div class="relative hidden overflow-hidden brand-gradient p-12 lg:col-span-2 lg:flex lg:flex-col lg:justify-between">
        <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-16 h-80 w-80 rounded-full bg-brand-green-500/10 blur-3xl"></div>

        <div class="relative flex items-center gap-4">
            <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="h-16 w-16 object-contain drop-shadow">
            <span class="text-xl font-bold tracking-wide text-white">SRRU Check</span>
        </div>

        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">ขั้นตอนสุดท้ายก่อนใช้งาน</p>
            <h1 class="mt-3 text-3xl font-bold leading-tight text-white">
                ยินดีต้อนรับสู่<br>ระบบกิจกรรมนักศึกษา
            </h1>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-violet-100/70">
                กรอกข้อมูลโปรไฟล์ให้ครบถ้วน เพื่อเริ่มสะสมชั่วโมงกิจกรรมและใช้งานระบบเช็กชื่อได้ทันที
            </p>

            <ul class="mt-8 space-y-3.5">
                @foreach ([
                    'เช็กชื่อกิจกรรมด้วย QR + GPS + เซลฟี ยืนยันตัวตน',
                    'ติดตามความคืบหน้าชั่วโมงกิจกรรมแบบเรียลไทม์',
                    'ยื่นคำร้องเทียบกิจกรรมภายนอกได้ในระบบเดียว',
                ] as $feature)
                    <li class="flex items-start gap-3 text-sm text-violet-100/90">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-green-500/20 text-brand-green-400">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        </span>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="relative text-xs text-violet-200/50">กองพัฒนานักศึกษา · มหาวิทยาลัยราชภัฏสุรินทร์</p>
    </div>

    <!-- Right: form panel -->
    <div class="flex items-center justify-center bg-slate-50 px-4 py-10 lg:col-span-3 lg:px-16">
        <div class="w-full max-w-lg">
            <div class="mb-6 flex items-center gap-3 lg:hidden">
                <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="h-11 w-11 object-contain">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-brand-purple-500">ขั้นตอนสุดท้ายก่อนใช้งาน</p>
                    <h1 class="text-lg font-bold text-slate-900">กรอกข้อมูลโปรไฟล์นักศึกษา</h1>
                </div>
            </div>
            <h1 class="mb-6 hidden text-2xl font-bold text-slate-900 lg:block">กรอกข้อมูลโปรไฟล์นักศึกษา</h1>

            @if ($errors->any())
                <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('profile-setup.store') }}" class="space-y-8">
                @csrf

                <!-- Section: personal info -->
                <div class="space-y-5">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-purple-600 text-xs font-bold text-white">1</span>
                        <p class="text-sm font-semibold text-slate-900">ข้อมูลส่วนตัว</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-600">คำนำหน้าชื่อ</label>
                            <div class="relative">
                                <select
                                    name="title_prefix" required
                                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-3.5 pr-9 text-sm text-slate-700 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                                >
                                    <option value="">-- เลือก --</option>
                                    @foreach (['นาย', 'นาง', 'นางสาว'] as $prefix)
                                        <option value="{{ $prefix }}" @selected(old('title_prefix') === $prefix)>{{ $prefix }}</option>
                                    @endforeach
                                </select>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                                </span>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-600">ชื่อ</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.964 0a9 9 0 10-11.964 0m11.964 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <input
                                    type="text" name="first_name" value="{{ old('first_name') }}" required
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                                    placeholder="กรอกชื่อ"
                                >
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-600">นามสกุล</label>
                        <input
                            type="text" name="last_name" value="{{ old('last_name') }}" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                            placeholder="กรอกนามสกุล"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-600">รหัสนักศึกษา</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018.75 4.5H5.25A2.25 2.25 0 003 6.75v10.5A2.25 2.25 0 005.25 19.5z"/></svg>
                            </span>
                            <input
                                type="text" name="student_id" value="{{ old('student_id') }}" required
                                inputmode="numeric" pattern="\d{11}" maxlength="11"
                                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm tracking-wide text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                                placeholder="รหัส 11 หลัก"
                            >
                        </div>
                    </div>
                </div>

                <!-- Section: academic info -->
                <div class="space-y-5">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-purple-600 text-xs font-bold text-white">2</span>
                        <p class="text-sm font-semibold text-slate-900">ข้อมูลการศึกษา</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-600">ปีที่เข้าศึกษา (พ.ศ.)</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                </span>
                                <input
                                    type="number" name="enrollment_year" value="{{ old('enrollment_year') }}" required
                                    min="2540" max="{{ date('Y') + 543 }}"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                                    placeholder="เช่น {{ date('Y') + 543 }}"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-600">ประเภทหลักสูตร</label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label class="flex cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-2 py-2.5 text-sm shadow-soft transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 has-[:checked]:shadow-none has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-purple-500/10">
                                    <input type="radio" name="program_type" value="normal" required class="sr-only">
                                    ภาคปกติ
                                </label>
                                <label class="flex cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-2 py-2.5 text-sm shadow-soft transition-all duration-200 has-[:checked]:border-brand-purple-500 has-[:checked]:bg-brand-purple-50 has-[:checked]:text-brand-purple-700 has-[:checked]:shadow-none has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-purple-500/10">
                                    <input type="radio" name="program_type" value="special" required class="sr-only">
                                    กศ.บป.
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-600">คณะ</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21V9.75l8.25-4.5 8.25 4.5V21M8.25 21v-6h7.5v6M3 21h18"/></svg>
                            </span>
                            <select
                                name="faculty_id" x-model="facultyId" @change="loadMajors()" required
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-700 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                            >
                                <option value="">-- เลือกคณะ --</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name_th }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-600">สาขาวิชา</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            </span>
                            <select
                                name="major_id" required :disabled="! facultyId || loadingMajors"
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-700 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 disabled:bg-slate-50 disabled:text-slate-400"
                            >
                                <option value="">-- <span x-text="loadingMajors ? 'กำลังโหลด...' : 'เลือกสาขาวิชา'"></span> --</option>
                                <template x-for="major in majors" :key="major.id">
                                    <option :value="major.id" x-text="`${major.name_th} (${major.degree_abbr ?? '-'})`"></option>
                                </template>
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </span>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-green-500 px-4 py-4 text-base font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg active:scale-[0.99]"
                >
                    บันทึกและเข้าใช้งานระบบ
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>

                <p class="text-center text-xs text-slate-400">
                    ข้อมูลของคุณจะถูกเก็บเป็นความลับตามนโยบายของมหาวิทยาลัย
                </p>
            </form>
        </div>
    </div>
</div>
@endsection
