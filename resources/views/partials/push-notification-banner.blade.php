<div
    x-data="pushNotificationBanner()"
    x-init="init()"
    x-show="visible"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    {{-- Offset further up than pwa-install-banner's bottom-24/md:bottom-4 —
         both can be visible at once on a student's first visit (this one
         isn't scoped to students only), and stacking at the same spot would
         overlap them. --}}
    class="fixed inset-x-4 z-50 bottom-56 md:bottom-28 sm:inset-x-auto sm:right-4 sm:w-96"
>
    <div class="flex items-start gap-3 rounded-2xl bg-white p-4 shadow-soft-lg ring-1 ring-black/5 dark:bg-slate-900 dark:ring-white/10">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
            <svg class="h-5.5 w-5.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('เปิดการแจ้งเตือน') }}</p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('รับแจ้งเตือนกิจกรรมและผลการตรวจสอบทันที แม้ไม่ได้เปิดหน้านี้อยู่') }}</p>

            <div class="mt-2.5 flex items-center gap-3">
                <button
                    type="button" @click="enable()" :disabled="loading"
                    class="rounded-lg bg-brand-green-500 px-3.5 py-1.5 text-xs font-semibold text-brand-purple-950 shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand-green-400 disabled:pointer-events-none disabled:opacity-60"
                >
                    <span x-show="! loading">{{ __('เปิดใช้งาน') }}</span>
                    <span x-show="loading">{{ __('กำลังเปิดใช้งาน...') }}</span>
                </button>
                <button type="button" @click="dismiss()" class="text-xs font-medium text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">
                    {{ __('ไม่ใช่ตอนนี้') }}
                </button>
            </div>
        </div>

        <button type="button" @click="dismiss()" aria-label="{{ __('ปิด') }}"
            class="shrink-0 rounded-full p-1 text-slate-300 transition-colors hover:bg-slate-100 hover:text-slate-500 dark:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>

<script>
    function pushNotificationBanner() {
        return {
            visible: false,
            loading: false,

            init() {
                if (localStorage.getItem('srru_push_dismissed') === '1') return;

                // Only offer the banner when there's actually a decision left to
                // make — 'unsupported'/'unconfigured' can't succeed no matter
                // what's clicked, and 'granted'/'denied' means the browser
                // already has an answer (denied can only be undone from the
                // browser's own site-settings UI, not by us re-asking).
                this.visible = typeof webPushStatus === 'function' && webPushStatus() === 'default';
            },

            async enable() {
                this.loading = true;
                const result = await enableWebPush();
                this.loading = false;
                this.visible = false;

                if (result.ok || result.reason === 'denied') {
                    localStorage.setItem('srru_push_dismissed', '1');
                }
            },

            dismiss() {
                this.visible = false;
                localStorage.setItem('srru_push_dismissed', '1');
            },
        };
    }
</script>
