@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'decorated' => false,
    'pinActions' => false,
])

<div {{ $attributes->class(['relative mb-4 sm:mb-6 overflow-hidden rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8']) }}>
    @if ($decorated)
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/5 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-brand-green-500/10 blur-2xl"></div>
    @endif

    @php
        $reservesCornerSpace = $pinActions && isset($actions) && $actions->isNotEmpty();
    @endphp
    <div class="relative flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="{{ $reservesCornerSpace ? 'pr-24 sm:pr-28' : '' }} text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ $eyebrow }}</p>
            @endif
            {{-- Only the eyebrow/title reserve room for the pinned corner
                 badge — it's only as tall as one short line, so squeezing
                 the subtitle and everything below it too just wrapped that
                 text earlier than the available width actually required. --}}
            <h1
                x-init="
                    const el = $el;
                    const fit = () => {
                        el.style.fontSize = '';
                        el.style.whiteSpace = 'nowrap';
                        const base = parseFloat(getComputedStyle(el).fontSize);
                        let size = base;
                        while (el.scrollWidth > el.clientWidth && size > 13) {
                            size -= 1;
                            el.style.fontSize = size + 'px';
                        }
                        if (el.scrollWidth > el.clientWidth) el.style.whiteSpace = 'normal';
                    };
                    fit();
                    window.addEventListener('resize', fit);
                "
                class="{{ $eyebrow ? 'mt-1' : '' }} {{ $reservesCornerSpace ? 'pr-24 sm:pr-28' : '' }} text-xl font-bold text-white sm:text-2xl"
            >{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1.5 text-sm font-light text-violet-100/80">{{ $subtitle }}</p>
            @endif
            @if ($slot->isNotEmpty())
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    {{ $slot }}
                </div>
            @endif
        </div>

        @if (isset($actions) && $actions->isNotEmpty())
            <div @class([
                // No shrink-0 + flex-wrap: a page with one short action (the
                // common case) is unaffected, since it already fits on one
                // line and nothing shrinks or wraps. A page with two-plus
                // actions (e.g. admin/attendance/index.blade.php) that don't
                // jointly fit a narrow screen now shrinks/wraps onto its own
                // stacked lines instead of overflowing off the right edge.
                'flex flex-wrap items-center gap-2' => ! $pinActions,
                'flex shrink-0 items-center gap-2' => $pinActions,
                'absolute right-0 top-0' => $pinActions,
            ])>
                {{ $actions }}
            </div>
        @endif
    </div>

    @isset($footer)
        <div class="relative mt-6 border-t border-white/10 pt-4">
            {{ $footer }}
        </div>
    @endisset
</div>
