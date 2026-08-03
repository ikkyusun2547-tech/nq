@props([
    'name',
    'options' => null,
    'groups' => null,
    'selected' => null,
    'placeholder' => '-- เลือก --',
    'autosubmit' => false,
    'resets' => null,
    'nullable' => true,
    // Name of an ancestor Alpine variable holding an already-shaped
    // [{value, label}, ...] array — for options that only exist once a
    // parent field is chosen (e.g. majors depending on faculty), where the
    // list can't be rendered server-side up front like a normal `options` prop.
    'liveOptions' => null,
    // Name of an ancestor Alpine variable to keep two-way synced with the
    // internal `selected` value, for cases where sibling logic outside this
    // component (e.g. "load majors for this faculty") needs to react to it.
    'liveSelected' => null,
    // Raw Alpine boolean expression (string) controlling both the trigger
    // button and the underlying native select, e.g. "! facultyId".
    'disabled' => null,
])

@php
    // Flatten either a plain value=>label list or a group-label => [value=>label]
    // list into one array Alpine can x-for over, tagging group headings so the
    // panel can render dividers without a second component to maintain.
    $flat = [];
    if ($groups) {
        foreach ($groups as $groupLabel => $items) {
            $flat[] = ['heading' => true, 'label' => $groupLabel];
            foreach ($items as $value => $label) {
                $flat[] = ['heading' => false, 'value' => (string) $value, 'label' => $label];
            }
        }
    } else {
        foreach (($options ?? []) as $value => $label) {
            $flat[] = ['heading' => false, 'value' => (string) $value, 'label' => $label];
        }
    }
    $selectedStr = $selected === null ? '' : (string) $selected;

    // A non-nullable select has no blank option, so a native <select> would
    // silently default its *submitted* value to the first <option> even
    // while nothing matches `selected` — mirror that here so the displayed
    // label always agrees with what the hidden select would actually post.
    if (! $nullable && $selectedStr === '') {
        $firstReal = collect($flat)->first(fn ($item) => ! $item['heading']);
        $selectedStr = $firstReal['value'] ?? '';
    }

    $hasError = $errors->has($name);
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        selected: @js($selectedStr),
        placeholderText: @js($placeholder),
        options: @js($flat),
        toggleOpen() {
            this.open = ! this.open;
            if (this.open) this.$nextTick(() => this.position());
        },
        // The panel is teleported to <body> (see the template below) so it
        // can't get clipped or visually swallowed by an overflow:auto
        // ancestor — e.g. the mobile filter sheet's own scroll container,
        // which used to squeeze the options list flush against the sheet's
        // edge instead of letting it float freely over the page. Teleporting
        // means it's no longer positioned via the trigger's `relative`
        // parent, so its coordinates are computed here instead, the same
        // technique the notification-bell panel already uses.
        position() {
            const trigger = this.$refs.trigger;
            const panel = this.$refs.panel;
            if (! trigger || ! panel) return;

            const margin = 12;
            const rect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom - margin;
            const spaceAbove = rect.top - margin;

            panel.style.left = rect.left + 'px';
            panel.style.width = rect.width + 'px';
            panel.style.maxHeight = 'none';
            panel.style.top = (rect.bottom + 8) + 'px';
            panel.style.bottom = 'auto';

            // Measure the panel's real, uncapped height (short list vs a
            // long grouped one need very different room) before deciding
            // anything — then cap it to whichever side actually has more
            // space, rather than a fixed max-height that squeezed a long
            // list down to a cramped little scrollbox flush against
            // whatever container the trigger happened to sit inside (e.g.
            // the mobile filter sheet). Being teleported to <body> means
            // that generous height is free to spill out over the sheet's
            // own edge instead of being confined inside it.
            const naturalHeight = panel.offsetHeight;
            const opensDown = naturalHeight <= spaceBelow || spaceAbove <= spaceBelow;

            // Still capped even when the room technically exists — a very
            // long grouped list (e.g. every major across every faculty)
            // filling nearly the whole screen reads as overwhelming, not
            // helpful, so it scrolls internally past this point same as
            // before, just with more breathing room than the old fixed 256px.
            const cap = Math.min(window.innerHeight * 0.5, 360);

            if (opensDown) {
                panel.style.maxHeight = Math.min(naturalHeight, spaceBelow, cap) + 'px';
            } else {
                panel.style.top = 'auto';
                panel.style.bottom = (window.innerHeight - rect.top + 8) + 'px';
                panel.style.maxHeight = Math.min(naturalHeight, spaceAbove, cap) + 'px';
            }
        },
        get label() {
            const match = this.options.find(o => ! o.heading && o.value === this.selected);
            return match ? match.label : this.placeholderText;
        },
        pick(opt) {
            this.selected = opt.value;
            this.open = false;
            this.$refs.native.value = opt.value;

            @if ($liveSelected)
                {{ $liveSelected }} = opt.value;
            @endif

            // Dispatched after the liveSelected write above so a sibling
            // @change handler (e.g. reloading majors for a newly picked
            // faculty) reads the updated value instead of the stale one.
            this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));

            @if ($resets)
                const target = this.$refs.native.form?.querySelector('[name=\'{{ $resets }}\']');
                if (target) {
                    target.value = '';
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                }
            @endif

            @if ($autosubmit)
                this.$refs.native.form?.submit();
            @endif
        },
        syncFromNative() {
            this.selected = this.$refs.native.value;
        },
    }"
    @keydown.escape="open = false"
    @click.outside="open = false"
    @if ($liveOptions)
        x-init="options = {{ $liveOptions }}; $watch('{{ $liveOptions }}', (val) => { options = val; })"
    @endif
