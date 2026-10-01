@extends('layouts.dashboard')

@section('content')
@php
    $programLabel = ['normal' => __('ภาคปกติ'), 'special' => __('ภาคพิเศษ (กศ.บป.)')];
@endphp

<div class="mx-auto max-w-4xl">
    <x-brand-header :title="__('เกณฑ์การจบการศึกษา')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('กำหนดจำนวนกิจกรรม/ชั่วโมงแยกตามปีที่นักศึกษาเข้าศึกษา — แต่ละรุ่นใช้เกณฑ์ของปีตัวเองตลอดหลักสูตร')">
        <x-slot:actions>
            <a href="{{ route('admin.settings.create') }}"
                class="flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-brand-purple-500/40 dark:hover:text-brand-purple-300">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('เพิ่มเกณฑ์ปีใหม่') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-brand-green-50 px-4 py-3 text-sm text-brand-green-700 shadow-soft ring-1 ring-brand-green-100 dark:bg-brand-green-500/10 dark:text-brand-green-400 dark:ring-brand-green-500/20">
            {{ session('status') }}
        </div>
    @endif

    @if ($years->isEmpty())
        <div class="rounded-3xl glass-card p-6 text-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('ยังไม่มีการตั้งเกณฑ์เฉพาะปี — นักศึกษาทุกคนใช้ค่าเริ่มต้นด้านล่างอยู่ตอนนี้') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($years as $year => $rows)
                <div class="rounded-3xl glass-card p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('รหัสนักศึกษาปี :year', ['year' => $year]) }}</h2>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.settings.edit', $year) }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('แก้ไข') }}</a>
                            <form method="POST" action="{{ route('admin.settings.destroy', $year) }}">
                                @csrf
                                @method('DELETE')
                                <x-confirm-submit tone="red"
                                    :message="__('ลบเกณฑ์ปี :year? นักศึกษารหัสปีนี้จะกลับไปใช้เกณฑ์ปีก่อนหน้าหรือค่าเริ่มต้นแทน', ['year' => $year])"
                                    :label="__('ลบ')"
                                    class="text-xs font-medium text-red-500 hover:underline dark:text-red-400">{{ __('ลบ') }}</x-confirm-submit>
                            </form>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach (['normal', 'special'] as $type)
                            <div class="rounded-xl bg-slate-50/70 p-3.5 text-sm dark:bg-slate-800/40">
                                <p class="mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $programLabel[$type] }}</p>
                                @if ($rows->has($type))
                                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ __(':a กิจกรรม / :h ชม.', ['a' => $rows[$type]->required_activities, 'h' => $rows[$type]->required_hours]) }}</p>
                                @else
                                    <p class="text-xs text-amber-600 dark:text-amber-400">{{ __('ยังไม่ได้ตั้ง — ใช้ค่าเริ่มต้น') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/50 p-5 dark:border-slate-800 dark:bg-slate-800/30">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ค่าเริ่มต้น (ใช้เมื่อไม่มีเกณฑ์ของปีนั้นหรือปีก่อนหน้า)') }}</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach (['normal', 'special'] as $type)
                <div class="rounded-xl bg-white p-3.5 text-sm shadow-soft dark:bg-slate-900">
                    <p class="mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $programLabel[$type] }}</p>
                    <p class="font-medium text-slate-700 dark:text-slate-200">{{ __(':a กิจกรรม / :h ชม.', ['a' => $defaultCriteria[$type]['required_activities'], 'h' => $defaultCriteria[$type]['required_hours']]) }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
