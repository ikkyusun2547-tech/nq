@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'decorated' => false,
    'pinActions' => false,
])

{{-- Design C page header: plain type on the page, no banner. `decorated`
     is accepted for backward compatibility but no longer draws anything. --}}
<div {{ $attributes->class(['page-header mb-5 sm:mb-6']) }}>
    <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-3">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $eyebrow }}</p>
            @endif
            <h1 class="{{ $eyebrow ? 'mt-1' : '' }} font-display text-2xl leading-tight text-slate-900 dark:text-slate-50 sm:text-[1.75rem]" style="text-wrap: balance;">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
            @endif
            @if ($slot->isNotEmpty())
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    {{ $slot }}
                </div>
            @endif
        </div>

        @if (isset($actions) && $actions->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>

    @isset($footer)
        <div class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-800">
            {{ $footer }}
        </div>
    @endisset
</div>
