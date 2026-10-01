{{-- simplePaginate() variant: previous / next only, same look as tailwind.blade.php. --}}
@if ($paginator->hasPages())
    @php
        $btn = 'flex h-9 items-center gap-1.5 rounded-full px-4 text-sm font-medium transition-colors';
        $idle = 'border border-slate-200 bg-white text-slate-700 hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:text-brand-purple-300';
        $off = 'cursor-default border border-slate-100 text-slate-300 dark:border-slate-800 dark:text-slate-600';
    @endphp
    <nav role="navigation" aria-label="{{ __('การแบ่งหน้า') }}" class="flex items-center justify-between gap-2">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="{{ $btn }} {{ $off }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                {{ __('หน้าก่อนหน้า') }}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btn }} {{ $idle }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                {{ __('หน้าก่อนหน้า') }}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btn }} {{ $idle }}">
                {{ __('หน้าถัดไป') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        @else
            <span aria-disabled="true" class="{{ $btn }} {{ $off }}">
                {{ __('หน้าถัดไป') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </span>
        @endif
    </nav>
@endif