>
    {{-- Real <select> stays in the DOM (visually hidden) so the form posts
         normally and no JS-disabled fallback is needed. --}}
    <select
        x-ref="native" name="{{ $name }}" tabindex="-1" aria-hidden="true"
        class="pointer-events-none absolute h-px w-px overflow-hidden opacity-0"
        x-on:change="syncFromNative()"
        @if ($disabled) :disabled="{{ $disabled }}" @endif
        {{ $attributes }}
    >
        @if ($nullable)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($flat as $item)
            @if (! $item['heading'])
                <option value="{{ $item['value'] }}" @selected($selectedStr === $item['value'])>{{ $item['label'] }}</option>
            @endif
        @endforeach
    </select>

    <button
        x-ref="trigger"
        type="button" @click="toggleOpen()" aria-haspopup="listbox" :aria-expanded="open"
        @if ($disabled) :disabled="{{ $disabled }}" @endif
        class="flex w-full items-center justify-between gap-2 rounded-xl border bg-white py-2.5 {{ isset($icon) ? 'pl-10' : 'pl-3.5' }} pr-3 text-left text-sm shadow-soft transition-all duration-200 dark:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400 dark:disabled:bg-slate-800/60 dark:disabled:text-slate-500"
        :class="open
            ? '{{ $hasError ? 'border-red-400 ring-4 ring-red-500/10' : 'border-brand-purple-500 ring-4 ring-brand-purple-500/10' }} text-slate-900 dark:text-slate-100'
            : '{{ $hasError ? 'border-red-300 dark:border-red-500/70' : 'border-slate-200 dark:border-slate-600' }} text-slate-700 hover:border-brand-purple-300 dark:text-slate-100 dark:hover:border-brand-purple-500/50'"
    >
        @isset($icon)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">{{ $icon }}</span>
        @endisset
        <span class="truncate" :class="selected === '' && 'text-slate-400 dark:text-slate-500'" x-text="label"></span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
    </button>

    <template x-teleport="body">
        <div
            x-ref="panel"
            x-show="open" x-cloak role="listbox"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed z-[60] overflow-auto rounded-2xl border border-slate-100 bg-white/95 p-1.5 shadow-soft-lg backdrop-blur-sm dark:border-slate-700 dark:bg-slate-800/95"
        >
            @if ($nullable)
                <button
                    type="button" @click="pick({ value: '', label: placeholderText })"
                    class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-brand-purple-50 dark:hover:bg-slate-700/70"
                    :class="selected === '' ? 'font-medium text-brand-purple-700 dark:text-brand-purple-400' : 'text-slate-500 dark:text-slate-400'"
                >
                    {{ $placeholder }}
                </button>
            @endif

            <template x-for="(opt, idx) in options" :key="idx">
                <div>
                    <p x-show="opt.heading" class="mt-1.5 select-none px-3 pb-1 pt-2 text-[0.68rem] font-semibold uppercase tracking-wide text-brand-purple-400 first:mt-0 dark:text-brand-purple-500/70" x-text="opt.label"></p>
                    <button
                        x-show="! opt.heading"
                        type="button" @click="pick(opt)" role="option" :aria-selected="selected === opt.value"
                        class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-brand-purple-50 dark:hover:bg-slate-700/70"
                        :class="selected === opt.value ? 'bg-brand-purple-50 font-medium text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400' : 'text-slate-600 dark:text-slate-300'"
                    >
                        <span class="truncate" x-text="opt.label"></span>
                        <svg x-show="selected === opt.value" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </button>
                </div>
            </template>
        </div>
    </template>
</div>
