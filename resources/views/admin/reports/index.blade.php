@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-brand-header :title="__('รายงาน')" :eyebrow="__('กองพัฒนานักศึกษา')" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.reports.clearance') }}"
            class="flex flex-col gap-3 rounded-2xl border border-brand-green-100 bg-brand-green-50 p-5 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg dark:border-brand-green-500/20 dark:bg-brand-green-500/10">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-green-500 text-white shadow-soft">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347"/></svg>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('รายชื่อนักศึกษาปี 4 ที่ผ่านเกณฑ์') }}</h2>
                <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('PDF สำหรับสำนักทะเบียน') }}</p>
            </div>
        </a>

        <a href="{{ route('admin.reports.faculty-participation') }}"
            class="flex flex-col gap-3 rounded-2xl border border-brand-purple-100 bg-brand-purple-50 p-5 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg dark:border-brand-purple-500/20 dark:bg-brand-purple-500/10">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-purple-500 text-white shadow-soft">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('สรุปการเข้าร่วมกิจกรรมรายคณะ') }}</h2>
                <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Excel — จำนวนนักศึกษา, อัตราผ่านเกณฑ์, ชั่วโมง/กิจกรรมเฉลี่ยต่อคณะ') }}</p>
            </div>
        </a>
    </div>
</div>
@endsection
