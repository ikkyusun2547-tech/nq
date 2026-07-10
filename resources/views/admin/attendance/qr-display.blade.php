@extends('layouts.app')

@section('content')
<div class="qr-stage relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10 text-center">
    <!-- Ambient field: deep violet base, drifting glow, fine grain -->
    <div class="pointer-events-none absolute inset-0 qr-noise"></div>
    <div class="qr-blob qr-blob--a pointer-events-none absolute -left-32 -top-32 h-[28rem] w-[28rem] rounded-full blur-3xl"></div>
    <div class="qr-blob qr-blob--b pointer-events-none absolute -bottom-32 -right-24 h-[26rem] w-[26rem] rounded-full blur-3xl"></div>
    <div class="qr-blob qr-blob--c pointer-events-none absolute left-1/2 top-1/2 h-[36rem] w-[36rem] -translate-x-1/2 -translate-y-1/2 rounded-full blur-3xl"></div>
    <div class="pointer-events-none absolute inset-0" style="box-shadow: inset 0 0 18rem 4rem rgba(8,5,20,0.55);"></div>

    <a href="{{ route('admin.activities.index') }}" class="absolute left-5 top-5 z-10 flex items-center gap-1.5 text-sm text-white/60 transition-colors hover:text-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        {{ __('กลับ') }}
    </a>

    <div class="qr-enter relative flex flex-col items-center">
        <span class="mb-6 flex h-[4.5rem] w-[4.5rem] items-center justify-center rounded-2xl bg-white/[0.07] p-3 ring-1 ring-white/15 qr-logo-glow">
            <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="h-full w-full object-contain drop-shadow">
        </span>

        @if ($canCheckIn)
            <span class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/[0.06] px-3.5 py-1.5 text-[0.68rem] font-medium uppercase tracking-[0.2em] text-white/70 ring-1 ring-white/10">
                <span class="relative flex h-1.5 w-1.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-green-400 opacity-75"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-brand-green-400"></span>
                </span>
                {{ __('กำลังเช็กชื่อสด') }}
            </span>
        @else
            <span class="mb-4 inline-flex items-center gap-2 rounded-full bg-red-500/10 px-3.5 py-1.5 text-[0.68rem] font-medium uppercase tracking-[0.2em] text-red-300 ring-1 ring-red-400/20">
                <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                {{ __('ปิดรับเช็กชื่อแล้ว') }}
            </span>
        @endif

        <h1 class="mb-1.5 max-w-lg text-2xl font-bold leading-tight text-white sm:text-[1.75rem]" style="text-wrap: balance; letter-spacing: -0.01em;">{{ $activity->title }}</h1>
        @if ($activity->location_name)
            <p class="mb-2 flex items-center gap-1.5 text-sm text-white/60">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                {{ $activity->location_name }}
            </p>
        @endif
        <p class="mb-9 text-sm font-light tracking-wide text-white/50">{{ __('สแกน QR นี้เพื่อเช็กชื่อเข้าร่วมกิจกรรม') }}</p>

        <div class="qr-frame relative rounded-[2.25rem] p-[1px]">
            <div
                id="qr-container"
                class="relative flex flex-col items-center rounded-[calc(2.25rem-1px)] bg-white px-11 py-10 dark:bg-slate-900"
                data-fragment-url="{{ route('admin.attendance.qr-fragment', $activity) }}"
            >
                <p class="text-sm text-gray-400 dark:text-slate-500">{{ __('กำลังโหลด QR...') }}</p>
            </div>
        </div>

        @if ($canCheckIn)
            <div class="mt-10 flex flex-wrap items-center justify-center gap-2 rounded-full bg-white/[0.05] px-2 py-2 ring-1 ring-white/10">
                <span class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs text-white/80">
                    <svg class="h-3.5 w-3.5 flex-shrink-0 text-brand-green-400/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    {{ __('เปลี่ยนรหัสทุก') }}&nbsp;<span class="font-semibold text-white">{{ $rotationSeconds }}</span>&nbsp;{{ __('วินาที') }}
                </span>
                <span class="h-3.5 w-px bg-white/15"></span>
                <span class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs text-white/80">
                    <svg class="h-3.5 w-3.5 flex-shrink-0 text-brand-green-400/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ __('มีเวลาส่งข้อมูล') }}&nbsp;<span class="font-semibold text-white">{{ $scanValidityMinutes }}</span>&nbsp;{{ __('นาที') }}
                </span>
            </div>

            <a href="{{ route('admin.attendance.qr-print', $activity) }}" class="mt-4 inline-flex items-center gap-1.5 text-xs text-white/50 underline decoration-dotted underline-offset-4 transition-colors hover:text-white/80">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                {{ __('ไม่มีจอแสดงหน้างาน? ดาวน์โหลด QR สำรองสำหรับพิมพ์') }}
            </a>
        @else
            <a href="{{ route('admin.activities.edit', $activity) }}" class="mt-10 flex items-center gap-1.5 rounded-full bg-white/[0.05] px-4 py-2 text-xs text-white/70 ring-1 ring-white/10 transition-colors hover:text-white">
                {{ __('ไปที่หน้าแก้ไขกิจกรรมเพื่อเปลี่ยนสถานะ') }} &rarr;
            </a>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<style>
    .qr-stage {
        background: radial-gradient(120% 100% at 50% 0%, #241a4a 0%, #150f30 45%, #0a0718 100%);
    }

    .qr-noise {
        opacity: 0.05;
        mix-blend-mode: overlay;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    .qr-blob { animation: qr-drift 16s ease-in-out infinite; }
    .qr-blob--a { background: radial-gradient(circle, rgba(139,92,246,0.35), transparent 70%); animation-delay: 0s; }
    .qr-blob--b { background: radial-gradient(circle, rgba(16,185,129,0.28), transparent 70%); animation-delay: -5s; }
    .qr-blob--c { background: radial-gradient(circle, rgba(124,58,237,0.18), transparent 70%); animation-delay: -10s; }

    @keyframes qr-drift {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(2rem, -1.5rem) scale(1.08); }
    }

    .qr-frame {
        background: linear-gradient(140deg, rgba(255,255,255,0.5), rgba(139,92,246,0.35) 45%, rgba(16,185,129,0.4));
        box-shadow: 0 25px 70px -15px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.04);
    }

    .qr-logo-glow {
        box-shadow: 0 0 24px rgba(16,185,129,0.25), inset 0 1px 0 rgba(255,255,255,0.15);
    }

    .qr-enter { animation: qr-enter 0.7s cubic-bezier(0.16,1,0.3,1) both; }
    @keyframes qr-enter {
        from { opacity: 0; transform: translateY(14px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes qr-countdown-bar {
        from { width: 100%; }
        to { width: 0%; }
    }
    .qr-countdown-fill {
        animation: qr-countdown-bar linear forwards;
        background: linear-gradient(90deg, var(--color-brand-purple-500), var(--color-brand-green-500));
    }

    @media (prefers-reduced-motion: reduce) {
        .qr-blob, .qr-enter, .qr-countdown-fill { animation: none !important; }
        .qr-countdown-fill { width: 0%; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('qr-container');
        const url = container.dataset.fragmentUrl;

        async function refresh() {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            container.innerHTML = await res.text();
        }

        refresh();
        setInterval(refresh, 15000);
    });
</script>
@endpush
