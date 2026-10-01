@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-brand-header :title="__('เพิ่มเกณฑ์การจบการศึกษา')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('กำหนดเกณฑ์สำหรับนักศึกษาที่เข้าศึกษาปีที่ระบุ')" />

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.store') }}" class="space-y-5">
        @csrf

        <div class="rounded-3xl glass-card p-5">
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ปีการศึกษาที่เข้า (ปี พ.ศ.)') }}</label>
            <input type="number" name="enrollment_year" value="{{ old('enrollment_year') }}" min="2500" max="2600" placeholder="{{ __('ระบุปีการศึกษา') }}" required
                class="w-full max-w-xs rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">{{ __('นักศึกษาที่มีปีที่เข้าศึกษาตรงกับปีนี้จะใช้เกณฑ์ชุดนี้ตลอดทั้งหลักสูตร') }}</p>
        </div>

        @include('admin.settings._criteria-fields')

        <button type="submit"
            class="w-full rounded-xl bg-brand-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:bg-brand-purple-800">
            {{ __('บันทึกเกณฑ์') }}
        </button>
    </form>
</div>
@endsection
