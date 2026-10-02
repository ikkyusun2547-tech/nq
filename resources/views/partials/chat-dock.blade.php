{{-- Minimised conversations docked bottom-right (desktop only) — see resources/js/chat-dock.js. --}}
<div x-data="chatDock(@js([
        'userId' => auth()->id(),
        'labels' => [
            'fileRejected' => __('ไฟล์ต้องเป็นรูป, PDF, Word, Excel หรือข้อความ และไม่เกิน 5 MB'),
            'sendFailed' => __('ส่งไม่สำเร็จ ลองอีกครั้ง'),
        ],
    ]))"
    x-show="windows.length" x-cloak
    class="pointer-events-none fixed bottom-0 right-6 z-[45] hidden items-end gap-3 lg:flex">
    <template x-for="w in windows" :key="w.showUrl">
        <section class="pointer-events-auto flex w-[22rem] flex-col overflow-hidden rounded-t-2xl border border-b-0 border-slate-200 bg-white shadow-soft-lg dark:border-slate-700 dark:bg-slate-900"
            :class="w.collapsed ? 'w-64' : 'h-[30rem]'">
            {{-- Header: click to collapse / expand --}}
            <div class="flex shrink-0 cursor-pointer items-center gap-2.5 bg-brand-purple-700 py-2 pl-3 pr-1.5 text-white" @click="toggle(w)">
                <span class="relative flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/20 text-sm font-semibold">
                    <template x-if="w.avatar"><img :src="w.avatar" alt="" class="h-full w-full object-cover"></template>
                    <template x-if="! w.avatar"><span x-text="(w.name || '?').charAt(0)"></span></template>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold leading-tight" x-text="w.name"></p>
                    <p class="truncate text-[0.7rem] leading-tight text-white/75" x-text="w.title"></p>
                </div>
                <span x-show="w.collapsed && unread(w) > 0" x-text="unread(w)"
                    class="flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1.5 text-[0.7rem] font-bold"></span>
                <a :href="w.showUrl" @click.stop title="{{ __('เปิดเต็มหน้า') }}" aria-label="{{ __('เปิดเต็มหน้า') }}"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white/80 hover:bg-white/15 hover:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                </a>
                <button type="button" @click.stop="toggle(w)" :title="w.collapsed ? '{{ __('ขยาย') }}' : '{{ __('ย่อ') }}'" :aria-label="w.collapsed ? '{{ __('ขยาย') }}' : '{{ __('ย่อ') }}'"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white/80 hover:bg-white/15 hover:text-white">
                    <svg x-show="! w.collapsed" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                    <svg x-show="w.collapsed" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/></svg>
                </button>
                <button type="button" @click.stop="close(w)" title="{{ __('ปิด') }}" aria-label="{{ __('ปิด') }}"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white/80 hover:bg-white/15 hover:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <template x-if="! w.collapsed">
                <div class="flex min-h-0 flex-1 flex-col">
                    <div x-show="w.status === 'closed'" class="shrink-0 bg-slate-50 px-3 py-1.5 text-center text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ __('เรื่องนี้ปิดแล้ว') }}</div>

                    <div :id="'chat-dock-' + w.id" class="min-h-0 flex-1 space-y-1.5 overflow-y-auto bg-slate-50/70 px-3 py-3 dark:bg-slate-950/40">
                        <template x-if="w.messages.length === 0">
                            <p class="py-10 text-center text-xs text-slate-400">{{ __('กำลังโหลด…') }}</p>
                        </template>
                        <template x-for="m in w.messages" :key="m.id">
                            <div class="flex flex-col" :class="m.is_mine ? 'items-end' : 'items-start'">
                                <div class="max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-relaxed"
                                    :class="m.is_mine ? 'rounded-br-md bg-brand-purple-700 text-white' : 'rounded-bl-md bg-white text-slate-800 ring-1 ring-slate-200/70 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700'">
                                    <template x-if="m.attachment_url && m.is_image_attachment">
                                        <a :href="m.attachment_url" target="_blank" rel="noopener" class="mb-1 block"><img :src="m.attachment_url" alt="" class="max-h-40 rounded-xl object-cover"></a>
                                    </template>
                                    <template x-if="m.attachment_url && ! m.is_image_attachment">
                                        <a :href="m.attachment_url" download class="mb-1 block truncate text-xs font-medium underline" x-text="m.attachment_name"></a>
                                    </template>
                                    <p x-show="m.body" class="whitespace-pre-wrap break-words" x-text="m.body"></p>
                                </div>
                                <span class="mt-0.5 px-1 text-[0.65rem] text-slate-400" x-text="timeOf(m)"></span>
                            </div>
                        </template>
                    </div>

                    <form @submit.prevent="send(w)" class="shrink-0 border-t border-slate-100 p-2 dark:border-slate-800">
                        {{-- Picked / pasted file waiting to be sent --}}
                        <div x-show="w.file" class="mb-2 flex items-center gap-2 rounded-xl bg-slate-50 p-1.5 pr-2 dark:bg-slate-800/60">
                            <template x-if="w.fileIsImage"><img :src="w.filePreview" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover"></template>
                            <template x-if="! w.fileIsImage">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 18H18a2.25 2.25 0 002.25-2.25V11.25a9 9 0 00-9-9H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z"/></svg>
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-xs font-medium text-slate-600 dark:text-slate-300" x-text="w.fileName"></span>
                            <button type="button" @click="clearFile(w)" aria-label="{{ __('เอาไฟล์ออก') }}" class="shrink-0 rounded-full p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <p x-show="w.error" x-text="w.error" class="mb-1.5 px-1 text-xs text-red-600 dark:text-red-400"></p>
                        <div class="flex items-end gap-1">
                        <input type="file" class="hidden" :id="'chat-dock-file-' + w.id" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" @change="attach(w, $event.target.files[0])">
                        <button type="button" @click="document.getElementById('chat-dock-file-' + w.id).click()" title="{{ __('แนบรูปหรือไฟล์') }}" aria-label="{{ __('แนบรูปหรือไฟล์') }}"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-500 transition-colors hover:bg-slate-100 hover:text-brand-purple-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-brand-purple-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32a1.5 1.5 0 01-2.122-2.121l7.81-7.81"/></svg>
                        </button>
                        <textarea @paste="onPaste(w, $event)" x-model="w.body" rows="1" maxlength="2000"
                            @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 96) + 'px'"
                            @keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); send(w); $el.style.height = 'auto'; }"
                            placeholder="{{ __('พิมพ์ข้อความ...') }}"
                            class="max-h-24 min-h-9 flex-1 resize-none rounded-2xl border-0 bg-slate-100 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-500/30 dark:bg-slate-800 dark:text-slate-100"></textarea>
                        <button type="submit" :disabled="! canSend(w)" aria-label="{{ __('ส่งข้อความ') }}"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-purple-700 text-white transition-colors hover:bg-brand-purple-800 disabled:bg-slate-300 dark:disabled:bg-slate-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                        </button>
                        </div>
                    </form>
                </div>
            </template>
        </section>
    </template>
</div>
