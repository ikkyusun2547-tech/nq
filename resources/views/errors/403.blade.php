@extends('layouts.app')

@section('content')
<div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-12 brand-gradient">
    <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-brand-green-500/10 blur-3xl"></div>

    <div class="relative w-full max-w-sm rounded-3xl glass-card p-8 text-center shadow-soft-lg">
        <span class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        </span>

        <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('ไม่มีสิทธิ์เข้าถึงหน้านี้') }}</h1>

        @auth
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ __('คุณกำลังเข้าสู่ระบบด้วยบัญชี :name (:email) ซึ่งไม่มีสิทธิ์เข้าถึงหน้านี้', ['name' => auth()->user()->name_thai ?? auth()->user()->name, 'email' => auth()->user()->email]) }}
            </p>

            <div class="mt-6 flex flex-col gap-2.5">
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
                    class="rounded-2xl bg-brand-purple-600 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-purple-700">
                    {{ __('กลับไปหน้าของฉัน') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        {{ __('ออกจากระบบเพื่อสลับบัญชี') }}
                    </button>
                </form>
            </div>
        @else
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ __('กรุณาเข้าสู่ระบบก่อนใช้งาน') }}
            </p>
            <a href="{{ route('login') }}"
                class="mt-6 block rounded-2xl bg-brand-purple-600 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-purple-700">
                {{ __('ไปหน้าเข้าสู่ระบบ') }}
            </a>
        @endauth
    </div>
</div>
@endsection
