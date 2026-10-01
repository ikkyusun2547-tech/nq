@extends('layouts.app')

@section('content')
{{-- Projector screen: always dark (reads best on a projector/TV in a lit
     room, whatever the viewer's theme), with large type for reading from
     the back of a hall. The QR card itself is always white — a dark
     background behind the code can stop phones from scanning it. --}}
<div class="qr-stage relative flex min-h-dvh flex-col items-center justify-center px-4 py-10 text-center text-white">
    <a href="{{ route('admin.activities.index') }}" class="absolute left-5 top-5 z-10 flex items-center gap-1.5 rounded-full px-3 py-2 text-sm text-white/70 transition-colors hover:bg-white/10 hover:text-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        {{ __('กลับ') }}
    </a>

    <div class="qr-enter flex w-full max-w-5xl flex-col items-center gap-10 lg:flex-row lg:items-center lg:justify-center lg:gap-16 lg:text-left">
        {{-- Activity info --}}
        <div class="flex max-w-md flex-col items-center lg:items-start">
            <img src="{{ asset('images/logo.png') }}" alt="SRRU" class="mb-6 h-16 w-16 object-contain">

            @if ($canCheckIn)
                <span class="mb-4 inline-flex items-center gap-2 rounded-full bg-brand-green-500/15 px-4 py-1.5 text-sm font-medium text-brand-green-300">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-green-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-green-400"></span>
                    </span>
                    {{ __('กำลังเช็คชื่อสด') }}
                </span>
            @else
                <span class="mb-4 inline-flex items-center gap-2 rounded-full bg-rose-500/15 px-4 py-1.5 text-sm font-medium text-rose-300">
                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                    {{ __('ปิดรับเช็คชื่อแล้ว') }}
                </span>
            @endif

            <h1 class="font-display text-3xl leading-tight sm:text-4xl lg:text-5xl" style="text-wrap: balance;">{{ $activity->title }}</h1>
            @if ($activity->location_name)
                <p class="mt-3 flex items-center gap-2 text-base text-white/70 sm:text-lg">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    {{ $activity->location_name }}
                </p>
            @endif

            @if ($canCheckIn)
                <ol class="mt-8 hidden space-y-3 text-left text-base text-white/80 lg:block">
                    @foreach ([__('เปิดแอปหรือเว็บ SRRU Check'), __('กดปุ่มสแกน QR ตรงกลางเมนูล่าง'), __('สแกนรหัสนี้ แล้วถ่ายเซลฟียืนยันตัวตน')] as $i => $step)
                        <li class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 font-display text-sm">{{ $i + 1 }}</span>
                            {{ $step }}
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        {{-- QR card --}}
        <div class="flex flex-col items-center">
            <div
                id="qr-container"
                class="flex w-[min(80vw,26rem)] flex-col items-center rounded-[2rem] bg-white p-8 text-slate-900 shadow-[0_30px_80px_-20px_rgb(0_0_0/0.7)] sm:p-10"
                data-fragment-url="{{ route('admin.attendance.qr-fragment', $activity) }}"
            >
                <p class="py-24 text-sm text-slate-500">{{ __('กำลังโหลด QR...') }}</p>
            </div>

            @if ($canCheckIn)
                <p class="mt-6 text-base text-white/70 lg:hidden">{{ __('สแกน QR นี้เพื่อเช็คชื่อเข้าร่วมกิจกรรม') }}</p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-white/70">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        {{ __('เปลี่ยนรหัสทุก') }} <span class="font-semibold text-white">{{ $rotationSeconds }}</span> {{ __('วินาที') }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('มีเวลาส่งข้อมูล') }} <span class="font-semibold text-white">{{ $scanValidityMinutes }}</span> {{ __('นาที') }}
                    </span>
                </div>
                <a href="{{ route('admin.attendance.qr-print', $activity) }}" class="mt-4 inline-flex items-center gap-1.5 text-xs text-white/50 underline decoration-dotted underline-offset-4 transition-colors hover:text-white/80">
                    {{ __('ไม่มีจอแสดงหน้างาน? ดาวน์โหลด QR สำรองสำหรับพิมพ์') }}
                </a>
            @else
                <a href="{{ route('admin.activities.edit', $activity) }}" class="mt-6 rounded-full bg-white/10 px-4 py-2 text-sm text-white/80 transition-colors hover:bg-white/15 hover:text-white">
                    {{ __('ไปที่หน้าแก้ไขกิจกรรมเพื่อเปลี่ยนสถานะ') }} &rarr;
                </a>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .qr-stage {
        background: radial-gradient(90% 60% at 50% 0%, #2a1b52 0%, #120d22 55%, #0b0814 100%);
    }

    .qr-enter { animation: qr-enter 0.6s cubic-bezier(0.16,1,0.3,1) both; }
    @keyframes qr-enter {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes qr-countdown-bar {
        from { width: 100%; }
        to { width: 0%; }
    }
    .qr-countdown-fill {
        animation: qr-countdown-bar linear forwards;
        background: var(--color-brand-purple-600);
    }

    @media (prefers-reduced-motion: reduce) {
        .qr-enter, .qr-countdown-fill { animation: none !important; }
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
