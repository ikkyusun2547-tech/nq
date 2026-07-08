@extends('layouts.dashboard')

@section('content')
@php
    $programLabel = ['normal' => __('ภาคปกติ'), 'special' => __('กศ.บป.')];
    $statusDot = ['active' => 'bg-brand-green-500', 'banned' => 'bg-red-500'];
    $statusBadge = [
        'active' => 'bg-brand-green-50 text-brand-green-700',
        'banned' => 'bg-red-50 text-red-700',
    ];
    $statusLabel = ['active' => __('ใช้งานปกติ'), 'banned' => __('ระงับการใช้งาน')];
@endphp

<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('กองพัฒนานักศึกษา') }}</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ __('ข้อมูลนักศึกษาในระบบ') }}</h1>
        </div>
        <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
            {{ __('ทั้งหมด :count คน', ['count' => $students->total()]) }}
        </span>
    </div>

    <form method="GET" action="{{ route('admin.students.index') }}" class="mt-4 space-y-3">
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            </span>
            <input
                type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อ หรือ รหัสนักศึกษา') }}"
                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
            >
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21V9.75l8.25-4.5 8.25 4.5V21M8.25 21v-6h7.5v6M3 21h18"/></svg>
                </span>
                <select
                    name="faculty_id" onchange="this.form.submit()"
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-700 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                >
                    <option value="">{{ __('-- ทุกคณะ --') }}</option>
                    @foreach ($faculties as $faculty)
                        <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>{{ $faculty->name_th }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </span>
            </div>

            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                </span>
                <select
                    name="major_id" onchange="this.form.submit()"
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-700 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                >
                    <option value="">{{ __('-- ทุกสาขา --') }}</option>
                    @foreach ($faculties as $faculty)
                        @if ($faculty->majors->isNotEmpty())
                            <optgroup label="{{ $faculty->name_th }}">
                                @foreach ($faculty->majors as $major)
                                    <option value="{{ $major->id }}" @selected(request('major_id') == $major->id)>{{ $major->name_th }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </span>
            </div>

            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347M4.26 10.147a48.474 48.474 0 017.748-3.909m0 0a48.94 48.94 0 013.98 0M4.26 10.147L2.16 8.42m9.828-2.182a48.94 48.94 0 013.98 0m0 0l2.09-1.727m-2.09 1.727l2.09 1.727M4.26 10.147L2.16 11.874m17.68-1.727l2.1 1.727"/></svg>
                </span>
                <select
                    name="year_level" onchange="this.form.submit()"
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-700 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10"
                >
                    <option value="">{{ __('-- ทุกชั้นปี --') }}</option>
                    @foreach ([1, 2, 3, 4] as $year)
                        <option value="{{ $year }}" @selected((string) request('year_level') === (string) $year)>{{ __('ชั้นปีที่ :year', ['year' => $year]) }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </span>
            </div>
        </div>

        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-6 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:from-brand-purple-500 hover:to-brand-purple-400 hover:shadow-lg active:scale-[0.99] sm:ml-auto sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            {{ __('ค้นหา') }}
        </button>
    </form>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ชื่อ-นามสกุล') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('รหัสนักศึกษา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('คณะ / สาขา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ชั้นปี') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ภาค') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('สถานะ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr @class([
                        'border-b border-slate-100 transition-colors last:border-0 hover:bg-brand-purple-50/40',
                        'bg-white' => $loop->even,
                        'bg-slate-50/50' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $student->name_thai ?? $student->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-slate-500">{{ $student->student_id ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                            {{ $student->faculty?->name_th ?? '-' }}
                            @if ($student->major)
                                <span class="text-slate-300">·</span> {{ $student->major->name_th }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $student->year_level ? __('ปี :year', ['year' => $student->year_level]) : '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $programLabel[$student->program_type] ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$student->account_status] ?? 'bg-slate-100 text-slate-500' }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$student->account_status] ?? 'bg-slate-400'])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$student->account_status] ?? 'bg-slate-400'])></span>
                                </span>
                                {{ $statusLabel[$student->account_status] ?? $student->account_status }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800">{{ __('ดูข้อมูล') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">{{ __('ไม่พบนักศึกษาที่ตรงกับเงื่อนไข') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $students->links() }}</div>
</div>
@endsection
