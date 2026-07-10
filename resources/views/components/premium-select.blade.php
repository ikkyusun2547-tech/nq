@props([
    'name',
    'options' => null,
    'groups' => null,
    'selected' => null,
    'placeholder' => '-- เลือก --',
    'autosubmit' => false,
    'resets' => null,
    'nullable' => true,
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
        get label() {
            const match = this.options.find(o => ! o.heading && o.value === this.selected);
            return match ? match.label : this.placeholderText;
        },
        pick(opt) {
            this.selected = opt.value;
            this.open = false;
            this.$refs.native.value = opt.value;
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
>
    {{-- Real <select> stays in the DOM (visually hidden) so the form posts
         normally and no JS-disabled fallback is needed. --}}
    <select
        x-ref="native" name="{{ $name }}" tabindex="-1" aria-hidden="true"
        class="pointer-events-none absolute h-px w-px overflow-hidden opacity-0"
        x-on:change="syncFromNative()"
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
        type="button" @click="open = ! open" aria-haspopup="listbox" :aria-expanded="open"
        class="flex w-full items-center justify-between gap-2 rounded-xl border bg-white py-2.5 pl-3.5 pr-3 text-left text-sm shadow-soft transition-all duration-200 dark:bg-slate-800"
        :class="open
            ? '{{ $hasError ? 'border-red-400 ring-4 ring-red-500/10' : 'border-brand-purple-500 ring-4 ring-brand-purple-500/10' }} text-slate-900 dark:text-slate-100'
            : '{{ $hasError ? 'border-red-300 dark:border-red-500/70' : 'border-slate-200 dark:border-slate-600' }} text-slate-700 hover:border-brand-purple-300 dark:text-slate-100 dark:hover:border-brand-purple-500/50'"
    >
        <span class="truncate" :class="selected === '' && 'text-slate-400 dark:text-slate-500'" x-text="label"></span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
    </button>

    <div
        x-show="open" x-cloak role="listbox"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute z-30 mt-2 max-h-64 w-full min-w-max overflow-auto rounded-2xl border border-slate-100 bg-white/95 p-1.5 shadow-soft-lg backdrop-blur-sm dark:border-slate-700 dark:bg-slate-800/95"
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
</div>
