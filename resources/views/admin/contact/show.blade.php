@extends('layouts.dashboard')

@section('content')
@php
    $statusLabel = ['open' => __('เปิดอยู่'), 'closed' => __('ปิดแล้ว')];
    $studentName = $thread->student->name_thai ?? $thread->student->name;
    $viewerId = auth()->id();
    $initialMessages = $thread->messages->map(fn ($m) => [
        'id' => $m->id,
        'body' => $m->body,
        'sender_name' => $m->sender->name_thai ?? $m->sender->name,
        'sender_avatar' => $m->sender->avatar_url,
        'is_mine' => $m->sender_id === $viewerId,
        'created_at' => $m->created_at->format('d/m/Y H:i'),
        'created_at_ts' => $m->created_at->timestamp,
        'attachment_url' => $m->attachment_path ? asset('storage/'.$m->attachment_path) : null,
        'attachment_name' => $m->attachment_name,
        'is_image_attachment' => $m->isImageAttachment(),
    ]);
@endphp

<div
    class="fixed inset-0 z-30 flex flex-col bg-white dark:bg-slate-950 lg:static lg:mx-auto lg:max-w-xl lg:bg-transparent lg:dark:bg-transparent"
    x-data="{
        status: '{{ $thread->status }}',
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
        pollUrl: '{{ route('admin.contact.poll', $thread) }}',
        replyUrl: '{{ route('admin.contact.reply', $thread) }}',
        // See Student\ContactController's contact/show.blade.php — same
        // measured-not-guessed viewport-fit reasoning, capped at a
        // balanced 34rem instead of stretching the narrow box the full
        // height of the viewport.
        fitToViewport() {
            const setHeight = () => {
                // Below lg (the admin sidebar's own breakpoint), the outer
                // wrapper is `fixed inset-0` (a real full-screen chat page,
                // no dashboard chrome around it) and chatCard is a plain
                // flex-1 child — CSS alone already fills exactly the right
                // space between the header and the composer, so an explicit
                // pixel height here would only fight that. Only measure/cap
                // at lg+, where the chat stays an embedded card inside the
                // normal sidebar layout.
                if (window.innerWidth < 1024) {
                    this.$refs.chatCard.style.height = '';
                    return;
                }
                const top = this.$refs.chatCard.getBoundingClientRect().top;
                const remPx = parseFloat(getComputedStyle(document.documentElement).fontSize);
                // See Student\ContactController's contact/show.blade.php —
                // window.innerHeight (not the CSS 100vh unit) so this stays
                // correct if a horizontal scrollbar is eating into the
                // visible vertical space.
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
                // See Student\ContactController's contact/show.blade.php —
                // same late-loading-image re-snap reasoning.
                box.querySelectorAll('img').forEach((img) => {
                    if (! img.complete) {
                        img.addEventListener('load', () => { box.scrollTop = box.scrollHeight; }, { once: true });
                    }
                });
            });
        },
        // See Student\ContactController's contact/show.blade.php — same
        // 5-minute grouping rule, shown on the last message of each run.
        showMeta(index) {
            const current = this.messages[index];
            const next = this.messages[index + 1];
            if (! next) return true;
            if (next.is_mine !== current.is_mine || next.sender_name !== current.sender_name) return true;
            return (next.created_at_ts - current.created_at_ts) > 300;
        },
        isImageOnly(message) {
            return !! (message.attachment_url && message.is_image_attachment && ! message.body);
        },
        // See Student\ContactController's contact/show.blade.php — same
        // in-page image viewer reasoning (download + close, instead of
        // navigating to a new tab).
        openViewer(message) {
            this.viewerUrl = message.attachment_url;
            this.viewerName = message.attachment_name || 'image';
        },
        closeViewer() {
            this.viewerUrl = null;
            this.viewerName = null;
        },
        // See Student\ContactController's contact/show.blade.php — same
        // escape-then-linkify reasoning.
        linkify(text, isMine) {
            if (! text) return '';
            // See Student\ContactController's contact/show.blade.php —
            // routes untrusted text through a real DOM node's
            // textContent/innerHTML round-trip instead of hand-written
            // entity strings, since literal entity text sitting inside this
            // x-data attribute would get decoded by the browser's own
            // attribute parser before Alpine/JS ever sees it.
            const div = document.createElement('div');
            div.textContent = text;
            const escaped = div.innerHTML;
            const linkClass = isMine
                ? 'underline decoration-white/60 hover:decoration-white'
                : 'underline text-brand-purple-600 dark:text-brand-purple-400';
            return escaped.replace(/(https?:\/\/[^\s]+)/g, (url) => {
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
        {{-- See Student\ContactController's contact/show.blade.php — same
             one-page exception, hand-rolled rather than reintroducing the
             removed back/leadingBack props on <x-brand-header> itself. --}}
        <a href="{{ route('admin.contact.index') }}" aria-label="{{ __('กลับ') }}"
            class="absolute left-2 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        </a>
        <x-brand-header eyebrow="{{ $studentName }} · {{ $thread->student->student_id }}" :title="$thread->subject" class="!mb-0 border-b border-slate-200 bg-white py-3 pl-14 pr-4 dark:border-slate-800 dark:bg-slate-950 lg:!mb-6 lg:border-0 lg:bg-transparent lg:p-0 lg:dark:bg-transparent">
            <x-slot:actions>
                <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300" x-text="statusLabel[status]"></span>
            </x-slot:actions>
        </x-brand-header>
    </div>

    <div class="mt-2 mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2 px-3 lg:-mt-2 lg:px-0">
        <div>
            @if ($thread->assignedAdmin)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-purple-50 px-3 py-1.5 text-xs font-medium text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                    {{ __('กำลังดูแลโดย') }}:
                    {{ $thread->assigned_admin_id === auth()->id() ? __('คุณ') : ($thread->assignedAdmin->name_thai ?? $thread->assignedAdmin->name) }}
                </span>
            @else
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีใครรับเรื่องนี้') }}</span>
            @endif
        </div>

        <div class="flex gap-2">
            @if ($thread->assigned_admin_id === auth()->id())
                <form method="POST" action="{{ route('admin.contact.release', $thread) }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-soft ring-1 ring-slate-200 transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                        {{ __('ปล่อยเรื่อง') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.contact.claim', $thread) }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-soft ring-1 ring-slate-200 transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                        {{ $thread->assignedAdmin ? __('รับช่วงต่อ') : __('รับเรื่องนี้') }}
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.contact.close', $thread) }}" x-show="status === 'open'">
                @csrf
                <button type="submit" class="rounded-xl bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-soft ring-1 ring-slate-200 transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    {{ __('ปิดเรื่อง') }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.contact.reopen', $thread) }}" x-show="status === 'closed'">
                @csrf
                <button type="submit" class="rounded-xl bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-soft ring-1 ring-slate-200 transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    {{ __('เปิดเรื่องกลับ') }}
                </button>
            </form>
        </div>
    </div>

    <div x-ref="chatCard" class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-none glass-card lg:flex-none lg:rounded-2xl lg:shadow-soft-lg">
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
        </form>
    </div>

    {{-- See Student\ContactController's contact/show.blade.php — same
         in-page image viewer (download + close), instead of navigating to
         a new tab. --}}
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
