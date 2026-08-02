@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-brand-header :title="__('คณะและสาขาวิชา')" :eyebrow="__('กองพัฒนานักศึกษา')">
        <x-slot:actions>
            <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
                {{ __(':count คณะ', ['count' => $faculties->count()]) }}
            </span>
            <a href="{{ route('admin.faculties.create') }}"
                class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
                {{ __('เพิ่มคณะใหม่') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    {{-- Headers shortened + a lighter mobile padding + the name column's
         forced min-width dropped (it already wraps to 2 lines fine at any
         width) so the whole table fits a phone screen without needing to
         scroll for the handful of faculties this ever really has —
         overflow-x-auto stays only as a safety net for when it doesn't. --}}
    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-2 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 sm:px-4">{{ __('รหัส') }}</th>
                    <th class="whitespace-nowrap px-2 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 sm:px-4">{{ __('ชื่อคณะ') }}</th>
                    <th class="whitespace-nowrap px-2 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 sm:px-4">{{ __('สาขา') }}</th>
                    <th class="whitespace-nowrap px-2 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 sm:px-4">{{ __('นักศึกษา') }}</th>
                    <th class="whitespace-nowrap px-2 py-3 sm:px-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($faculties as $faculty)
                    <tr @class([
                        'border-b border-slate-100 dark:border-slate-800 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:hover:bg-slate-800/60',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-2 py-3 font-mono text-slate-500 dark:text-slate-400 sm:px-4">{{ $faculty->code }}</td>
                        <td class="max-w-[9rem] whitespace-normal break-words px-2 py-3 font-medium text-slate-900 dark:text-slate-100 sm:max-w-xs sm:px-4">{{ $faculty->name_th }}</td>
                        <td class="whitespace-nowrap px-2 py-3 text-slate-500 dark:text-slate-400 sm:px-4">{{ $faculty->majors_count }}</td>
                        <td class="whitespace-nowrap px-2 py-3 text-slate-500 dark:text-slate-400 sm:px-4">{{ $faculty->users_count }}</td>
                        <td class="whitespace-nowrap px-2 py-3 text-right sm:px-4">
                            <a href="{{ route('admin.faculties.edit', $faculty) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('จัดการ') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีคณะในระบบ') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
