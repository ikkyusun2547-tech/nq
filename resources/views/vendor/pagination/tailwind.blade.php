{{--
    Default paginator for the whole app (every ->links()).
    Phones get a compact "‹ หน้า 2 / 5 ›"; wider screens get the full row of
    page numbers in one pill-shaped group.
--}}
@if ($paginator->hasPages())
    @php
        $btn = 'flex h-9 min-w-9 shrink-0 items-center justify-center rounded-full px-2 text-sm transition-colors';
        $idle = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white';
        $off = 'cursor-default text-slate-300 dark:text-slate-600';
        $chevronLeft = 'M15.75 19.5L8.25 12l7.5-7.5';
        $chevronRight = 'M8.25 4.5l7.5 7.5-7.5 7.5';
    @endphp
    <nav role="navigation" aria-label="{{ __('การแบ่งหน้า') }}" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            @if ($paginator->firstItem())
                {{ __('แสดง') }}
                <span class="font-semibold text-slate-800 dark:text-slate-100">{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}</span>
                {{ __('จากทั้งหมด') }}
                <span class="font-semibold text-slate-800 dark:text-slate-100">{{ number_format($paginator->total()) }}</span>
                {{ __('รายการ') }}
            @else
                {{ __('ทั้งหมด :total รายการ', ['total' => number_format($paginator->total())]) }}
            @endif
        </p>

        <div class="flex items-center gap-0.5 rounded-full border border-slate-200 bg-white p-1 dark:border-slate-800 dark:bg-slate-900">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('หน้าก่อนหน้า') }}" class="{{ $btn }} {{ $off }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevronLeft }}"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('หน้าก่อนหน้า') }}" class="{{ $btn }} {{ $idle }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevronLeft }}"/></svg>
                </a>
            @endif

            {{-- Phones: just "page X / Y" --}}
            <span class="px-3 text-sm text-slate-600 dark:text-slate-300 sm:hidden">
                {{ __('หน้า') }} <span class="font-semibold text-slate-900 dark:text-white">{{ $paginator->currentPage() }}</span> / {{ $paginator->lastPage() }}
            </span>

            {{-- Wider screens: page numbers --}}
            <span class="hidden items-center gap-0.5 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $btn }} {{ $off }}">…</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $btn }} bg-brand-purple-700 font-semibold text-white dark:bg-brand-purple-600">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('ไปหน้า :page', ['page' => $page]) }}" class="{{ $btn }} {{ $idle }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </span>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('หน้าถัดไป') }}" class="{{ $btn }} {{ $idle }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevronRight }}"/></svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('หน้าถัดไป') }}" class="{{ $btn }} {{ $off }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chevronRight }}"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
