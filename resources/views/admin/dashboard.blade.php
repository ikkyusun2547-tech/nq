@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="overflow-hidden rounded-2xl brand-gradient p-6 text-white shadow-sm sm:p-8">
        <h1 class="text-xl font-semibold sm:text-2xl">{{ __('แผงควบคุมกองพัฒนานักศึกษา') }}</h1>
        <p class="mt-1 text-sm text-white/80">{{ __('มหาวิทยาลัยราชภัฏสุรินทร์') }}</p>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.activities.index') }}" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:ring-brand-green-300 dark:bg-slate-900 dark:ring-slate-700">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-green-50 text-brand-green-600 group-hover:bg-brand-green-100 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </span>
            <p class="mt-3 font-medium text-gray-900 dark:text-slate-100">{{ __('จัดการกิจกรรม') }}</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">{{ __('สร้าง/แก้ไขกิจกรรม กำหนดสิทธิ์ผู้เข้าร่วม') }}</p>
        </a>
        <a href="{{ route('admin.external-activities.index') }}" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:ring-brand-purple-300 dark:bg-slate-900 dark:ring-slate-700">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-purple-50 text-brand-purple-600 group-hover:bg-brand-purple-100 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <p class="mt-3 font-medium text-gray-900 dark:text-slate-100">{{ __('คำร้องกิจกรรมภายนอก') }}</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">{{ __('ตรวจสอบและอนุมัติ/ปฏิเสธคำร้อง') }}</p>
        </a>
        <a href="{{ route('admin.reports.clearance', ['year' => 4]) }}" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:ring-brand-green-300 dark:bg-slate-900 dark:ring-slate-700">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-green-50 text-brand-green-600 group-hover:bg-brand-green-100 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </span>
            <p class="mt-3 font-medium text-gray-900 dark:text-slate-100">{{ __('รายงานนักศึกษาพร้อมยื่นจบ (PDF)') }}</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">{{ __('ชั้นปีที่ 4 ที่ผ่านเกณฑ์ครบ 100% ส่งต่อสำนักทะเบียน') }}</p>
        </a>
    </div>
</div>
@endsection
