@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'back' => null,
    'decorated' => false,
])

<div {{ $attributes->class(['relative mb-4 sm:mb-6 overflow-hidden rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8']) }}>
    @if ($decorated)
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/5 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-brand-green-500/10 blur-2xl"></div>
    @endif

    <div class="relative flex flex-wrap items-start justify-between gap-3">
        <div>
            @if ($eyebrow)
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ $eyebrow }}</p>
            @endif
            <h1 class="{{ $eyebrow ? 'mt-1' : '' }} text-xl font-bold text-white sm:text-2xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1.5 text-sm font-light text-violet-100/80">{{ $subtitle }}</p>
            @endif
        </div>

        @if (isset($actions) && $actions->isNotEmpty())
            <div class="flex shrink-0 items-center gap-2">
                {{ $actions }}
            </div>
        @elseif ($back)
            <a href="{{ $back }}"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-white/10 px-3.5 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                {{ __('กลับ') }}
            </a>
        @endif
    </div>

    @isset($footer)
        <div class="relative mt-6 border-t border-white/10 pt-4">
            {{ $footer }}
        </div>
    @endisset
</div>
