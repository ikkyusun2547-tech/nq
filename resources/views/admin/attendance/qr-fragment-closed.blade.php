@php
    $statusLabel = [
        'draft' => __('ร่าง'), 'open' => __('เปิดรับสมัคร'), 'full' => __('เต็มแล้ว'),
        'ongoing' => __('กำลังดำเนินการ'), 'closed' => __('ปิดกิจกรรม'), 'cancelled' => __('ถูกยกเลิก'),
    ];
@endphp

<div class="flex flex-col items-center py-6">
    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400">
        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
    </span>
    <p class="mb-1 text-base font-semibold text-gray-900 dark:text-slate-100">{{ __('กิจกรรมนี้ปิดรับเช็กชื่อแล้ว') }}</p>
    <p class="text-sm text-gray-400 dark:text-slate-500">
        {{ __('สถานะปัจจุบัน:') }} <span class="font-medium text-red-500 dark:text-red-400">{{ $statusLabel[$activity->status] ?? $activity->status }}</span>
    </p>
    <p class="mt-3 max-w-[16rem] text-xs text-gray-400 dark:text-slate-500">{{ __('เปลี่ยนสถานะกิจกรรมเป็น "เปิดรับสมัคร" หรือ "กำลังดำเนินการ" เพื่อเปิดใช้ QR เช็กชื่ออีกครั้ง') }}</p>
</div>
