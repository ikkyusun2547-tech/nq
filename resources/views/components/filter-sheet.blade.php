{{--
    Mobile bottom sheet of chip filters for a GET filter form.

    Every option is a radio "chip"; nothing submits until "แสดงผลลัพธ์", so
    several filters can be picked in one go. Must sit inside an Alpine scope
    that has `filtersOpen` and `isDesktop` (the desktop toolbar renders the
    same fields, so these radios are disabled on desktop and only one copy is
    ever submitted). The sheet is teleported to <body>, so the radios join
    the form through the `form` attribute.

    groups: list of [
        'name' => field name, 'label' => heading, 'options' => [value => label],
        'selected' => current value, 'all' => label of the "everything" chip
        (omit for a field that always has a value),
        'dots' => [value => tailwind bg class] (optional colour dot),
        'dependsOn' => other field name + 'optionsByParent' => [parentValue => [value => label]]
            (optional: e.g. majors shown for the picked faculty only),
    ]
--}}
@props(['form', 'groups', 'clearUrl'])

@php
    $chip = 'inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-700 transition-colors peer-checked:border-brand-purple-700 peer-checked:bg-brand-purple-700 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-brand-purple-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:peer-checked:border-brand-purple-500 dark:peer-checked:bg-brand-purple-600';
    $initial = collect($groups)->mapWithKeys(fn ($g) => [$g['name'] => (string) ($g['selected'] ?? '')])->all();
@endphp

<template x-teleport="body">
    <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-50 sm:hidden" @keydown.escape.window="filtersOpen = false"
        x-data="{
            picked: @js($initial),
            // Picking a parent (e.g. faculty) resets its dependent field (major) to "all".
            pick(name, value) {
                this.picked[name] = value;
                document.querySelectorAll(`input[data-depends='${name}']`).forEach((r) => {
                    if (r.value === '') { r.checked = true; this.picked[r.name] = ''; }
                });
            },
        }">
        <div x-show="filtersOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="filtersOpen = false"></div>
        <div
            x-show="filtersOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            class="absolute inset-x-0 bottom-0 flex max-h-[88vh] flex-col rounded-t-[1.75rem] bg-white dark:bg-slate-900"
        >
            <div class="px-5 pt-3">
                <div class="mx-auto h-1.5 w-10 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                <div class="mt-3 flex items-center justify-between">
                    <h3 class="font-display text-xl text-slate-900 dark:text-white">{{ __('ตัวกรอง') }}</h3>
                    <button type="button" @click="filtersOpen = false" aria-label="{{ __('ปิด') }}"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto px-5 pb-4 pt-5">
                @foreach ($groups as $group)
                    @php
                        $name = $group['name'];
                        $selected = (string) ($group['selected'] ?? '');
                        $parent = $group['dependsOn'] ?? null;
                        // Flat [value => [label, parentValue|null]] so dependent chips can be shown per parent.
                        $chips = isset($group['all']) ? ['' => [$group['all'], null]] : [];
                        if ($parent) {
                            foreach ($group['optionsByParent'] as $parentValue => $options) {
                                foreach ($options as $value => $label) {
                                    $chips[$value] = [$label, (string) $parentValue];
                                }
                            }
                        } else {
                            foreach ($group['options'] as $value => $label) {
                                $chips[$value] = [$label, null];
                            }
                        }
                    @endphp
                    <fieldset>
                        <legend class="mb-2.5 text-sm font-semibold text-slate-900 dark:text-white">{{ $group['label'] }}</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($chips as $value => [$label, $parentValue])
                                <label class="relative"
                                    @if ($parentValue !== null) x-show="picked['{{ $parent }}'] === @js($parentValue)" @endif>
                                    <input type="radio" form="{{ $form }}" name="{{ $name }}" value="{{ $value }}" class="peer sr-only"
                                        x-bind:disabled="isDesktop" @checked($selected === (string) $value)
                                        @if ($parent) data-depends="{{ $parent }}" @endif
                                        @change="pick('{{ $name }}', $event.target.value)">
                                    <span class="{{ $chip }}">
                                        @if (isset($group['dots'][$value]))
                                            <span class="h-2 w-2 rounded-full {{ $group['dots'][$value] }}"></span>
                                        @endif
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($parent)
                            <p x-show="picked['{{ $parent }}'] === ''" class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('เลือกคณะก่อน เพื่อแสดงสาขา') }}</p>
                        @endif
                    </fieldset>
                @endforeach
            </div>

            <div class="flex gap-3 border-t border-slate-100 px-5 pb-[calc(1rem+env(safe-area-inset-bottom))] pt-4 dark:border-slate-800">
                <a href="{{ $clearUrl }}"
                    class="flex h-12 flex-1 items-center justify-center rounded-full border border-slate-200 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                    {{ __('ล้างทั้งหมด') }}
                </a>
                <button type="submit" form="{{ $form }}"
                    class="flex h-12 flex-[2] items-center justify-center rounded-full bg-brand-purple-700 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">
                    {{ __('แสดงผลลัพธ์') }}
                </button>
            </div>
        </div>
    </div>
</template>
