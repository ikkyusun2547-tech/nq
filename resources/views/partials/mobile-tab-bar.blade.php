@php
    $tabItems = [
        ['route' => 'dashboard', 'label' => __('แดชบอร์ด'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'activities.index', 'label' => __('กิจกรรม'), 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        ['route' => 'hour-requests.index', 'label' => __('ขอชั่วโมง'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['route' => 'profile.show', 'label' => __('โปรไฟล์'), 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 rounded-t-3xl bg-white px-2 pt-2 shadow-soft-lg dark:bg-slate-900 md:hidden">
    <div class="flex items-stretch justify-between gap-1 pb-[env(safe-area-inset-bottom)]">
        @foreach ($tabItems as $item)
            @php $active = request()->routeIs($item['route'].'*'); @endphp
            <a href="{{ route($item['route']) }}" class="flex flex-1 flex-col items-center gap-1 rounded-2xl px-2 py-2 transition-colors duration-200 {{ $active ? 'bg-brand-purple-700' : '' }}">
                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-white' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                <span class="text-[11px] font-medium {{ $active ? 'text-white' : 'text-slate-400 dark:text-slate-500' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
