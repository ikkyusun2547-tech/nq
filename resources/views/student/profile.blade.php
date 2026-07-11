@extends('layouts.dashboard')

@section('content')
@php
    $programTypeLabel = $user->program_type === 'special' ? __('ภาคพิเศษ (กศ.บป.)') : __('ภาคปกติ');
@endphp

<div class="mx-auto max-w-md">
    <x-brand-header :title="$user->name_thai ?? $user->name" :subtitle="$user->email" :decorated="true">
        <x-slot:footer>
            <div class="flex justify-center">
                <span class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white/15 ring-2 ring-white/25">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <svg class="h-8 w-8 text-white/80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    @endif
                </span>
            </div>
        </x-slot:footer>
    </x-brand-header>

    <div class="flex flex-col gap-4">
        <x-section-card icon="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" :title="__('ข้อมูลส่วนตัว')">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 dark:text-slate-500">{{ __('ชื่อ-นามสกุล') }}</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $user->name_thai ?? $user->name }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 dark:text-slate-500">{{ __('รหัสนักศึกษา') }}</span>
                <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $user->student_id }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 dark:text-slate-500">{{ __('อีเมล') }}</span>
                <span class="truncate font-medium text-slate-800 dark:text-slate-200">{{ $user->email }}</span>
            </div>
        </x-section-card>

        <x-section-card icon="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347M4.24 10.147a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814M4.24 10.147a50.697 50.697 0 0111.76 0" :title="__('ข้อมูลการศึกษา')">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 dark:text-slate-500">{{ __('คณะ') }}</span>
                <span class="text-right font-medium text-slate-800 dark:text-slate-200">{{ $user->faculty?->name_th ?? '-' }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 dark:text-slate-500">{{ __('สาขา') }}</span>
                <span class="text-right font-medium text-slate-800 dark:text-slate-200">{{ $user->major?->name_th ?? '-' }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 dark:text-slate-500">{{ __('ชั้นปีที่') }}</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $user->year_level }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 dark:text-slate-500">{{ __('ปีที่เข้าศึกษา') }}</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $user->enrollment_year }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-400 dark:text-slate-500">{{ __('ประเภทหลักสูตร') }}</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $programTypeLabel }}</span>
            </div>
        </x-section-card>
    </div>
</div>
@endsection
