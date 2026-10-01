{{--
    Body of the "วิธีติดตั้งแอป" guide page: how to add SRRU Check (a PWA) to the
    home screen on Android, iPhone/iPad and a computer. Picks the tab for the
    device in use, and offers a one-tap install where the browser allows it
    (Chromium's beforeinstallprompt, caught early in partials/pwa-head).
--}}
@php
    $shareIcon = 'M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15m0-3l-3-3m0 0l-3 3m3-3V15';
    $plusIcon = 'M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z';
    $dotsIcon = 'M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z';
    $installIcon = 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3';
    $tapIcon = 'M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59';
    $guides = [
        'android' => [
            'label' => __('Android'),
            'browser' => __('ใช้ Chrome'),
            'steps' => [
                [__('เปิดเว็บด้วย Chrome'), __('เข้า :url', ['url' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'srrucheck.up.railway.app']), $tapIcon],
                [__('แตะปุ่ม ⋮'), __('มุมขวาบนของ Chrome'), $dotsIcon],
                [__('เลือก "ติดตั้งแอป"'), __('บางเครื่องเขียนว่า "เพิ่มลงในหน้าจอหลัก"'), $installIcon],
                [__('แตะ "ติดตั้ง"'), __('ไอคอน SRRU Check จะอยู่บนหน้าจอหลัก'), $plusIcon],
            ],
            'tip' => __('ถ้ามีป้าย "ติดตั้งแอป SRRU Check" เด้งขึ้นมาที่มุมจอ กด "ติดตั้ง" ได้เลย เร็วกว่า'),
        ],
        'ios' => [
            'label' => __('iPhone / iPad'),
            'browser' => __('ใช้ Safari เท่านั้น'),
            'steps' => [
                [__('เปิดเว็บด้วย Safari'), __('ไม่ใช่ Chrome หรือแอปอื่น'), $tapIcon],
                [__('แตะปุ่มแชร์'), __('สี่เหลี่ยมมีลูกศรชี้ขึ้น แถบล่างของจอ (iPad อยู่มุมขวาบน)'), $shareIcon],
                [__('เลือก "เพิ่มไปยังหน้าจอโฮม"'), __('เลื่อนรายการลงมาเล็กน้อยถ้ายังไม่เห็น'), $plusIcon],
                [__('แตะ "เพิ่ม"'), __('มุมขวาบน ไอคอนจะอยู่บนหน้าจอโฮม'), $installIcon],
            ],
            'tip' => __('อยากรับการแจ้งเตือนบน iPhone: ต้องเป็น iOS 16.4 ขึ้นไป และเปิดแอปจากไอคอนบนหน้าจอโฮม แล้วกด "อนุญาต" เมื่อระบบถาม'),
        ],
        'desktop' => [
            'label' => __('คอมพิวเตอร์'),
            'browser' => __('ใช้ Chrome หรือ Edge'),
            'steps' => [
                [__('เปิดเว็บด้วย Chrome หรือ Edge'), __('บน Windows หรือ Mac'), $tapIcon],
                [__('กดไอคอนติดตั้ง'), __('ด้านขวาของช่องที่อยู่เว็บ (รูปจอมีลูกศรลง)'), $installIcon],
                [__('กด "ติดตั้ง"'), __('แอปจะเปิดเป็นหน้าต่างของตัวเอง และมีทางลัดบนเดสก์ท็อป'), $plusIcon],
            ],
            'tip' => __('ถ้าไม่เห็นไอคอน เปิดเมนู ⋮ ของเบราว์เซอร์ แล้วหา "ติดตั้ง SRRU Check"'),
        ],
    ];
@endphp

<section x-data="installGuide()" x-init="init()" class="space-y-4">
    {{-- Intro --}}
    <div class="rounded-3xl bg-brand-purple-700 p-5 text-white dark:bg-brand-purple-600/90 sm:p-7">
        <div class="flex items-start gap-4">
            <img src="{{ asset('images/icons/icon-192.png') }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl bg-white/10">
            <div class="min-w-0">
                <h2 class="font-display text-2xl">{{ __('ติดตั้ง SRRU Check เป็นแอป') }}</h2>
                <p class="mt-1 text-sm text-white/80">{{ __('ไม่ต้องโหลดจาก Play Store หรือ App Store ใช้เวลาไม่ถึง 1 นาที') }}</p>
            </div>
        </div>
        <ul class="mt-5 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
            @foreach ([__('เปิดจากหน้าจอหลักได้ทันที'), __('เต็มจอ ไม่มีแถบเบราว์เซอร์'), __('รับแจ้งเตือนกิจกรรมและผลคำร้อง')] as $benefit)
                <li class="flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    {{ $benefit }}
                </li>
            @endforeach
        </ul>

        {{-- Status / one-tap install --}}
        <div x-show="installed" class="mt-5 flex items-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-brand-green-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ __('คุณกำลังใช้ SRRU Check แบบแอปอยู่แล้ว') }}
        </div>
        <button type="button" x-show="! installed && canPrompt" @click="install()"
            class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-white text-[0.95rem] font-semibold text-brand-purple-800 transition-colors hover:bg-brand-purple-50">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $installIcon }}"/></svg>
            {{ __('ติดตั้งเลย') }}
        </button>
    </div>

    {{-- Device tabs --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-7">
        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('เลือกเครื่องที่ใช้') }}</p>
        <div class="mt-3 grid grid-cols-3 gap-2" role="tablist">
            @foreach ($guides as $key => $g)
                <button type="button" role="tab" @click="device = '{{ $key }}'" :aria-selected="device === '{{ $key }}'"
                    class="flex h-11 items-center justify-center rounded-xl border px-2 text-sm font-semibold transition-colors"
                    :class="device === '{{ $key }}'
                        ? 'border-brand-purple-600 bg-brand-purple-600 text-white'
                        : 'border-slate-200 text-slate-700 hover:border-brand-purple-300 dark:border-slate-700 dark:text-slate-200'">
                    {{ $g['label'] }}
                </button>
            @endforeach
        </div>
        <p x-show="detected" class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('เลือกให้ตามเครื่องที่คุณใช้อยู่แล้ว') }}</p>

        @foreach ($guides as $key => $g)
            <div x-show="device === '{{ $key }}'" @if ($key !== 'android') x-cloak @endif class="mt-6">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    {{ $g['browser'] }}
                </span>

                <ol class="mt-4 space-y-3">
                    @foreach ($g['steps'] as $n => [$stepTitle, $stepText, $stepIcon])
                        <li class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-purple-700 font-display text-lg text-white dark:bg-brand-purple-600">{{ $n + 1 }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-slate-900 dark:text-white">{{ $stepTitle }}</span>
                                <span class="mt-0.5 block text-sm text-slate-600 dark:text-slate-400">{{ $stepText }}</span>
                            </span>
                            <svg class="hidden h-6 w-6 shrink-0 text-brand-purple-600 dark:text-brand-purple-300 sm:block" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stepIcon }}"/></svg>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-4 flex gap-2 rounded-2xl border border-brand-purple-100 bg-brand-purple-50 p-4 text-sm text-brand-purple-900 dark:border-brand-purple-500/20 dark:bg-brand-purple-500/10 dark:text-brand-purple-100">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/></svg>
                    <span>{{ $g['tip'] }}</span>
                </p>
            </div>
        @endforeach
    </div>

    {{-- After installing --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-7">
        <p class="font-display text-lg text-slate-900 dark:text-white">{{ __('ติดตั้งแล้ว ทำอะไรต่อ') }}</p>
        <ol class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            @foreach ([
                __('เปิดแอปจากไอคอน SRRU Check บนหน้าจอหลัก'),
                __('เข้าสู่ระบบด้วยอีเมล @srru.ac.th ครั้งแรกครั้งเดียว'),
                __('กด "อนุญาต" เมื่อระบบขอส่งการแจ้งเตือน จะได้ไม่พลาดกิจกรรม'),
            ] as $n => $step)
                <li class="flex gap-2.5">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[0.7rem] font-semibold text-white dark:bg-white dark:text-slate-900">{{ $n + 1 }}</span>
                    <span>{{ $step }}</span>
                </li>
            @endforeach
        </ol>
    </div>
</section>

<script>
    function installGuide() {
        return {
            device: 'android',
            detected: false,
            installed: false,
            canPrompt: false,
            init() {
                const ua = navigator.userAgent;
                // iPadOS reports itself as a Mac; touch support tells them apart.
                const isIos = /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
                if (isIos) { this.device = 'ios'; this.detected = true; }
                else if (/android/i.test(ua)) { this.device = 'android'; this.detected = true; }
                else { this.device = 'desktop'; this.detected = true; }

                this.installed = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                this.canPrompt = !! window.srruInstallPrompt;
                window.addEventListener('srru-install-available', () => { this.canPrompt = true; });
                window.addEventListener('appinstalled', () => { this.installed = true; this.canPrompt = false; });
            },
            async install() {
                const prompt = window.srruInstallPrompt;
                if (! prompt) return;
                prompt.prompt();
                await prompt.userChoice;
                window.srruInstallPrompt = null;
                this.canPrompt = false;
            },
        };
    }
</script>
