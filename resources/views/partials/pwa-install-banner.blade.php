<div
    x-data="pwaInstallBanner()"
    x-init="init()"
    x-show="visible"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed inset-x-4 z-50 bottom-24 md:bottom-4 sm:inset-x-auto sm:right-4 sm:w-96"
>
    <div class="flex items-start gap-3 rounded-2xl bg-white p-4 shadow-soft-lg ring-1 ring-black/5 dark:bg-slate-900 dark:ring-white/10">
        <img src="{{ asset('images/icons/icon-192.png') }}" alt="" class="h-11 w-11 shrink-0 rounded-xl shadow-soft">

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ติดตั้งแอป SRRU Check') }}</p>

            <template x-if="platform === 'ios'">
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('แตะปุ่มแชร์ด้านล่างเบราว์เซอร์ แล้วเลือก "เพิ่มไปยังหน้าจอโฮม"') }}</p>
            </template>
            <template x-if="platform !== 'ios' && ! showManualHelp">
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('เข้าใช้งานได้เร็วขึ้นจากหน้าจอหลัก เหมือนแอปทั่วไป') }}</p>
            </template>
            <template x-if="platform !== 'ios' && showManualHelp">
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('เปิดเมนูของเบราว์เซอร์ (⋮ หรือ ≡) แล้วมองหา "ติดตั้งแอป" หรือ "เพิ่มไปยังหน้าจอหลัก"') }}</p>
            </template>

            <div class="mt-2.5 flex items-center gap-3">
                <button
                    type="button" x-show="platform !== 'ios' && ! showManualHelp" @click="install()"
                    class="rounded-lg bg-brand-green-500 px-3.5 py-1.5 text-xs font-semibold text-brand-purple-950 shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand-green-400"
                >
                    {{ __('ติดตั้ง') }}
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
    function pwaInstallBanner() {
        return {
            visible: false,
            platform: null,
            deferredPrompt: null,
            showManualHelp: false,

            init() {
                if (localStorage.getItem('srru_pwa_install_dismissed') === '1') return;

                // Already installed and running as a standalone app — nothing to offer.
                if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                    return;
                }

                // iOS never fires beforeinstallprompt — Apple has no API for it.
                // Safari is the only iOS browser that can install to home screen
                // at all, and only via the manual Share sheet, so this is
                // instructions, not a button.
                const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && ! window.MSStream;
                const isSafari = /^((?!chrome|android|crios|fxios|edgios).)*safari/i.test(navigator.userAgent);
                this.platform = (isIos && isSafari) ? 'ios' : 'chromium';

                // Show the banner (and the button) immediately instead of
                // waiting on an event that may never fire (Firefox never
                // fires it; Chromium only fires it once its own engagement
                // heuristic is satisfied). If the real prompt shows up later,
                // great — the button already on screen just starts working.
                this.visible = true;

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    this.showManualHelp = false;
                });

                window.addEventListener('appinstalled', () => {
                    this.visible = false;
                    localStorage.setItem('srru_pwa_install_dismissed', '1');
                });
            },

            async install() {
                // The button is visible before we necessarily know whether the
                // browser will hand us a real native prompt. If it hasn't
                // (yet), degrade gracefully to manual instructions instead of
                // doing nothing when tapped.
                if (! this.deferredPrompt) {
                    this.showManualHelp = true;
                    return;
                }

                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;
                this.deferredPrompt = null;
                this.visible = false;

                if (outcome === 'accepted') {
                    localStorage.setItem('srru_pwa_install_dismissed', '1');
                }
            },

            dismiss() {
                this.visible = false;
                localStorage.setItem('srru_pwa_install_dismissed', '1');
            },
        };
    }
</script>
