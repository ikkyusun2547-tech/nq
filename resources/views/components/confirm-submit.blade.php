@props([
    'message',
    'label',
    'tone' => 'red',
    'title' => null,
])

@php
    $palette = [
        'red' => [
            'icon_bg' => 'bg-red-50 ring-red-50/50 dark:bg-red-500/10 dark:ring-red-500/5',
            'icon_text' => 'text-red-600 dark:text-red-400',
            'border' => 'to-red-200/40 dark:to-red-500/20',
            'button' => 'bg-gradient-to-r from-red-600 to-red-500',
        ],
        'green' => [
            'icon_bg' => 'bg-brand-green-50 ring-brand-green-50/50 dark:bg-brand-green-500/10 dark:ring-brand-green-500/5',
            'icon_text' => 'text-brand-green-600 dark:text-brand-green-400',
            'border' => 'to-brand-green-200/40 dark:to-brand-green-500/20',
            'button' => 'bg-gradient-to-r from-brand-green-600 to-brand-green-500',
        ],
        'purple' => [
            'icon_bg' => 'bg-brand-purple-50 ring-brand-purple-50/50 dark:bg-brand-purple-500/10 dark:ring-brand-purple-500/5',
            'icon_text' => 'text-brand-purple-600 dark:text-brand-purple-400',
            'border' => 'to-brand-purple-200/40 dark:to-brand-purple-500/20',
            'button' => 'bg-gradient-to-r from-brand-purple-600 to-brand-purple-500',
        ],
        'amber' => [
            'icon_bg' => 'bg-amber-50 ring-amber-50/50 dark:bg-amber-500/10 dark:ring-amber-500/5',
            'icon_text' => 'text-amber-600 dark:text-amber-400',
            'border' => 'to-amber-200/40 dark:to-amber-500/20',
            'button' => 'bg-gradient-to-r from-amber-600 to-amber-500',
        ],
        'slate' => [
            'icon_bg' => 'bg-slate-100 ring-slate-100/50 dark:bg-slate-800 dark:ring-slate-800/50',
            'icon_text' => 'text-slate-500 dark:text-slate-400',
            'border' => 'to-slate-200/60 dark:to-slate-500/20',
            'button' => 'bg-gradient-to-r from-slate-600 to-slate-500',
        ],
    ];
    $c = $palette[$tone] ?? $palette['red'];
@endphp

{{--
    Wraps a form's submit action behind a styled confirmation modal instead
    of the native browser confirm() popup — drop this in place of the
    trigger <button>; the modal's "confirm" button submits the *enclosing*
    <form> exactly like the original button did.

    The modal is teleported to <body>: left in place, a `fixed` overlay
    gets trapped inside any ancestor with backdrop-filter (e.g. .glass-card
    around admin tables) and clipped by its overflow. That moves it out of
    the <form>, hence submitting via the form captured at init.
--}}
<div x-data="{ open: false, form: null }" x-init="form = $el.closest('form')" class="contents">
    <button type="button" @click="open = true" {{ $attributes }}>{{ $slot }}</button>

    <template x-teleport="body">
    <div x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm"
        @keydown.escape.window="open = false">
        <div
            @click.outside="open = false"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-sm rounded-[2rem] bg-gradient-to-br from-white/60 via-white/10 {{ $c['border'] }} p-[1.5px] shadow-soft-lg dark:from-white/10 dark:via-white/5"
        >
            <div class="rounded-[calc(2rem-1.5px)] bg-white p-7 text-center dark:bg-slate-900">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full ring-8 {{ $c['icon_bg'] }}">
                    <svg class="h-8 w-8 shrink-0 {{ $c['icon_text'] }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.362-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-slate-100">{{ $title ?? __('ยืนยันการดำเนินการ') }}</h3>
                <p class="mx-auto mt-2 max-w-[15rem] text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $message }}</p>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <button type="button" @click="open = false"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-800">
                        {{ __('ยกเลิก') }}
                    </button>
                    <button type="button" @click="open = false; form.requestSubmit()"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-200 hover:shadow-lg active:scale-[0.98] {{ $c['button'] }}">
                        {{ $label }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>
