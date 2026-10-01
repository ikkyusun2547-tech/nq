@extends('layouts.app')

@section('content')
<div class="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden px-4 py-12 brand-gradient">
    <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-brand-green-500/10 blur-3xl"></div>

    <div class="relative mb-4 flex w-full max-w-sm items-center justify-end gap-2">
        @include('partials.theme-toggle')
        @include('partials.locale-switch')
    </div>

    <div class="relative w-full max-w-sm rounded-3xl glass-card p-8 shadow-soft-lg">
        <div class="mb-6 text-center">
            <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="mx-auto mb-4 h-16 w-16 object-contain drop-shadow">
            <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('เข้าสู่ระบบแบบทดลอง') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('สำหรับอาจารย์และผู้ประเมินระบบ') }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('demo-login.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('รหัสผ่านทดลอง') }}</label>
                <input
                    id="password" name="password" type="password" required autofocus autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-soft focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                >
                <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">{{ __('รหัสผ่านจะกำหนดว่าเข้าใช้งานในฐานะผู้ดูแลระบบหรือนักศึกษา') }}</p>
            </div>

            <button type="submit" class="w-full rounded-2xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-violet-700 hover:shadow-lg active:scale-[0.99]">
                {{ __('เข้าสู่ระบบ') }}
            </button>
        </form>

        <p class="mt-6 text-center text-xs">
            <a href="{{ route('login') }}" class="text-slate-500 underline-offset-2 hover:underline dark:text-slate-400">
                {{ __('กลับไปเข้าสู่ระบบด้วย Google') }}
            </a>
        </p>
    </div>
</div>
@endsection
