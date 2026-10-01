@php
    $tabItems = [
        ['route' => 'dashboard', 'active' => ['dashboard', 'activity-history.*'], 'label' => __('วันนี้'), 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
        ['route' => 'activities.index', 'active' => ['activities.*', 'self-checkin.*', 'late-checkin.*'], 'label' => __('กิจกรรม'), 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        'scan',
        ['route' => 'hour-requests.index', 'active' => ['hour-requests.*', 'external-activities.*', 'credit-transfers.*'], 'label' => __('คำร้อง'), 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ['route' => 'profile.show', 'active' => ['profile.*', 'profile-setup.*'], 'label' => __('ฉัน'), 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
    ];
@endphp

<nav aria-label="{{ __('เมนูลัด') }}" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950 lg:hidden">
    <div class="mx-auto grid max-w-md grid-cols-5 items-end px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
        @foreach ($tabItems as $item)
            @if ($item === 'scan')
                <a href="{{ route('checkin.show') }}" aria-label="{{ __('สแกน QR เช็คชื่อ') }}"
                    class="-mt-6 flex h-14 w-14 items-center justify-center justify-self-center rounded-[1.25rem] bg-brand-purple-700 text-white shadow-[0_10px_22px_rgb(109_40_217/0.32)] transition hover:bg-brand-purple-800 active:scale-95 dark:bg-brand-purple-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/></svg>
                </a>
            @else
                @php $active = request()->routeIs(...$item['active']); @endphp
                <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                    @class([
                        'flex flex-col items-center gap-0.5 pt-2.5 text-[11px] transition-colors',
                        'font-semibold text-brand-purple-700 dark:text-brand-purple-300' => $active,
                        'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' => ! $active,
                    ])>
                    @php
                        // Red dot: a reviewed request / late check-in result not opened yet (StudentAttention).
                        $dotKey = ['activities.index' => 'activities', 'hour-requests.index' => 'requests'][$item['route']] ?? null;
                        $hasDot = $dotKey && ($studentAttention[$dotKey] ?? 0) > 0;
                    @endphp
                    <span class="relative">
                        <svg class="h-[1.4rem] w-[1.4rem] shrink-0" fill="none" stroke="currentColor" stroke-width="{{ $active ? '1.9' : '1.6' }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        @if ($hasDot)
                            <span class="absolute -right-1 -top-0.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-950" aria-label="{{ __('มีรายการใหม่') }}"></span>
                        @endif
                    </span>
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</nav>
