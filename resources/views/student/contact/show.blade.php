@extends('layouts.dashboard')

@section('content')
@php
    $statusLabel = ['open' => __('เปิดอยู่'), 'closed' => __('ปิดแล้ว')];
    $initialMessages = $thread->messages->map(fn ($m) => [
        'id' => $m->id,
        'body' => $m->body,
        'sender_name' => $m->sender->name_thai ?? $m->sender->name,
        'sender_avatar' => $m->sender->avatar_url,
        'is_mine' => $m->sender_id === auth()->id(),
        'created_at' => $m->created_at->format('d/m/Y H:i'),
        'created_at_ts' => $m->created_at->timestamp,
        'attachment_url' => $m->attachment_path ? asset('storage/'.$m->attachment_path) : null,
        'attachment_name' => $m->attachment_name,
        'is_image_attachment' => $m->isImageAttachment(),
    ]);
@endphp

<div
    class="fixed inset-0 z-30 flex flex-col bg-white dark:bg-slate-950 md:static md:mx-auto md:max-w-xl md:bg-transparent md:dark:bg-transparent"
    x-data="{
        status: '{{ $thread->status }}',
        assignedAdminName: @js($thread->assignedAdmin?->name_thai ?? $thread->assignedAdmin?->name),
        messages: @js($initialMessages),
        body: '',
        sending: false,
        attachmentFile: null,
        attachmentPreview: null,
        attachmentName: '',
        attachmentIsImage: false,
        viewerUrl: null,
        viewerName: null,
        statusLabel: @js($statusLabel),
        pollUrl: '{{ route('contact.poll', $thread) }}',
        replyUrl: '{{ route('contact.reply', $thread) }}',
        // Caps the chat card at a comfortable, proportionate size (34rem —
        // balanced against the max-w-xl width instead of stretching a
        // narrow box the full height of the viewport) while still never
        // exceeding whatever room is actually available below the header,
        // so the page itself never needs to scroll — measured from wherever
        // this element actually sits (varies between the admin sidebar
        // layout and the student top-nav layout) rather than a guessed
        // constant, and re-measured on resize.
        fitToViewport() {
            const setHeight = () => {
                // Below md, the outer wrapper is `fixed inset-0` (a real
                // full-screen chat page, no dashboard chrome around it) and
                // chatCard is a plain flex-1 child — CSS alone already fills
                // exactly the right space between the header and the
                // composer, so an explicit pixel height here would only
                // fight that. Only measure/cap on desktop, where the chat
                // stays an embedded card inside the normal page.
                if (window.innerWidth < 768) {
                    this.$refs.chatCard.style.height = '';
                    return;
                }
                const top = this.$refs.chatCard.getBoundingClientRect().top;
                const remPx = parseFloat(getComputedStyle(document.documentElement).fontSize);
                // window.innerHeight, not the CSS 100vh unit — if a
                // horizontal scrollbar is showing (a pre-existing overflow
                // in the shared top nav at some widths, unrelated to this
                // page), it eats into the visible vertical space and
                // window.innerHeight shrinks to reflect that, but 100vh
                // does not, which was exactly enough of a mismatch to force
                // the whole page to scroll again.
                const available = window.innerHeight - top - (2 * remPx);
                this.$refs.chatCard.style.height = `${Math.min(available, 37 * remPx)}px`;
            };
            setHeight();
            window.addEventListener('resize', setHeight);
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const box = this.$refs.scrollBox;
                if (! box) return;
                box.scrollTop = box.scrollHeight;
                // Attachment images finish loading asynchronously, after
                // this initial scroll — each one grows scrollHeight as it
                // comes in (its box has no fixed height until then), which
                // otherwise leaves a long, image-heavy thread scrolled short
                // of the real bottom. Re-snap once each one is in.
                box.querySelectorAll('img').forEach((img) => {
                    if (! img.complete) {
                        img.addEventListener('load', () => { box.scrollTop = box.scrollHeight; }, { once: true });
                    }
                });
            });
        },
        // Facebook-style grouping: an avatar/timestamp only needs to show
        // once per run of consecutive messages from the same sender sent
        // within 5 minutes of each other — shown on the *last* message of
        // that run (i.e. when the next message belongs to a different
        // sender or is far enough apart in time to count as a new group).
        showMeta(index) {
            const current = this.messages[index];
            const next = this.messages[index + 1];
            if (! next) return true;
            if (next.is_mine !== current.is_mine || next.sender_name !== current.sender_name) return true;
            return (next.created_at_ts - current.created_at_ts) > 300;
        },
        // An image with no caption bleeds edge-to-edge in its bubble (no
        // padding) instead of sitting inside the normal padded text bubble
        // — matches how Facebook renders a bare photo message.
        isImageOnly(message) {
            return !! (message.attachment_url && message.is_image_attachment && ! message.body);
        },
        // Tapping an image attachment opens it enlarged in-page (with a
        // download link and a close button) instead of just navigating away
        // to a new tab — mirrors the app's full-screen image viewer.
        openViewer(message) {
            this.viewerUrl = message.attachment_url;
            this.viewerName = message.attachment_name || 'image';
        },
        closeViewer() {
            this.viewerUrl = null;
            this.viewerName = null;
        },
        // Escapes the body first (it's untrusted user text, and this feeds
        // x-html) then wraps http(s) URLs in a real <a> — x-text alone
        // rendered links as inert text, this is the minimal way to make
        // them clickable without pulling in a linkify library.
        linkify(text, isMine) {
            if (! text) return '';
            // Writing literal HTML-entity text (e.g. an '&amp;' string) directly
            // inside this x-data HTML attribute doesn't work — the browser's
            // attribute-value parser decodes named character references
            // *before* Alpine/JS ever sees them, silently turning a
            // hand-written escaper into a no-op. Routing the untrusted text
            // through a real DOM node's textContent/innerHTML round-trip
            // instead escapes it correctly, since that happens at runtime via
            // DOM APIs rather than as literal attribute text.
            const div = document.createElement('div');
            div.textContent = text;
            const escaped = div.innerHTML;
            const linkClass = isMine
                ? 'underline decoration-white/60 hover:decoration-white'
                : 'underline text-brand-purple-600 dark:text-brand-purple-400';
            return escaped.replace(/(https?:\/\/[^\s]+)/g, (url) => {
                // Trailing punctuation right after a URL (end of sentence,
                // closing paren, ...) usually isn't part of the link.
                const trailingMatch = url.match(/[).,!?;:]+$/);
                const trailing = trailingMatch ? trailingMatch[0] : '';
                const cleanUrl = trailing ? url.slice(0, -trailing.length) : url;
                return `<a href='${cleanUrl}' target='_blank' rel='noopener noreferrer' class='${linkClass} break-all'>${cleanUrl}</a>${trailing}`;
            });
        },
        onFileSelected(event) {
            const file = event.target.files[0];
            if (! file) return;
            this.attachmentFile = file;
            this.attachmentName = file.name;
            this.attachmentIsImage = file.type.startsWith('image/');
            this.attachmentPreview = this.attachmentIsImage ? URL.createObjectURL(file) : null;
        },
        clearAttachment() {
            this.attachmentFile = null;
            this.attachmentPreview = null;
            this.attachmentName = '';
            this.attachmentIsImage = false;
            this.$refs.attachmentInput.value = '';
        },
        async poll() {
            try {
                const res = await fetch(this.pollUrl, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                const grew = data.messages.length > this.messages.length;
                this.status = data.status;
                this.assignedAdminName = data.assigned_admin_name;
                this.messages = data.messages;
                if (grew) this.scrollToBottom();
            } catch (e) {}
        },
        async send() {
            if ((! this.body.trim() && ! this.attachmentFile) || this.sending) return;
            this.sending = true;
            try {
                const formData = new FormData();
                if (this.body.trim()) formData.append('body', this.body);
                if (this.attachmentFile) formData.append('attachment', this.attachmentFile);
                const res = await fetch(this.replyUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: formData,
                });
                if (res.ok) {
                    const data = await res.json();
                    this.messages.push(data.data);
                    this.status = 'open';
                    this.body = '';
                    this.clearAttachment();
                    this.scrollToBottom();
                }
            } catch (e) {}
            this.sending = false;
        },
        init() {
            this.fitToViewport();
            this.scrollToBottom();
            setInterval(() => this.poll(), 5000);
            window.addEventListener('push-notification-received', () => this.poll());
        },
    }"
