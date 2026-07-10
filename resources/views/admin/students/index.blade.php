@extends('layouts.dashboard')

@section('content')
@php
    $programLabel = ['normal' => __('ภาคปกติ'), 'special' => __('กศ.บป.')];
    $statusDot = ['active' => 'bg-brand-green-500', 'banned' => 'bg-red-500'];
    $statusBadge = [
        'active' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'banned' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $statusLabel = ['active' => __('ใช้งานปกติ'), 'banned' => __('ระงับการใช้งาน')];
@endphp

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('กองพัฒนานักศึกษา') }}</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ __('ข้อมูลนักศึกษาในระบบ') }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
                {{ __('ทั้งหมด :count คน', ['count' => $students->total()]) }}
            </span>
            <a href="{{ route('admin.students.import.create') }}"
                class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
                {{ __('นำเข้ารายชื่อนักศึกษา') }}
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.students.index') }}" class="mt-4 space-y-3">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อ หรือ รหัสนักศึกษา') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>

            <button type="submit"
                class="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-6 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:from-brand-purple-500 hover:to-brand-purple-400 hover:shadow-lg active:scale-[0.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                {{ __('ค้นหา') }}
            </button>
        </div>

        @php
            $facultyOptions = $faculties->pluck('name_th', 'id')->all();

            if (request('faculty_id')) {
                $selectedFaculty = $faculties->firstWhere('id', (int) request('faculty_id'));
                $majorOptions = $selectedFaculty?->majors->pluck('name_th', 'id')->all() ?? [];
                $majorGroups = null;
            } else {
                $majorOptions = null;
                $majorGroups = $faculties->filter(fn ($f) => $f->majors->isNotEmpty())
                    ->mapWithKeys(fn ($f) => [$f->name_th => $f->majors->pluck('name_th', 'id')->all()])
                    ->all();
            }

            $yearOptions = collect([1, 2, 3, 4])->mapWithKeys(fn ($y) => [$y => __('ชั้นปีที่ :year', ['year' => $y])])->all();
        @endphp

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-premium-select
                name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
                placeholder="{{ __('-- ทุกคณะ --') }}" autosubmit resets="major_id"
            />

            <x-premium-select
                name="major_id" :options="$majorOptions" :groups="$majorGroups" :selected="request('major_id')"
                placeholder="{{ __('-- ทุกสาขา --') }}" autosubmit
            />

            <x-premium-select
                name="year_level" :options="$yearOptions" :selected="request('year_level')"
                placeholder="{{ __('-- ทุกชั้นปี --') }}" autosubmit
            />
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อ-นามสกุล') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('รหัสนักศึกษา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ / สาขา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชั้นปี') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ภาค') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr @class([
                        'border-b border-slate-100 dark:border-slate-800 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:hover:bg-slate-800/60',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $student->name_thai ?? $student->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-slate-500 dark:text-slate-400">{{ $student->student_id ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            {{ $student->faculty?->name_th ?? '-' }}
                            @if ($student->major)
                                <span class="text-slate-300 dark:text-slate-600">·</span> {{ $student->major->name_th }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $student->year_level ? __('ปี :year', ['year' => $student->year_level]) : '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $programLabel[$student->program_type] ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$student->account_status] ?? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$student->account_status] ?? 'bg-slate-400'])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$student->account_status] ?? 'bg-slate-400'])></span>
                                </span>
                                {{ $statusLabel[$student->account_status] ?? $student->account_status }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('ดูข้อมูล') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ไม่พบนักศึกษาที่ตรงกับเงื่อนไข') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $students->links() }}</div>
</div>
@endsection
