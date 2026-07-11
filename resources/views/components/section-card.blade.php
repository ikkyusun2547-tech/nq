@props([
    'icon',
    'title',
])

<div {{ $attributes->class(['rounded-2xl glass-card shadow-soft']) }}>
    <div class="flex items-center gap-3 px-5 pb-3 pt-4">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
        </span>
        <h2 class="flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h2>
        @isset($action)
            {{ $action }}
        @endisset
    </div>
    <div class="border-t border-brand-purple-100 px-5 pb-5 pt-4 dark:border-slate-700/60">
        <div class="space-y-3.5">
            {{ $slot }}
        </div>
    </div>
</div>
