@extends('layouts.dashboard')

@section('content')
@php
    $programLabel = ['normal' => 'ภาคปกติ', 'special' => 'กศ.บป.'];
    $statusDot = ['active' => 'bg-brand-green-500', 'banned' => 'bg-red-500'];
    $statusBadge = [
        'active' => 'bg-brand-green-50 text-brand-green-700',
        'banned' => 'bg-red-50 text-red-700',
    ];
    $statusLabel = ['active' => 'ใช้งานปกติ', 'banned' => 'ระงับการใช้งาน'];
@endphp

<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">กองพัฒนานักศึกษา</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">ข้อมูลนักศึกษาในระบบ</h1>
        </div>
        <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
            ทั้งหมด {{ $students->total() }} คน
        </span>
    </div>

    <form method="GET" action="{{ route('admin.students.index') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
        <input
            type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อ หรือ รหัสนักศึกษา"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 sm:flex-1"
        >
        <select
            name="faculty_id" onchange="this.form.submit()"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 sm:w-64"
        >
            <option value="">-- ทุกคณะ --</option>
            @foreach ($faculties as $faculty)
                <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>{{ $faculty->name_th }}</option>
            @endforeach
        </select>
        <button type="submit"
            class="rounded-xl bg-brand-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-purple-700 hover:shadow-lg">
            ค้นหา
        </button>
    </form>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">ชื่อ-นามสกุล</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">รหัสนักศึกษา</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">คณะ / สาขา</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">ชั้นปี</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">ภาค</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">สถานะ</th>
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
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $student->year_level ? 'ปี '.$student->year_level : '-' }}</td>
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
                            <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800">ดูข้อมูล</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">ไม่พบนักศึกษาที่ตรงกับเงื่อนไข</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $students->links() }}</div>
</div>
@endsection
