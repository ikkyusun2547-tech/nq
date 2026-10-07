{{-- "⋯" menu (edit / duplicate / delete), teleported to <body> so card overflow never clips it. Expects $activity. --}}
<div class="relative"
                                x-data="{
                                    open: false,
                                    style: {},
                                    place() {
                                        const r = this.$refs.more.getBoundingClientRect();
                                        const w = 208, m = 12;
                                        const left = Math.max(m, Math.min(r.right - w, window.innerWidth - w - m));
                                        this.style = (window.innerHeight - r.bottom > 230)
                                            ? { left: left + 'px', top: (r.bottom + 6) + 'px' }
                                            : { left: left + 'px', bottom: (window.innerHeight - r.top + 6) + 'px' };
                                    },
                                }"
                                @keydown.escape.window="open = false" @scroll.window="open = false" @resize.window="open = false">
                                <button type="button" x-ref="more" @click="place(); open = ! open" :aria-expanded="open" aria-haspopup="menu" aria-label="{{ __('ตัวเลือกเพิ่มเติม') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 12a1.75 1.75 0 11-3.5 0A1.75 1.75 0 016 12zm7.75 0a1.75 1.75 0 11-3.5 0 1.75 1.75 0 013.5 0zM21.5 12a1.75 1.75 0 11-3.5 0 1.75 1.75 0 013.5 0z"/></svg>
                                </button>
                                <template x-teleport="body">
                                    <div x-show="open" x-cloak role="menu" :style="style"
                                        @click.outside="if (! $refs.more.contains($event.target)) open = false"
                                        x-transition.opacity.duration.100ms
                                        class="fixed z-50 w-52 rounded-2xl border border-slate-200 bg-white p-1.5 text-left text-sm shadow-soft-lg dark:border-slate-800 dark:bg-slate-900">
                                        <a href="{{ route('admin.activities.edit', $activity) }}" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                            {{ __('แก้ไข') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.activities.duplicate', $activity) }}">
                                            @csrf
                                            <button type="submit" role="menuitem" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                                                {{ __('คัดลอก') }}
                                            </button>
                                        </form>
                                        <div class="my-1 h-px bg-slate-100 dark:bg-slate-800"></div>
                                        <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-confirm-submit tone="red" :message="__('ยืนยันลบกิจกรรม \':title\'? การลบไม่สามารถย้อนกลับได้', ['title' => $activity->title])" :label="__('ลบ')"
                                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                {{ __('ลบ') }}
                                            </x-confirm-submit>
                                        </form>
                                    </div>
                                </template>
</div>
