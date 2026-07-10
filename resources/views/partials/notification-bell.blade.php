@php
    $iconMeta = [
        'external' => ['tint' => 'bg-brand-purple-500/15 text-brand-purple-300', 'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'check' => ['tint' => 'bg-brand-green-500/15 text-brand-green-300', 'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'reject' => ['tint' => 'bg-red-500/15 text-red-300', 'path' => 'M6 18L18 6M6 6l12 12'],
        'flag' => ['tint' => 'bg-amber-500/15 text-amber-300', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        'credit' => ['tint' => 'bg-brand-purple-500/15 text-brand-purple-300', 'path' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15M6 12h.008v.008H6V12zm3 0h6'],
    ];
@endphp
<div
    x-data="{
        open: false,
        unread: 0,
        items: [],
        icons: @js($iconMeta),
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
        },
    }"
    @click.outside="open = false"
    class="relative"
>
    <button @click="open = ! open; if (open) poll();" type="button"
        class="relative flex h-8 w-8 items-center justify-center rounded-lg text-violet-200/70 transition-colors hover:bg-white/5 hover:text-white"
        :aria-label="unread > 0 ? '{{ __('การแจ้งเตือน') }} (' + unread + ')' : '{{ __('การแจ้งเตือน') }}'"
    >
        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread"
            class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[0.6rem] font-bold leading-none text-white ring-2 ring-brand-purple-950"></span>
    </button>

    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="fixed inset-x-4 top-16 z-50 origin-top overflow-hidden rounded-2xl bg-white shadow-soft-lg ring-1 ring-black/5 dark:bg-slate-900 dark:ring-white/10 sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96 sm:origin-top-right"
    >
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('การแจ้งเตือน') }}</p>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('notifications.read-all') }}" x-show="unread > 0">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('อ่านทั้งหมด') }}</button>
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
                <div class="group flex items-start transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60" :class="! item.read ? 'bg-brand-purple-50/60 dark:bg-brand-purple-500/[0.06]' : ''">
                    <div class="flex min-w-0 flex-1 items-start gap-3 py-3 pl-4 pr-1">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="(icons[item.icon] || icons.check).tint">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="(icons[item.icon] || icons.check).path"/></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-800 dark:text-slate-100" x-text="item.title"></span>
                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" x-text="item.body"></span>
                            <span class="mt-1 block text-[0.65rem] text-slate-400 dark:text-slate-500" x-text="item.created_at"></span>
                        </span>
                        <span x-show="! item.read" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-purple-500"></span>
                    </div>
                    <button type="button" @click="remove(item)"
                        class="mr-2 mt-3 shrink-0 rounded-lg p-1.5 text-slate-300 opacity-0 transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-slate-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                        aria-label="{{ __('ลบการแจ้งเตือน') }}"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-xs font-medium text-brand-purple-600 hover:bg-slate-50 dark:border-slate-800 dark:text-brand-purple-400 dark:hover:bg-slate-800/60">
            {{ __('ดูการแจ้งเตือนทั้งหมด') }}
        </a>
    </div>
</div>
