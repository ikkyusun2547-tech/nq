@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl" x-data="{ addingMajor: false, editingMajorId: null }">
    <div class="mb-6 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <a href="{{ route('admin.faculties.index') }}" class="mb-3 inline-flex items-center gap-1 text-xs font-medium text-violet-200/70 transition-colors hover:text-white">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            {{ __('กลับรายชื่อคณะ') }}
        </a>
        <h1 class="text-xl font-bold text-white sm:text-2xl">{{ $faculty->name_th }}</h1>
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

    <div class="mb-5 rounded-2xl glass-card p-5 shadow-soft">
        <h2 class="mb-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ข้อมูลคณะ') }}</h2>
        <form method="POST" action="{{ route('admin.faculties.update', $faculty) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('รหัสคณะ') }}</label>
                    <input type="text" name="code" value="{{ old('code', $faculty->code) }}" required maxlength="10"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ชื่อคณะ (ไทย)') }}</label>
                    <input type="text" name="name_th" value="{{ old('name_th', $faculty->name_th) }}" required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ชื่อคณะ (อังกฤษ)') }}</label>
                <input type="text" name="name_en" value="{{ old('name_en', $faculty->name_en) }}"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            </div>
            <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                {{ __('บันทึก') }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.faculties.destroy', $faculty) }}" class="mt-3" onsubmit="return confirm('{{ __('ยืนยันลบคณะนี้?') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">{{ __('ลบคณะนี้') }}</button>
        </form>
    </div>

    <div class="rounded-2xl glass-card p-5 shadow-soft">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('สาขาวิชา') }} ({{ $faculty->majors->count() }})</h2>
            <button type="button" @click="addingMajor = ! addingMajor" class="text-xs font-medium text-brand-purple-600 hover:text-brand-purple-800 dark:text-brand-purple-400">
                <span x-text="addingMajor ? '{{ __('ยกเลิก') }}' : '{{ __('+ เพิ่มสาขา') }}'"></span>
            </button>
        </div>

        <form x-show="addingMajor" x-cloak method="POST" action="{{ route('admin.majors.store', $faculty) }}" class="mb-4 grid grid-cols-1 gap-3 rounded-xl bg-brand-purple-50/50 p-3.5 sm:grid-cols-4 dark:bg-brand-purple-500/5">
            @csrf
            <input type="text" name="code" placeholder="{{ __('รหัสสาขา') }}" required maxlength="20"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-soft focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <input type="text" name="name_th" placeholder="{{ __('ชื่อสาขา (ไทย)') }}" required
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-soft focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <input type="text" name="degree_abbr" placeholder="{{ __('วุฒิ เช่น ค.บ.') }}"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-soft focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <button type="submit" class="rounded-xl bg-brand-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-soft transition-colors hover:bg-brand-purple-700">
                {{ __('เพิ่ม') }}
            </button>
        </form>

        <div class="space-y-2">
            @forelse ($faculty->majors as $major)
                <div class="rounded-xl bg-white/60 p-3 text-sm shadow-soft dark:bg-slate-800/60">
                    <div x-show="editingMajorId !== {{ $major->id }}" class="flex items-center justify-between gap-2">
                        <div>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ $major->name_th }} <span class="font-mono text-xs text-slate-400 dark:text-slate-500">({{ $major->code }})</span></p>
                            @if ($major->degree_abbr)
                                <p class="text-xs text-slate-400 dark:text-slate-500">{{ $major->degree_abbr }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <button type="button" @click="editingMajorId = {{ $major->id }}" class="text-xs font-medium text-brand-purple-600 hover:text-brand-purple-800 dark:text-brand-purple-400">{{ __('แก้ไข') }}</button>
                            <form method="POST" action="{{ route('admin.majors.destroy', $major) }}" onsubmit="return confirm('{{ __('ยืนยันลบสาขานี้?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 dark:text-red-400">{{ __('ลบ') }}</button>
                            </form>
                        </div>
                    </div>

                    <form x-show="editingMajorId === {{ $major->id }}" x-cloak method="POST" action="{{ route('admin.majors.update', $major) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                        @csrf
                        @method('PUT')
                        <input type="text" name="code" value="{{ $major->code }}" required maxlength="20"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm shadow-soft focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                        <input type="text" name="name_th" value="{{ $major->name_th }}" required
                            class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm shadow-soft focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                        <input type="text" name="degree_abbr" value="{{ $major->degree_abbr }}"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm shadow-soft focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-brand-purple-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-brand-purple-700">{{ __('บันทึก') }}</button>
                            <button type="button" @click="editingMajorId = null" class="flex-1 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ __('ยกเลิก') }}</button>
                        </div>
                    </form>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีสาขาในคณะนี้') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
