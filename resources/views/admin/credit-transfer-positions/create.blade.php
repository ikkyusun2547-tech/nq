@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-lg">
    <x-brand-header :title="__('เพิ่มตำแหน่งเทียบโอนชั่วโมง')" :back="route('admin.credit-transfer-positions.index')" />

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.credit-transfer-positions.store') }}" class="space-y-4 rounded-2xl glass-card p-5 shadow-soft">
        @csrf
        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('รหัสตำแหน่ง (a-z, 0-9, _)') }}</label>
            <input type="text" name="key" value="{{ old('key') }}" required maxlength="100" pattern="[a-z0-9_]+"
                placeholder="{{ __('เช่น class_leader') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('รหัสอ้างอิงถาวรของตำแหน่งนี้ในระบบ ไม่ควรเปลี่ยนภายหลังหากมีคำร้องผูกอยู่แล้ว') }}</p>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ชื่อตำแหน่ง') }}</label>
            <input type="text" name="label" value="{{ old('label') }}" required
                placeholder="{{ __('เช่น หัวหน้าหมู่เรียน') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ชั่วโมงมาตรฐาน') }}</label>
                <input type="number" name="hours" value="{{ old('hours') }}" required min="0" max="200"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ลำดับแสดงผล (เว้นว่าง = ไปท้ายสุด)') }}</label>
                <input type="number" name="sort_order" value="{{ old('sort_order') }}" min="0"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            </div>
        </div>
        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
            {{ __('บันทึก') }}
        </button>
    </form>
</div>
@endsection