>
    <div class="relative shrink-0">
        {{-- This is the one page that gets its own back button back — full
             screen with no other nav chrome at all on mobile, so a student
             who installed the PWA (no browser chrome either) would
             otherwise have no way out except the OS-level back gesture.
             Hand-rolled here rather than reintroducing the removed
             back/leadingBack props on <x-brand-header> itself, since every
             other page intentionally has none. !pl-14 on the header
             reserves the room this sits in; md:!pl-6 gives that room back
             once the button hides at md+. --}}
        <a href="{{ route('contact.index') }}" aria-label="{{ __('กลับ') }}"
            class="absolute left-2 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 md:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        </a>
        <x-brand-header eyebrow="{{ __('กองพัฒนานักศึกษา') }}" :title="$thread->subject" class="!mb-0 border-b border-slate-200 bg-white py-3 pl-14 pr-4 dark:border-slate-800 dark:bg-slate-950 md:!mb-6 md:border-0 md:bg-transparent md:p-0 md:dark:bg-transparent">
            <x-slot:actions>
                @php
                    $dockEntry = [
                        'id' => $thread->id,
                        'title' => $thread->subject,
                        'name' => __('กองพัฒนานักศึกษา'),
                        'avatar' => null,
                        'showUrl' => route('contact.show', $thread),
                        'pollUrl' => route('contact.poll', $thread),
                        'replyUrl' => route('contact.reply', $thread),
                        'indexUrl' => route('contact.index'),
                    ];
                @endphp
                <button type="button" title="{{ __('ย่อแชทไว้มุมจอ แล้วใช้หน้าอื่นต่อได้') }}"
                    @click="minimizeChat({{ auth()->id() }}, Object.assign(@js($dockEntry), { lastSeenId: messages.length ? messages[messages.length - 1].id : 0 }))"
                    class="hidden h-8 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition-colors hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:text-brand-purple-300 lg:inline-flex">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/></svg>
                    {{ __('ย่อแชท') }}
                </button>
                <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300" x-text="statusLabel[status]"></span>
            </x-slot:actions>
        </x-brand-header>
    </div>

    <div x-show="assignedAdminName" x-cloak class="mt-2 shrink-0 px-3 md:-mt-2 md:px-0">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-purple-50 px-3 py-1.5 text-xs font-medium text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
            {{ __('เจ้าหน้าที่ผู้ดูแลเรื่องนี้') }}: <span x-text="assignedAdminName"></span>
        </span>
    </div>

    <div x-ref="chatCard" :class="assignedAdminName ? 'mt-2 md:mt-1' : 'mt-0 md:-mt-2'" class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-none glass-card md:flex-none md:rounded-2xl">
        <div x-ref="scrollBox" class="min-h-0 flex-1 overflow-y-auto bg-slate-50/60 p-4 dark:bg-slate-900/40">
            <template x-for="(message, index) in messages" :key="message.id">
                <div :class="[
                    message.is_mine ? 'flex justify-end' : 'flex items-end gap-2 justify-start',
                    showMeta(index) ? 'mb-3' : 'mb-0.5',
                ]">
                    <template x-if="! message.is_mine">
                        <div class="h-7 w-7 shrink-0 overflow-hidden rounded-full ring-1 ring-slate-100 dark:ring-slate-700" :class="showMeta(index) ? '' : 'invisible'">
                            <img x-show="message.sender_avatar" :src="message.sender_avatar" class="h-full w-full object-cover" alt="">
                            <div x-show="! message.sender_avatar" class="flex h-full w-full items-center justify-center bg-brand-purple-700 text-[0.65rem] font-semibold text-white" x-text="message.sender_name.charAt(0)"></div>
                        </div>
                    </template>
                    <div class="flex max-w-[75%] flex-col" :class="message.is_mine ? 'items-end' : 'items-start'">
                        <div :class="[
                            'shadow-soft text-sm',
                            isImageOnly(message) ? 'overflow-hidden rounded-2xl' : 'rounded-2xl px-3.5 py-2',
                            message.is_mine
                                ? 'bg-brand-purple-700 text-white'
                                : 'bg-white text-slate-700 ring-1 ring-slate-100 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
                        ]">
                            <template x-if="message.attachment_url && message.is_image_attachment">
                                <button type="button" @click="openViewer(message)" :class="! isImageOnly(message) && 'mb-2 -mx-1 block'">
                                    <img :src="message.attachment_url" class="max-h-64 w-full rounded-xl object-cover" :class="isImageOnly(message) && '!rounded-2xl'">
                                </button>
                            </template>
                            <template x-if="message.attachment_url && ! message.is_image_attachment">
                                <a :href="message.attachment_url" download
                                    class="mb-2 flex items-center gap-2 rounded-xl px-3 py-2"
                                    :class="message.is_mine ? 'bg-white/15' : 'bg-slate-100 dark:bg-slate-700/60'">
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 18H18a2.25 2.25 0 002.25-2.25V11.25a9 9 0 00-9-9H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z"/></svg>
                                    <span class="truncate text-xs font-medium" x-text="message.attachment_name"></span>
                                </a>
                            </template>
                            <p x-show="message.body" class="whitespace-pre-wrap break-words" x-html="linkify(message.body, message.is_mine)"></p>
                        </div>
                        <p x-show="showMeta(index)" :class="message.is_mine ? 'mt-1 text-[0.68rem] text-slate-400 dark:text-slate-500 mr-1 text-right' : 'mt-1 text-[0.68rem] text-slate-400 dark:text-slate-500 ml-1'" x-text="message.created_at"></p>
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="send()" class="shrink-0 space-y-2 border-t border-slate-100 bg-white p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] dark:border-slate-800 dark:bg-slate-900">
            <div x-show="attachmentFile" class="flex items-center gap-2.5 rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
                <template x-if="attachmentIsImage">
                    <img :src="attachmentPreview" class="h-10 w-10 shrink-0 rounded-lg object-cover">
                </template>
                <template x-if="! attachmentIsImage">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 18H18a2.25 2.25 0 002.25-2.25V11.25a9 9 0 00-9-9H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z"/></svg>
                    </div>
                </template>
                <span class="min-w-0 flex-1 truncate text-xs font-medium text-slate-600 dark:text-slate-300" x-text="attachmentName"></span>
                <button type="button" @click="clearAttachment()" class="shrink-0 rounded-full p-1 text-slate-400 transition-colors hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex items-end gap-1.5">
                <input type="file" x-ref="attachmentInput" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" @change="onFileSelected($event)">
                <button type="button" @click="$refs.attachmentInput.click()" title="{{ __('แนบไฟล์') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-brand-purple-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-brand-purple-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32a1.5 1.5 0 01-2.122-2.121l7.81-7.81"/></svg>
                </button>

                <textarea x-model="body" rows="1" maxlength="2000"
                    @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); send(); }"
                    placeholder="{{ __('พิมพ์ข้อความ...') }}"
                    class="max-h-24 flex-1 resize-none rounded-2xl border border-slate-200 bg-white px-3.5 py-2 text-sm transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"></textarea>

                <button type="submit" :disabled="sending || (! body.trim() && ! attachmentFile)" title="{{ __('ส่งข้อความ') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-purple-700 text-white shadow-soft transition-all duration-300 disabled:pointer-events-none disabled:opacity-40 hover:bg-brand-purple-800">
                    <svg x-show="! sending" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                    <svg x-show="sending" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
            <p x-show="status === 'closed'" class="text-center text-xs text-slate-400 dark:text-slate-500">{{ __('เรื่องนี้ปิดแล้ว การส่งข้อความจะเปิดเรื่องขึ้นมาใหม่อัตโนมัติ') }}</p>
        </form>
    </div>

    {{-- Enlarged image viewer — tapping any image attachment above opens it
         here instead of navigating to a new tab, with its own download and
         close controls, mirroring the app's full-screen image viewer. --}}
    <div x-show="viewerUrl" x-cloak
        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/90 p-4"
        @keydown.escape.window="closeViewer()"
    >
        <div class="absolute inset-0" @click="closeViewer()"></div>
        <div class="absolute right-4 top-4 flex gap-2 sm:right-6 sm:top-6">
            <a :href="viewerUrl" :download="viewerName" @click.stop
                class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/20"
                aria-label="{{ __('ดาวน์โหลด') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            </a>
            <button type="button" @click="closeViewer()"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/20"
                aria-label="{{ __('ปิด') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <img :src="viewerUrl" @click.stop class="relative max-h-[85vh] max-w-full rounded-lg object-contain shadow-2xl">
    </div>
</div>
@endsection
