{{-- Click-to-change status chip with a confirm dialog; expects $activity, $statusLabel, $statusBadge, $statusDot. --}}
                            <div
                                x-data="{
                                    pending: '{{ $activity->status }}',
                                    original: '{{ $activity->status }}',
                                    open: false,
                                    confirmOpen: false,
                                    panelStyle: '',
                                    labels: @js($statusLabel),
                                    badgeClass: @js($statusBadge),
                                    dotClass: @js($statusDot),
                                    toggle() {
                                        if (this.open) { this.open = false; return; }
                                        // Open below the chip, or above it when the row is near the bottom of the screen.
                                        const r = this.$refs.trigger.getBoundingClientRect();
                                        const w = 176, h = Math.min(260, window.innerHeight - 24), m = 12;
                                        const left = Math.max(m, Math.min(r.left, window.innerWidth - w - m));
                                        this.panelStyle = (window.innerHeight - r.bottom > h + 16)
                                            ? `top:${r.bottom + 8}px; left:${left}px; max-height:${h}px;`
                                            : `bottom:${window.innerHeight - r.top + 8}px; left:${left}px; max-height:${h}px;`;
                                        this.open = true;
                                    },
                                    pick(value) {
                                        this.open = false;
                                        if (value === this.pending) return;
                                        this.pending = value;
                                        this.confirmOpen = true;
                                    },
                                    cancel() { this.pending = this.original; this.confirmOpen = false; },
                                    proceed() { this.original = this.pending; this.confirmOpen = false; this.$refs.statusForm.submit(); },
                                }"
                                class="relative inline-block"
                            >
                                <form method="POST" action="{{ route('admin.activities.update-status', $activity) }}" x-ref="statusForm" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" :value="pending">
                                </form>

                                <button
                                    type="button" x-ref="trigger" @click="toggle()" aria-haspopup="listbox" :aria-expanded="open"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-black/5 transition-all duration-150 hover:-translate-y-px hover:shadow-md focus:outline-none focus:ring-4 focus:ring-brand-purple-500/20 dark:ring-white/5"
                                    :class="badgeClass[pending]"
                                >
                                    <span class="relative flex h-1.5 w-1.5 shrink-0">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60" :class="dotClass[pending]"></span>
                                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full" :class="dotClass[pending]"></span>
                                    </span>
                                    <span x-text="labels[pending]"></span>
                                    <svg class="h-3 w-3 shrink-0 opacity-60 transition-transform duration-150" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                                </button>

                                <template x-teleport="body">
                                    <div
                                        x-show="open" x-cloak role="listbox" :style="panelStyle"
                                        @click.outside="if (! $refs.trigger.contains($event.target)) open = false" @keydown.escape.window="open = false"
                                        @scroll.window="open = false" @resize.window="open = false"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="fixed z-50 w-44 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-soft-lg dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        @foreach ($statusLabel as $statusKey => $label)
                                            <button
                                                type="button" @click="pick('{{ $statusKey }}')" role="option" :aria-selected="pending === '{{ $statusKey }}'"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-brand-purple-50 dark:hover:bg-slate-700/70"
                                                :class="pending === '{{ $statusKey }}' ? 'font-medium text-brand-purple-700 dark:text-brand-purple-400' : 'text-slate-600 dark:text-slate-300'"
                                            >
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $statusDot[$statusKey] }}"></span>
                                                <span class="flex-1 truncate">{{ $label }}</span>
                                                <svg x-show="pending === '{{ $statusKey }}'" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                </template>

                                <template x-teleport="body">
                                    <div x-show="confirmOpen" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
                                        @keydown.escape.window="cancel()">
                                        <div
                                            @click.outside="cancel()"
                                            x-show="confirmOpen"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="w-full max-w-sm rounded-[2rem] bg-slate-200 p-px shadow-soft-lg dark:bg-slate-800"
                                        >
                                            <div class="rounded-[calc(2rem-1px)] bg-white p-7 text-center dark:bg-slate-900">
                                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-purple-50 ring-8 ring-brand-purple-50/50 dark:bg-brand-purple-500/10 dark:ring-brand-purple-500/5">
                                                    <svg class="h-8 w-8 shrink-0 text-brand-purple-600 dark:text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.362-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                                                    </svg>
                                                </div>
                                                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('ยืนยันการเปลี่ยนสถานะ') }}</h3>
                                                <p class="mt-2 break-words text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                                                    {{ __('ต้องการเปลี่ยนสถานะกิจกรรม') }}
                                                    <span class="font-medium text-slate-700 dark:text-slate-200">"{{ $activity->title }}"</span>
                                                    {{ __('เป็น') }}
                                                    "<span x-text="labels[pending]"></span>" {{ __('ใช่หรือไม่?') }}
                                                </p>
                                                <div class="mt-6 grid grid-cols-2 gap-3">
                                                    <button type="button" @click="cancel()"
                                                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-800">
                                                        {{ __('ยกเลิก') }}
                                                    </button>
                                                    <button type="button" @click="proceed()"
                                                        class="rounded-xl bg-brand-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-200 active:scale-[0.98] hover:bg-brand-purple-800">
                                                        {{ __('ยืนยัน') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
