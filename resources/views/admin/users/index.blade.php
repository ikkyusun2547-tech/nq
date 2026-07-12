@extends('layouts.dashboard')

@section('content')
@php
    $roleTabs = ['all' => __('ทั้งหมด'), 'student' => __('นักศึกษา'), 'admin' => __('แอดมิน'), 'super_admin' => __('ผู้ดูแลระบบสูงสุด')];
    $roleLabel = ['student' => __('นักศึกษา'), 'admin' => __('แอดมิน'), 'super_admin' => __('ผู้ดูแลระบบสูงสุด')];
    $roleBadge = [
        'student' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
        'admin' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400',
        'super_admin' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
    ];
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
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ __('จัดการผู้ใช้งานและสิทธิ์') }}</h1>
        </div>
        <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
            {{ __('ทั้งหมด :count คน', ['count' => $users->total()]) }}
        </span>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach ($roleTabs as $value => $label)
            <a href="{{ route('admin.users.index', array_merge(request()->only(['search']), ['role' => $value])) }}"
                @class([
                    'rounded-full px-3.5 py-1.5 font-medium transition-all duration-200',
                    'bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 text-white shadow-soft' => $role === $value,
                    'bg-white text-slate-500 shadow-soft ring-1 ring-slate-200 hover:-translate-y-0.5 hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-brand-purple-400' => $role !== $value,
                ])>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4">
        <input type="hidden" name="role" value="{{ $role }}">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อ, อีเมล หรือรหัสนักศึกษา') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>
            <button type="submit"
                class="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-6 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:from-brand-purple-500 hover:to-brand-purple-400 hover:shadow-lg active:scale-[0.99]">
                {{ __('ค้นหา') }}
            </button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อ-นามสกุล') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('อีเมล') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สิทธิ์') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะบัญชี') }}</th>
                    <th class="whitespace-nowrap px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr @class([
                        'border-b border-slate-100 dark:border-slate-800 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:hover:bg-slate-800/60',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                            {{ $user->name_thai ?? $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="ml-1 text-xs font-normal text-slate-400 dark:text-slate-500">({{ __('คุณ') }})</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $user->email }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $roleBadge[$user->role] ?? 'bg-slate-100 text-slate-500' }}">
                                {{ $roleLabel[$user->role] ?? $user->role }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$user->account_status] ?? 'bg-slate-100 text-slate-500' }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$user->account_status] ?? 'bg-slate-400'])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$user->account_status] ?? 'bg-slate-400'])></span>
                                </span>
                                {{ $statusLabel[$user->account_status] ?? $user->account_status }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if ($user->role === 'student')
                                    <form method="POST" action="{{ route('admin.users.promote', $user) }}" onsubmit="return confirm('{{ __('ยืนยันเลื่อนสิทธิ์เป็นแอดมิน?') }}')">
                                        @csrf
                                        <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-brand-purple-600 transition-colors hover:bg-brand-purple-50 dark:text-brand-purple-400 dark:hover:bg-brand-purple-500/10">{{ __('เลื่อนเป็นแอดมิน') }}</button>
                                    </form>
                                @elseif ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.demote', $user) }}" onsubmit="return confirm('{{ __('ยืนยันลดสิทธิ์เป็นนักศึกษา?') }}')">
                                        @csrf
                                        <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">{{ __('ลดสิทธิ์') }}</button>
                                    </form>
                                @endif

                                @if ($user->id !== auth()->id())
                                    @if ($user->account_status === 'active')
                                        <form method="POST" action="{{ route('admin.users.ban', $user) }}" onsubmit="return confirm('{{ __('ยืนยันระงับการใช้งานบัญชีนี้?') }}')">
                                            @csrf
                                            <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">{{ __('ระงับบัญชี') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-brand-green-600 transition-colors hover:bg-brand-green-50 dark:text-brand-green-400 dark:hover:bg-brand-green-500/10">{{ __('ปลดระงับ') }}</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ไม่พบผู้ใช้ที่ตรงกับเงื่อนไข') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
