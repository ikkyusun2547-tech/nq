@php
    $adminTabItems = [
        ['route' => 'admin.dashboard', 'label' => __('แดชบอร์ด'), 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
        ['route' => 'admin.activities.index', 'label' => __('กิจกรรม'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
    ];

    // Fixed per-page parent, not browser history — a route name not listed
    // here (every other top-level index page, plus the dashboard itself)
    // falls back to the dashboard, since none of those have a more specific
    // "list" above them.
    $adminBackRoute = match (true) {
        request()->routeIs('admin.activities.create', 'admin.activities.edit', 'admin.activities.calendar', 'admin.attendance.index') => 'admin.activities.index',
        request()->routeIs('admin.announcements.create') => 'admin.announcements.index',
        request()->routeIs('admin.credit-transfer-positions.create', 'admin.credit-transfer-positions.edit') => 'admin.credit-transfer-positions.index',
        request()->routeIs('admin.faculties.create', 'admin.faculties.edit') => 'admin.faculties.index',
        request()->routeIs(
            'admin.reports.activity-participation',
            'admin.reports.at-risk',
            'admin.reports.category',
            'admin.reports.clearance',
            'admin.reports.faculty-participation',
            'admin.reports.request-stats',
        ) => 'admin.reports.index',
        request()->routeIs('admin.settings.create', 'admin.settings.edit') => 'admin.settings.index',
        request()->routeIs('admin.students.import.create', 'admin.students.show') => 'admin.students.index',
        default => 'admin.dashboard',
    };
@endphp

{{--
    Only 2 real destinations plus a "ย้อนกลับ" action: admin has far more
    sections than a bottom bar can hold, so this isn't a full nav
    replacement — just quick access to the two most-visited pages, with the
    header's menu sheet still the way to reach everything else. "ย้อนกลับ"
    covers drilling into an activity's attendance/detail page and needing
    to step back out.
--}}
<nav aria-label="{{ __('เมนูลัด') }}" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950 lg:hidden">
    <div class="mx-auto flex max-w-md items-stretch justify-between gap-1 px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
        @foreach ($adminTabItems as $item)
            @php $active = request()->routeIs($item['route'].'*'); @endphp
            <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                @class([
                    'flex flex-1 flex-col items-center gap-0.5 pt-2.5 text-[11px] transition-colors',
                    'font-semibold text-brand-purple-700 dark:text-brand-purple-300' => $active,
                    'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' => ! $active,
                ])>
                <svg class="h-[1.4rem] w-[1.4rem] shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        {{-- Fixed to each page's logical parent list (see $adminBackRoute
             above), not browser history — always lands on the same page
             regardless of how the admin arrived (direct link, refresh,
             bookmark, ...), unlike history.back() which depends on
             whatever happens to be in this tab's history. --}}
        <a href="{{ route($adminBackRoute) }}" class="flex flex-1 flex-col items-center gap-0.5 pt-2.5 text-[11px] text-slate-500 transition-colors hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">
            <svg class="h-[1.4rem] w-[1.4rem] shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            {{ __('ย้อนกลับ') }}
        </a>
    </div>
</nav>
