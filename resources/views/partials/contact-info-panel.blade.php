@php
    $officePhone = config('services.srru.office_phone');
    $officeEmail = config('services.srru.office_email');
    $officeAddress = config('services.srru.office_address');
    $officeHours = config('services.srru.office_hours');
    $responseTime = config('services.srru.response_time');
@endphp

<div class="rounded-2xl glass-card p-5 shadow-soft">
    <h2 class="mb-3.5 text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('ช่องทางติดต่ออื่น') }}</h2>
    <dl class="space-y-3.5 text-sm">
        @if ($officePhone)
            <div class="flex items-start gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                </span>
                <div class="min-w-0">
                    <dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('โทรศัพท์') }}</dt>
                    <dd><a href="tel:{{ $officePhone }}" class="font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ $officePhone }}</a></dd>
                </div>
            </div>
        @endif

        @if ($officeEmail)
            <div class="flex items-start gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                </span>
                <div class="min-w-0">
                    <dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('อีเมล') }}</dt>
                    <dd class="truncate"><a href="mailto:{{ $officeEmail }}" class="font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ $officeEmail }}</a></dd>
                </div>
            </div>
        @endif

        @if ($officeAddress)
            <div class="flex items-start gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                </span>
                <div class="min-w-0">
                    <dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('ที่อยู่') }}</dt>
                    <dd class="font-medium text-slate-700 dark:text-slate-200">{{ $officeAddress }}</dd>
                </div>
            </div>
        @endif

        <div class="flex items-start gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div class="min-w-0">
                <dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('เวลาทำการ') }}</dt>
                <dd class="font-medium text-slate-700 dark:text-slate-200">{{ $officeHours }}</dd>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
            </span>
            <div class="min-w-0">
                <dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('ระยะเวลาตอบกลับแชท') }}</dt>
                <dd class="font-medium text-slate-700 dark:text-slate-200">{{ $responseTime }}</dd>
            </div>
        </div>
    </dl>

    @if ($officeAddress)
        {{-- No Google Maps API key configured for this project, so this uses
             the classic key-free embed URL (maps.google.com/maps?q=...&output=embed)
             rather than the official Embed API — same map, just doesn't need
             billing/API setup. --}}
        <div class="mt-4 overflow-hidden rounded-xl ring-1 ring-slate-100 dark:ring-slate-800">
            <iframe
                src="https://maps.google.com/maps?q={{ urlencode($officeAddress) }}&t=&z=16&ie=UTF8&iwloc=&output=embed"
                class="h-48 w-full border-0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="{{ __('แผนที่ที่ตั้งกองพัฒนานักศึกษา') }}"
            ></iframe>
        </div>
        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($officeAddress) }}" target="_blank" rel="noopener noreferrer"
            class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            {{ __('เปิดใน Google Maps เพื่อนำทาง') }}
        </a>
    @endif
</div>
