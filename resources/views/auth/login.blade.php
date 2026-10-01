@extends('layouts.app')

@section('content')
<div class="relative flex min-h-dvh flex-col items-center justify-center px-4 py-12">
    <div class="relative mb-4 flex w-full max-w-sm items-center justify-end gap-2">
        @include('partials.theme-toggle')
        @include('partials.locale-switch')
    </div>

    <div class="relative w-full max-w-sm rounded-3xl glass-card p-8">
        <div class="mb-8 text-center">
            <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="mx-auto mb-4 h-20 w-20 object-contain">
            <h1 class="font-display text-2xl text-slate-900 dark:text-white">SRRU Check</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('มหาวิทยาลัยราชภัฏสุรินทร์') }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                {{ $errors->first() }}
            </div>
        @endif

        <a
            href="{{ route('auth.google.redirect') }}"
            class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 active:scale-[0.99] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.47a5.54 5.54 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.54-5.17 3.54-8.66z"/>
                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.07 7.93-2.9l-3.88-3c-1.08.72-2.45 1.15-4.05 1.15-3.11 0-5.75-2.1-6.69-4.92H1.3v3.09A12 12 0 0 0 12 24z"/>
                <path fill="#FBBC05" d="M5.31 14.33a7.2 7.2 0 0 1 0-4.66V6.58H1.3a12 12 0 0 0 0 10.84l4.01-3.09z"/>
                <path fill="#EA4335" d="M12 4.77c1.77 0 3.35.61 4.6 1.8l3.44-3.44A11.6 11.6 0 0 0 12 0 12 12 0 0 0 1.3 6.58l4.01 3.09C6.25 6.86 8.89 4.77 12 4.77z"/>
            </svg>
            {{ __('เข้าสู่ระบบด้วยบัญชี Google มหาวิทยาลัย') }}
        </a>

        <p class="mt-3 text-center text-xs text-slate-400 dark:text-slate-500">
            {{ __('ใช้ได้เฉพาะบัญชีอีเมล @ :domain เท่านั้น', ['domain' => config('services.srru.email_domain')]) }}
        </p>

        @if (\App\Http\Controllers\Auth\DemoLoginController::enabled())
            <div class="my-5 flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500">
                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
                {{ __('หรือ') }}
                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            </div>

            <a
                href="{{ route('demo-login.show') }}"
                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-purple-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-purple-800 active:scale-[0.99]"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.03 5.91c-.47-.08-.97.02-1.3.36L11.25 17.25H9v2.25H6.75v2.25H3v-2.82c0-.6.24-1.17.66-1.6l6.07-6.07c.34-.33.44-.83.36-1.3A6 6 0 1 1 21.75 8.25Z"/>
                </svg>
                {{ __('เข้าสู่ระบบแบบทดลอง') }}
            </a>

            <p class="mt-3 text-center text-xs text-slate-400 dark:text-slate-500">
                {{ __('สำหรับอาจารย์และผู้ประเมินระบบ') }}
            </p>
        @endif
    </div>

    <p class="relative mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
        {{ __('กองพัฒนานักศึกษา · มหาวิทยาลัยราชภัฏสุรินทร์') }}
    </p>
</div>
@endsection
