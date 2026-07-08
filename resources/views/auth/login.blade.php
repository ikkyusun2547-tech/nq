@extends('layouts.app')

@section('content')
<div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-12 brand-gradient">
    <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-brand-green-500/10 blur-3xl"></div>

    <div class="relative w-full max-w-sm rounded-3xl glass-card p-8 shadow-soft-lg">
        <div class="mb-8 text-center">
            <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="mx-auto mb-4 h-20 w-20 object-contain drop-shadow">
            <h1 class="text-lg font-semibold text-slate-900">ระบบเช็กชื่อกิจกรรมนักศึกษา</h1>
            <p class="mt-1 text-sm text-slate-500">มหาวิทยาลัยราชภัฏสุรินทร์</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100">
                {{ $errors->first() }}
            </div>
        @endif

        <a
            href="{{ route('auth.google.redirect') }}"
            class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg active:scale-[0.99]"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.47a5.54 5.54 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.54-5.17 3.54-8.66z"/>
                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.07 7.93-2.9l-3.88-3c-1.08.72-2.45 1.15-4.05 1.15-3.11 0-5.75-2.1-6.69-4.92H1.3v3.09A12 12 0 0 0 12 24z"/>
                <path fill="#FBBC05" d="M5.31 14.33a7.2 7.2 0 0 1 0-4.66V6.58H1.3a12 12 0 0 0 0 10.84l4.01-3.09z"/>
                <path fill="#EA4335" d="M12 4.77c1.77 0 3.35.61 4.6 1.8l3.44-3.44A11.6 11.6 0 0 0 12 0 12 12 0 0 0 1.3 6.58l4.01 3.09C6.25 6.86 8.89 4.77 12 4.77z"/>
            </svg>
            เข้าสู่ระบบด้วยบัญชี Google มหาวิทยาลัย
        </a>

        <p class="mt-6 text-center text-xs text-slate-400">
            ใช้ได้เฉพาะบัญชีอีเมล @ {{ config('services.srru.email_domain') }} เท่านั้น
        </p>
    </div>

    <p class="relative mt-6 text-center text-xs text-violet-200/70">
        กองพัฒนานักศึกษา · มหาวิทยาลัยราชภัฏสุรินทร์
    </p>
</div>
@endsection
