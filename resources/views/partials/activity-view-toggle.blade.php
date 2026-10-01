{{-- "แบบรายการ | แบบปฏิทิน" switch between an activity list page and its calendar twin.
     Expects $listRoute, $calendarRoute and $active ('list'|'calendar'). --}}
<nav aria-label="{{ __('มุมมอง') }}" class="inline-flex rounded-full bg-slate-100 p-1 dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
    @foreach (['list' => [$listRoute, __('แบบรายการ'), 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z'],
               'calendar' => [$calendarRoute, __('แบบปฏิทิน'), 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5']] as $key => [$routeName, $label, $icon])
        <a href="{{ route($routeName) }}" @if ($active === $key) aria-current="page" @endif
            @class([
                'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm transition-colors',
                'bg-white font-semibold text-brand-purple-700 shadow-soft dark:bg-slate-700 dark:text-white' => $active === $key,
                'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => $active !== $key,
            ])>
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            {{ $label }}
        </a>
    @endforeach
</nav>
