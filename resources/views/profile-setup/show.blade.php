@extends('layouts.app')

@section('content')
<div
    class="mx-auto flex min-h-screen max-w-md flex-col px-4 py-8"
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
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">กรอกข้อมูลโปรไฟล์นักศึกษา</h1>
        <p class="mt-1 text-sm text-gray-500">กรุณากรอกข้อมูลให้ครบก่อนใช้งานระบบ</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('profile-setup.store') }}" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        @csrf

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อ-นามสกุล (ภาษาไทย)</label>
            <input
                type="text" name="name_thai" value="{{ old('name_thai') }}" required
                class="w-full rounded-xl border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="เช่น นายสมชาย ใจดี"
            >
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">รหัสนักศึกษา</label>
            <input
                type="text" name="student_id" value="{{ old('student_id') }}" required
                inputmode="numeric" pattern="\d{8}" maxlength="8"
                class="w-full rounded-xl border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="รหัส 8 หลัก"
            >
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">ปีที่เข้าศึกษา (พ.ศ.)</label>
            <input
                type="number" name="enrollment_year" value="{{ old('enrollment_year') }}" required
                min="2540" max="{{ date('Y') + 543 }}"
                class="w-full rounded-xl border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="เช่น {{ date('Y') + 543 }}"
            >
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">ประเภทหลักสูตร</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-300 px-3 py-2.5 text-sm has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                    <input type="radio" name="program_type" value="normal" required class="text-blue-600 focus:ring-blue-500">
                    ภาคปกติ
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-300 px-3 py-2.5 text-sm has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                    <input type="radio" name="program_type" value="special" required class="text-blue-600 focus:ring-blue-500">
                    ภาคพิเศษ (กศ.บป.)
                </label>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">คณะ</label>
            <select
                name="faculty_id" x-model="facultyId" @change="loadMajors()" required
                class="w-full rounded-xl border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="">-- เลือกคณะ --</option>
                @foreach ($faculties as $faculty)
                    <option value="{{ $faculty->id }}">{{ $faculty->name_th }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">สาขาวิชา</label>
            <select
                name="major_id" required :disabled="! facultyId || loadingMajors"
                class="w-full rounded-xl border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400"
            >
                <option value="">-- <span x-text="loadingMajors ? 'กำลังโหลด...' : 'เลือกสาขาวิชา'"></span> --</option>
                <template x-for="major in majors" :key="major.id">
                    <option :value="major.id" x-text="`${major.name_th} (${major.degree_abbr ?? '-'})`"></option>
                </template>
            </select>
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 active:scale-[0.99]"
        >
            บันทึกและเข้าใช้งานระบบ
        </button>
    </form>
</div>
@endsection
