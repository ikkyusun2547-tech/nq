@props([
    'icon',
    'title',
    // Opt-in only: stretches to fill a taller CSS-grid row (grid's default
    // align-items: stretch already makes the outer box that tall) and
    // spreads the slot's rows out to use that extra height instead of
    // leaving it as dead space below a short list — see the dashboard's
    // two side-by-side breakdown cards for the motivating case. Left off
    // by default since every other user of this component (feed lists,
    // admin tables, etc.) wants natural content-height sizing.
    'fill' => false,
])

<div {{ $attributes->class(['rounded-3xl glass-card', 'flex h-full flex-col' => $fill]) }}>
    <div class="flex items-center gap-3 px-5 pb-3 pt-4">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
        </span>
        <h2 class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h2>
        @isset($action)
            {{ $action }}
        @endisset
    </div>
    <div @class(['border-t border-slate-100 px-5 pb-5 pt-4 dark:border-slate-800', 'flex-1' => $fill])>
        <div @class(['space-y-3.5', 'flex h-full flex-col justify-between' => $fill])>
            {{ $slot }}
        </div>
    </div>
</div>
