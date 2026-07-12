@extends('layouts.dashboard')

@section('content')
@php
    $programLabel = ['normal' => __('ภาคปกติ'), 'special' => __('ภาคพิเศษ (กศ.บป.)')];
@endphp

<div class="mx-auto max-w-3xl">
    <x-brand-header :title="__('เกณฑ์การจบการศึกษา')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('กำหนดจำนวนกิจกรรมและชั่วโมงที่นักศึกษาต้องสะสมเพื่อผ่านเกณฑ์')" />

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        @foreach (['normal', 'special'] as $type)
            <div class="rounded-2xl glass-card p-5 shadow-soft">
                <h2 class="mb-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $programLabel[$type] }}</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('จำนวนกิจกรรมที่ต้องสะสม') }}</label>
                        <input type="number" name="{{ $type }}[required_activities]" value="{{ old("$type.required_activities", $criteria[$type]['required_activities']) }}" min="1" max="200" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('จำนวนชั่วโมงที่ต้องสะสม') }}</label>
                        <input type="number" name="{{ $type }}[required_hours]" value="{{ old("$type.required_hours", $criteria[$type]['required_hours']) }}" min="1" max="2000" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                </div>

                <p class="mb-2 mt-4 text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('เป้าหมายชั่วโมงต่อชั้นปี') }}</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([1, 2, 3, 4] as $year)
                        <div>
                            <label class="mb-1 block text-[0.68rem] text-slate-400 dark:text-slate-500">{{ __('ชั้นปีที่ :year', ['year' => $year]) }}</label>
                            <input type="number" name="{{ $type }}[yearly_targets][{{ $year }}]" value="{{ old("$type.yearly_targets.$year", $criteria[$type]['yearly_targets'][$year] ?? 0) }}" min="0" max="500" required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button type="submit"
            class="w-full rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
            {{ __('บันทึกเกณฑ์') }}
        </button>
    </form>
</div>
@endsection
