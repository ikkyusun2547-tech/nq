@extends('layouts.app')

@section('content')
<div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-8 text-center brand-gradient">
    <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>

    <a href="{{ route('admin.activities.index') }}" class="absolute left-4 top-4 text-sm text-white/70 hover:text-white">&larr; {{ __('กลับ') }}</a>

    <span class="relative mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-sm font-bold text-white ring-1 ring-white/30">SR</span>
    <h1 class="relative mb-1 text-xl font-semibold text-white">{{ $activity->title }}</h1>
    <p class="relative mb-6 text-sm text-white/80">{{ __('สแกน QR นี้เพื่อเช็กชื่อเข้าร่วมกิจกรรม') }}</p>

    <div
        id="qr-container"
        class="relative flex flex-col items-center rounded-3xl bg-white dark:bg-slate-900 p-8 shadow-xl"
        data-fragment-url="{{ route('admin.attendance.qr-fragment', $activity) }}"
    >
        <p class="text-sm text-gray-400 dark:text-slate-500">{{ __('กำลังโหลด QR...') }}</p>
    </div>

    <p class="relative mt-4 text-xs text-white/70">{{ __('QR จะเปลี่ยนรหัสอัตโนมัติทุก 15 วินาทีเพื่อความปลอดภัย') }}</p>
</div>
@endsection

@push('scripts')
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
