@extends('layouts.dashboard')

@section('content')
@php
    $statusLabel = ['open' => __('เปิดอยู่'), 'closed' => __('ปิดแล้ว')];
    $student = $thread->student;
    $studentName = $student->name_thai ?? $student->name;
    $isMine = $thread->assigned_admin_id === auth()->id();
    $dockEntry = [
        'id' => $thread->id,
        'title' => $thread->subject,
        'name' => $studentName,
        'avatar' => $student->avatar_url,
        'showUrl' => route('admin.contact.show', $thread),
        'pollUrl' => route('admin.contact.poll', $thread),
        'replyUrl' => route('admin.contact.reply', $thread),
        'indexUrl' => route('admin.contact.index'),
    ];
    $handlerName = $thread->assignedAdmin ? ($isMine ? __('คุณ') : ($thread->assignedAdmin->name_thai ?? $thread->assignedAdmin->name)) : null;
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
    class="fixed inset-0 z-30 flex flex-col bg-white dark:bg-slate-950 lg:static lg:mx-auto lg:grid lg:max-w-6xl lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-start lg:gap-6 lg:bg-transparent lg:dark:bg-transparent"
    x-data="{
        status: '{{ $thread->status }}',
        messages: @js($initialMessages),
        body: '',
        sending: false,
        menuOpen: false,
        dayNames: @js(['today' => __('วันนี้'), 'yesterday' => __('เมื่อวาน')]),
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
                this.$refs.chatCard.style.height = `${Math.max(Math.min(available, 46 * remPx), 26 * remPx)}px`;
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
        // created_at is d/m/Y H:i — a divider whenever the day changes.
        dayOf(message) { return message.created_at.split(' ')[0]; },
        timeOf(message) { return message.created_at.split(' ')[1] || ''; },
        showDay(index) {
            return index === 0 || this.dayOf(this.messages[index]) !== this.dayOf(this.messages[index - 1]);
        },
        dayLabel(message) {
            const fmt = (d) => [d.getDate(), d.getMonth() + 1, d.getFullYear()].map((n, i) => i < 2 ? String(n).padStart(2, '0') : n).join('/');
            const today = new Date();
            const yesterday = new Date(Date.now() - 86400000);
            const day = this.dayOf(message);
            if (day === fmt(today)) return this.dayNames.today;
            if (day === fmt(yesterday)) return this.dayNames.yesterday;
            return day;
        },
        autoGrow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 128) + 'px'; },
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
                    this.$nextTick(() => this.$refs.composer && this.autoGrow(this.$refs.composer));
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
    @php
        $ghostBtn = 'inline-flex h-10 w-full items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800';
        $primaryBtn = 'inline-flex h-10 w-full items-center justify-center gap-2 rounded-full bg-brand-purple-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800';
        $menuItem = 'flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800';
    @endphp

    {{-- Left column: the conversation --}}
    <div class="flex min-h-0 flex-1 flex-col">
        {{-- Phone top bar (the dashboard header is hidden for this page below md) --}}
        <div class="relative flex shrink-0 items-center gap-2 border-b border-slate-200 bg-white px-2 py-2 dark:border-slate-800 dark:bg-slate-950 lg:hidden">
            <a href="{{ route('admin.contact.index') }}" aria-label="{{ __('กลับ') }}"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-purple-700 text-sm font-semibold text-white">
                @if ($student->avatar_url)
                    <img src="{{ $student->avatar_url }}" alt="" class="h-full w-full object-cover">
                @else
                    {{ mb_substr($studentName, 0, 1) }}
                @endif
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $studentName }}</p>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $thread->subject }}</p>
            </div>
            <span class="shrink-0 rounded-full px-2.5 py-1 text-[0.7rem] font-semibold"
                :class="status === 'open' ? 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/15 dark:text-brand-green-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                x-text="statusLabel[status]"></span>
            <button type="button" @click="menuOpen = ! menuOpen" aria-label="{{ __('ตัวเลือก') }}"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
            </button>

            <div x-show="menuOpen" x-cloak @click.outside="menuOpen = false"
                x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute right-2 top-full z-20 mt-1 w-64 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-soft-lg dark:border-slate-800 dark:bg-slate-900">
                <p class="px-3 pb-1.5 pt-2 text-xs text-slate-500 dark:text-slate-400">
                    {{ $handlerName ? __('กำลังดูแลโดย').': '.$handlerName : __('ยังไม่มีใครรับเรื่องนี้') }}
                </p>
                <form method="POST" action="{{ $isMine ? route('admin.contact.release', $thread) : route('admin.contact.claim', $thread) }}">
                    @csrf
                    <button type="submit" class="{{ $menuItem }}">{{ $isMine ? __('ปล่อยเรื่อง') : ($thread->assignedAdmin ? __('รับช่วงต่อ') : __('รับเรื่องนี้')) }}</button>
                </form>
                <form method="POST" action="{{ route('admin.contact.close', $thread) }}" x-show="status === 'open'">
                    @csrf
                    <button type="submit" class="{{ $menuItem }}">{{ __('ปิดเรื่อง') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.contact.reopen', $thread) }}" x-show="status === 'closed'">
                    @csrf
                    <button type="submit" class="{{ $menuItem }}">{{ __('เปิดเรื่องกลับ') }}</button>
                </form>
                <a href="{{ route('admin.students.show', $student) }}" class="{{ $menuItem }}">{{ __('ดูข้อมูลนักศึกษา') }}</a>
            </div>
        </div>

        {{-- Desktop title --}}
        <div class="mb-4 hidden lg:block">
            <a href="{{ route('admin.contact.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors hover:text-brand-purple-700 dark:text-slate-400 dark:hover:text-brand-purple-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                {{ __('กล่องข้อความ') }}
            </a>
            <div class="mt-2 flex items-start justify-between gap-4">
                <h1 class="min-w-0 flex-1 font-display text-3xl leading-tight text-slate-900 dark:text-white">{{ $thread->subject }}</h1>
                <button type="button" title="{{ __('ย่อแชทไว้มุมจอ แล้วใช้หน้าอื่นต่อได้') }}"
                    @click="minimizeChat({{ auth()->id() }}, Object.assign(@js($dockEntry), { lastSeenId: messages.length ? messages[messages.length - 1].id : 0 }))"
                    class="hidden h-8 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition-colors hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:text-brand-purple-300 lg:inline-flex">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/></svg>
                    {{ __('ย่อแชท') }}
                </button>
                <span class="mt-1.5 inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                    :class="status === 'open' ? 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/15 dark:text-brand-green-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'">
                    <span class="h-1.5 w-1.5 rounded-full" :class="status === 'open' ? 'bg-brand-green-500' : 'bg-slate-400'"></span>
                    <span x-text="statusLabel[status]"></span>
                </span>
            </div>
        </div>

        <div x-ref="chatCard" class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white dark:bg-slate-900 lg:flex-none lg:rounded-3xl lg:border lg:border-slate-200 lg:shadow-soft dark:lg:border-slate-800">
            {{-- Closed notice --}}
            <div x-show="status === 'closed'" x-cloak class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2.5 text-sm dark:border-slate-800 dark:bg-slate-800/50">
                <span class="text-slate-600 dark:text-slate-300">{{ __('เรื่องนี้ปิดแล้ว') }}</span>
                <form method="POST" action="{{ route('admin.contact.reopen', $thread) }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('เปิดเรื่องกลับ') }}</button>
                </form>
            </div>

            <div x-ref="scrollBox" class="min-h-0 flex-1 overflow-y-auto bg-slate-50/70 px-4 py-5 dark:bg-slate-950/40 sm:px-6">
                <template x-if="messages.length === 0">
                    <p class="py-16 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีข้อความ') }}</p>
                </template>

                <template x-for="(message, index) in messages" :key="message.id">
                    <div>
                        <template x-if="showDay(index)">
                            <div class="my-4 flex items-center gap-3 first:mt-0">
                                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                                <span class="rounded-full bg-white px-3 py-1 text-[0.7rem] font-medium text-slate-500 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-800" x-text="dayLabel(message)"></span>
                                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                            </div>
                        </template>

                        <div :class="[
                            message.is_mine ? 'flex justify-end' : 'flex items-end gap-2 justify-start',
                            showMeta(index) ? 'mb-4' : 'mb-1',
                        ]">
                            <template x-if="! message.is_mine">
                                <div class="h-8 w-8 shrink-0 overflow-hidden rounded-full" :class="showMeta(index) ? '' : 'invisible'">
                                    <img x-show="message.sender_avatar" :src="message.sender_avatar" class="h-full w-full object-cover" alt="">
                                    <div x-show="! message.sender_avatar" class="flex h-full w-full items-center justify-center bg-brand-purple-700 text-xs font-semibold text-white" x-text="message.sender_name.charAt(0)"></div>
                                </div>
                            </template>
                            <div class="flex max-w-[80%] flex-col sm:max-w-[70%]" :class="message.is_mine ? 'items-end' : 'items-start'">
                                <div :class="[
                                    'text-[0.95rem] leading-relaxed',
                                    isImageOnly(message) ? 'overflow-hidden rounded-2xl' : 'px-4 py-2.5',
                                    ! isImageOnly(message) && (message.is_mine ? 'rounded-2xl rounded-br-md' : 'rounded-2xl rounded-bl-md'),
                                    message.is_mine
                                        ? 'bg-brand-purple-700 text-white'
                                        : 'bg-white text-slate-800 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700',
                                ]">
                                    <template x-if="message.attachment_url && message.is_image_attachment">
                                        <button type="button" @click="openViewer(message)" :class="! isImageOnly(message) && 'mb-2 -mx-1.5 block'">
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
                                <p x-show="showMeta(index)" class="mt-1 px-1 text-[0.7rem] text-slate-400 dark:text-slate-500"
                                    x-text="(message.is_mine ? '' : message.sender_name + ' · ') + timeOf(message)"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Composer --}}
            <form @submit.prevent="send()" class="shrink-0 border-t border-slate-100 bg-white px-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] pt-3 dark:border-slate-800 dark:bg-slate-900 sm:px-4">
                <div x-show="attachmentFile" x-cloak class="mb-2 flex items-center gap-2.5 rounded-2xl bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
                    <template x-if="attachmentIsImage">
                        <img :src="attachmentPreview" class="h-10 w-10 shrink-0 rounded-lg object-cover">
                    </template>
                    <template x-if="! attachmentIsImage">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 18H18a2.25 2.25 0 002.25-2.25V11.25a9 9 0 00-9-9H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z"/></svg>
                        </div>
                    </template>
                    <span class="min-w-0 flex-1 truncate text-xs font-medium text-slate-600 dark:text-slate-300" x-text="attachmentName"></span>
                    <button type="button" @click="clearAttachment()" aria-label="{{ __('เอาไฟล์ออก') }}" class="shrink-0 rounded-full p-1 text-slate-400 transition-colors hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex items-end gap-2 rounded-3xl border border-slate-200 bg-slate-50 p-1.5 transition-colors focus-within:border-brand-purple-400 focus-within:bg-white focus-within:ring-4 focus-within:ring-brand-purple-500/10 dark:border-slate-700 dark:bg-slate-800/60 dark:focus-within:bg-slate-800">
                    <input type="file" x-ref="attachmentInput" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" @change="onFileSelected($event)">
                    <button type="button" @click="$refs.attachmentInput.click()" title="{{ __('แนบไฟล์') }}" aria-label="{{ __('แนบไฟล์') }}"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-500 transition-colors hover:bg-white hover:text-brand-purple-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-brand-purple-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32a1.5 1.5 0 01-2.122-2.121l7.81-7.81"/></svg>
                    </button>

                    <textarea x-ref="composer" x-model="body" rows="1" maxlength="2000"
                        @input="autoGrow($el)"
                        @keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); send(); }"
                        placeholder="{{ __('พิมพ์ข้อความถึง :name...', ['name' => $studentName]) }}"
                        class="max-h-32 min-h-9 flex-1 resize-none border-0 bg-transparent px-1 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"></textarea>

                    <button type="submit" :disabled="sending || (! body.trim() && ! attachmentFile)" title="{{ __('ส่งข้อความ') }}" aria-label="{{ __('ส่งข้อความ') }}"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-purple-700 text-white transition-all hover:bg-brand-purple-800 disabled:pointer-events-none disabled:bg-slate-300 dark:disabled:bg-slate-700">
                        <svg x-show="! sending" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                        <svg x-show="sending" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </button>
                </div>
                <p class="mt-1.5 hidden px-2 text-[0.7rem] text-slate-400 dark:text-slate-500 lg:block">{{ __('Enter เพื่อส่ง · Shift + Enter ขึ้นบรรทัดใหม่') }}</p>
            </form>
        </div>
    </div>

    {{-- Right column (desktop): who this is and what to do with it --}}
    <aside class="hidden space-y-4 lg:sticky lg:top-24 lg:block">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-purple-700 text-lg font-semibold text-white">
                    @if ($student->avatar_url)
                        <img src="{{ $student->avatar_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ mb_substr($studentName, 0, 1) }}
                    @endif
                </span>
                <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $studentName }}</p>
                    <p class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $student->student_id }}</p>
                </div>
            </div>
            <dl class="mt-4 space-y-2.5 text-sm">
                @if ($student->faculty)
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('คณะ') }}</dt><dd class="text-slate-800 dark:text-slate-100">{{ $student->faculty->name_th }}</dd></div>
                @endif
                @if ($student->major)
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('สาขา') }}</dt><dd class="text-slate-800 dark:text-slate-100">{{ $student->major->name_th }}</dd></div>
                @endif
                @if ($student->year_level)
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('ชั้นปี') }}</dt><dd class="text-slate-800 dark:text-slate-100">{{ __('ปี :year', ['year' => $student->year_level]) }}</dd></div>
                @endif
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('อีเมล') }}</dt><dd class="break-all text-slate-800 dark:text-slate-100">{{ $student->email }}</dd></div>
            </dl>
            <a href="{{ route('admin.students.show', $student) }}" class="{{ $ghostBtn }} mt-4">
                {{ __('ดูข้อมูลนักศึกษา') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('การจัดการเรื่อง') }}</p>
            <div class="mt-3 flex items-center gap-2.5 rounded-2xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/60">
                <span @class([
                    'h-2 w-2 shrink-0 rounded-full',
                    'bg-brand-purple-500' => $handlerName,
                    'bg-amber-400' => ! $handlerName,
                ])></span>
                <p class="min-w-0 text-sm text-slate-700 dark:text-slate-200">
                    @if ($handlerName)
                        {{ __('กำลังดูแลโดย') }} <span class="font-semibold">{{ $handlerName }}</span>
                    @else
                        {{ __('ยังไม่มีใครรับเรื่องนี้') }}
                    @endif
                </p>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('เริ่มเมื่อ') }}</dt><dd class="text-slate-800 dark:text-slate-100">{{ $thread->created_at->format('d/m/Y') }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('ข้อความ') }}</dt><dd class="text-slate-800 dark:text-slate-100" x-text="messages.length"></dd></div>
            </dl>
            <div class="mt-4 space-y-2">
                <form method="POST" action="{{ $isMine ? route('admin.contact.release', $thread) : route('admin.contact.claim', $thread) }}">
                    @csrf
                    <button type="submit" class="{{ $isMine ? $ghostBtn : $primaryBtn }}">
                        {{ $isMine ? __('ปล่อยเรื่อง') : ($thread->assignedAdmin ? __('รับช่วงต่อ') : __('รับเรื่องนี้')) }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.contact.close', $thread) }}" x-show="status === 'open'">
                    @csrf
                    <button type="submit" class="{{ $ghostBtn }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ __('ปิดเรื่อง') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.contact.reopen', $thread) }}" x-show="status === 'closed'" x-cloak>
                    @csrf
                    <button type="submit" class="{{ $ghostBtn }}">{{ __('เปิดเรื่องกลับ') }}</button>
                </form>
            </div>
        </section>
    </aside>

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
