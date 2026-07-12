@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-lg">
    <div class="mb-6 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <a href="{{ route('admin.faculties.index') }}" class="mb-3 inline-flex items-center gap-1 text-xs font-medium text-violet-200/70 transition-colors hover:text-white">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            {{ __('กลับรายชื่อคณะ') }}
        </a>
        <h1 class="text-xl font-bold text-white sm:text-2xl">{{ __('เพิ่มคณะใหม่') }}</h1>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.faculties.store') }}" class="space-y-4 rounded-2xl glass-card p-5 shadow-soft">
        @csrf
        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('รหัสคณะ') }}</label>
            <input type="text" name="code" value="{{ old('code') }}" required maxlength="10"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ชื่อคณะ (ไทย)') }}</label>
            <input type="text" name="name_th" value="{{ old('name_th') }}" required
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ชื่อคณะ (อังกฤษ)') }}</label>
            <input type="text" name="name_en" value="{{ old('name_en') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
        </div>
        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
            {{ __('บันทึก') }}
        </button>
    </form>
</div>
@endsection
