@php
    // 'right' (default) matches the student topnav, where this bell sits
    // near the top-right. The admin sidebar include passes 'left': that
    // bell sits near the bottom-left of the viewport instead (last item in
    // the sidebar's flex-col footer).
    $align = $align ?? 'right';
    $iconMeta = [
        'external' => ['tint' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300', 'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'check' => ['tint' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/15 dark:text-brand-green-300', 'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'reject' => ['tint' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300', 'path' => 'M6 18L18 6M6 6l12 12'],
        'flag' => ['tint' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        'credit' => ['tint' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300', 'path' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15M6 12h.008v.008H6V12zm3 0h6'],
        'chat' => ['tint' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'path' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z'],
    ];
@endphp
<div
    x-data="{
        open: false,
        unread: 0,
        items: [],
        icons: @js($iconMeta),
        panelStyle: {},
        // The bell isn't always at the true edge of the viewport (the
        // student topnav centers its content in a max-w container, so on a
        // wide screen the bell sits well inboard of sm:right-4) — anchoring
        // the panel to the bell's own on-screen position instead of a fixed
        // screen offset keeps it correct at any container width. Below the
        // sm breakpoint the panel goes back to Tailwind's inset-x-4 (a
        // near-full-width sheet reads better on a narrow screen than a
        // pixel-precise anchor), so panelStyle is left empty there.
        position() {
            if (window.innerWidth < 640) {
                this.panelStyle = {};

                return;
            }

            const rect = this.$refs.button.getBoundingClientRect();

            this.panelStyle = @js($align) === 'left'
                ? { left: rect.left + 'px', bottom: (window.innerHeight - rect.top + 8) + 'px' }
                : { top: (rect.bottom + 8) + 'px', right: (window.innerWidth - rect.right) + 'px' };
        },
        async poll() {
            try {
                const res = await fetch('{{ route('notifications.poll') }}', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.unread = data.unread_count;
                this.items = data.notifications;
            } catch (e) {}
        },
        async remove(item) {
            this.items = this.items.filter(i => i.id !== item.id);
            if (! item.read) this.unread = Math.max(0, this.unread - 1);
            try {
                await fetch('/notifications/' + item.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
            } catch (e) {}
        },
        init() {
            this.poll();
            setInterval(() => this.poll(), 20000);

            // Web push arriving while this tab is focused doesn't show an OS
            // toast (push-notifications.js suppresses that on purpose — the
            // student's already looking at the app) and dispatches this
            // instead, so the bell updates immediately rather than waiting
            // up to 20s for the next poll.
            window.addEventListener('push-notification-received', () => this.poll());
        },
    }"
    @click.outside="open = false"
    class="relative"
>
    <button x-ref="button" @click="open = ! open; if (open) { poll(); position(); }" type="button"
        class="relative flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
        :aria-label="unread > 0 ? '{{ __('การแจ้งเตือน') }} (' + unread + ')' : '{{ __('การแจ้งเตือน') }}'"
    >
        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread"
            class="absolute right-0.5 top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[0.6rem] font-bold leading-none text-white"></span>
    </button>

    {{--
        Teleported to <body> rather than left as a normal descendant here.
        The admin sidebar <aside> that align=left renders inside always has
        an active CSS `transform` (Tailwind's translate-x utilities drive
        its open/close slide animation), and ANY element with a transform
        becomes the containing block for its position:fixed descendants too
        — not just absolute ones. So a plain sm:fixed panel nested in there
        still resolved relative to the sidebar, not the viewport, and still
        got clipped by the sidebar's own overflow-hidden + 256px width.
        x-teleport physically moves this DOM node to be a child of <body>
        (no transform there) while keeping it fully wired to the same
        Alpine component above — including @click.outside on the wrapper,
        which Alpine's teleport support accounts for.
    --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak :style="panelStyle" @resize.window="position()"
            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            @class([
                // sm and up: overridden by the :style binding above, computed
                // from the bell's actual position (see position() in x-data).
                // These classes are just the sane pre-JS/mobile fallback.
                'fixed inset-x-4 z-50 w-auto overflow-hidden rounded-2xl bg-white shadow-soft-lg ring-1 ring-black/5 dark:bg-slate-900 dark:ring-white/10 sm:inset-x-auto sm:w-96',
                'top-16 origin-top sm:origin-top-right' => $align === 'right',
                'bottom-20 origin-bottom sm:origin-bottom-left' => $align === 'left',
            ])
        >
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('การแจ้งเตือน') }}</p>
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('notifications.read-all') }}" x-show="unread > 0">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('อ่านทั้งหมด') }}</button>
                    </form>
                    <form method="POST" action="{{ route('notifications.destroy-all') }}" x-show="items.length > 0">
                        @csrf
                        @method('DELETE')
                        <x-confirm-submit tone="red" :message="__('ลบการแจ้งเตือนทั้งหมด? การลบไม่สามารถย้อนกลับได้')" :label="__('ลบทั้งหมด')"
                            class="text-xs font-medium text-red-500 hover:underline dark:text-red-400">{{ __('ลบทั้งหมด') }}</x-confirm-submit>
                    </form>
                    <button type="button" @click="open = false"
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                        aria-label="{{ __('ปิด') }}"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <template x-if="items.length === 0">
                    <p class="px-4 py-8 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('ไม่มีการแจ้งเตือน') }}</p>
                </template>
                <template x-for="item in items" :key="item.id">
                    {{-- Swipe-to-delete on touch devices ("ทำเหมือนแอพ") —
                         nested x-data for per-row drag state (dragX/dragging)
                         while still reading/calling the parent scope's
                         `items`/`remove()` directly, since Alpine's x-data
                         scopes nest lexically. touch-action:pan-y lets the
                         panel's own vertical scroll keep working for a
                         mostly-vertical touch, only horizontal drags are
                         captured here. The existing trash button/tap-to-read
                         row are untouched, so mouse/desktop behavior doesn't
                         change at all. --}}
                    <div class="relative overflow-hidden" x-data="{ dragX: 0, dragging: false, startX: 0, startY: 0, horizontal: false }">
                        <div class="absolute inset-0 flex items-center justify-end bg-red-500 px-5">
                            <svg class="h-4.5 w-4.5 text-white" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </div>
                        <div
                            class="group relative flex items-start bg-white transition-colors hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800/60"
                            :class="! item.read ? 'bg-brand-purple-50/60 dark:bg-brand-purple-500/[0.06]' : ''"
                            style="touch-action: pan-y;"
                            :style="`transform: translateX(${dragX}px); transition: ${dragging ? 'none' : 'transform 0.2s ease-out'};`"
                            @touchstart="startX = $event.touches[0].clientX; startY = $event.touches[0].clientY; dragging = true; horizontal = false"
                            @touchmove="
                                if (! dragging) return;
                                const dx = $event.touches[0].clientX - startX;
                                const dy = $event.touches[0].clientY - startY;
                                if (! horizontal && Math.abs(dx) > Math.abs(dy) + 4) horizontal = true;
                                if (horizontal) dragX = Math.min(0, dx);
                            "
                            @touchend="
                                dragging = false;
                                if (horizontal && dragX < -80) { dragX = -400; setTimeout(() => remove(item), 150); }
                                else { dragX = 0; }
                                horizontal = false;
                            "
                        >
                            <form method="POST" :action="'{{ url('notifications') }}/' + item.id + '/read'" class="min-w-0 flex-1">
                                @csrf
                                <button type="submit" class="flex w-full min-w-0 items-start gap-3 py-3 pl-4 pr-1 text-left">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="(icons[item.icon] || icons.check).tint">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="(icons[item.icon] || icons.check).path"/></svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-slate-800 dark:text-slate-100" x-text="item.title"></span>
                                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" x-text="item.body"></span>
                                        <span class="mt-1 block text-[0.65rem] text-slate-400 dark:text-slate-500" x-text="item.created_at"></span>
                                    </span>
                                    <span x-show="! item.read" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-purple-500"></span>
                                </button>
                            </form>
                            <button type="button" @click="remove(item)"
                                class="mr-2 mt-3 shrink-0 rounded-lg p-1.5 text-slate-300 opacity-0 transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-slate-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                aria-label="{{ __('ลบการแจ้งเตือน') }}"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-xs font-medium text-brand-purple-600 hover:bg-slate-50 dark:border-slate-800 dark:text-brand-purple-400 dark:hover:bg-slate-800/60">
                {{ __('ดูการแจ้งเตือนทั้งหมด') }}
            </a>
        </div>
    </template>
</div>
