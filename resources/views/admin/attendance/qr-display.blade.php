@extends('layouts.app')

@section('content')
<div class="mx-auto flex min-h-screen max-w-lg flex-col items-center justify-center px-4 py-8 text-center">
    <h1 class="mb-1 text-lg font-semibold text-gray-900">{{ $activity->title }}</h1>
    <p class="mb-6 text-sm text-gray-500">สแกน QR นี้เพื่อเช็กชื่อเข้าร่วมกิจกรรม</p>

    <div
        id="qr-container"
        class="flex flex-col items-center rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200"
        data-fragment-url="{{ route('admin.attendance.qr-fragment', $activity) }}"
    >
        <p class="text-sm text-gray-400">กำลังโหลด QR...</p>
    </div>

    <p class="mt-4 text-xs text-gray-400">QR จะเปลี่ยนรหัสอัตโนมัติทุก 15 วินาทีเพื่อความปลอดภัย</p>
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
