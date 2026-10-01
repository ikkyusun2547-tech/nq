@extends('layouts.dashboard')

@section('content')
@php
    // Plain array union (+), not Collection::merge() — merge() renumbers
    // integer-keyed items (and PHP silently casts numeric string keys like
    // '-1'/'0' to real ints), so it would've collapsed 'เรื่องทั่วไป' onto
    // the same key as the first topic instead of keeping it at -1.
    $topicOptions = ['-1' => __('เรื่องทั่วไป')] + $topics->mapWithKeys(fn ($topic, $index) => [
        (string) $index => $topic->reason ? "{$topic->title} — {$topic->reason}" : $topic->title,
    ])->all();

    // Deep-linked from a specific flagged/rejected row (dashboard,
    // activity-history) — pre-select the matching topic server-side instead
    // of always defaulting to 'เรื่องทั่วไป'.
    $initialSelectedIndex = '-1';
    if (request('context_type')) {
        $match = $topics->search(fn ($topic) => $topic->type === request('context_type')
            && (string) $topic->activity_id === (string) request('context_id'));
        if ($match !== false) {
            $initialSelectedIndex = (string) $match;
        }
    }
@endphp

<div class="mx-auto max-w-2xl">
    <x-brand-header eyebrow="{{ __('กองพัฒนานักศึกษา') }}" :title="__('เริ่มข้อความใหม่')" />

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST" action="{{ route('contact.store') }}" enctype="multipart/form-data" class="space-y-4 rounded-3xl glass-card p-5"
        x-data="{
            topics: @js($topics),
            subject: @js(old('subject', request('subject', ''))),
            contextType: @js(old('context_type', request('context_type', ''))),
            contextId: @js(old('context_id', request('context_id', ''))),
            selectedIndex: @js((int) $initialSelectedIndex),
            attachmentPreview: null,
            attachmentName: '',
            attachmentIsImage: false,
            select(indexStr) {
                const index = parseInt(indexStr, 10);
                this.selectedIndex = index;
                if (index === -1) {
                    this.contextType = '';
                    this.contextId = '';
                    return;
                }
                const topic = this.topics[index];
                this.contextType = topic.type;
                this.contextId = topic.activity_id;
                if (! this.subject) this.subject = topic.title + (topic.reason ? (' — ' + topic.reason) : '');
            },
            onFileSelected(event) {
                const file = event.target.files[0];
                if (! file) return;
                this.attachmentName = file.name;
                this.attachmentIsImage = file.type.startsWith('image/');
                this.attachmentPreview = this.attachmentIsImage ? URL.createObjectURL(file) : null;
            },
            clearAttachment() {
                this.attachmentPreview = null;
                this.attachmentName = '';
                this.attachmentIsImage = false;
                this.$refs.attachmentInput.value = '';
            },
        }"
    >
        @csrf
        <input type="hidden" name="context_type" :value="contextType">
        <input type="hidden" name="context_id" :value="contextId">

        @if ($topics->isNotEmpty())
            <div @change="select($event.target.value)">
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('เรื่องที่ต้องการติดต่อ') }}</label>
                <x-premium-select name="topic" :options="$topicOptions" :selected="$initialSelectedIndex" :nullable="false" />
            </div>
        @endif

        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('หัวข้อ') }}</label>
            <input type="text" name="subject" x-model="subject" required maxlength="255"
                placeholder="{{ __('เช่น สอบถามเรื่องคำร้องที่ถูกปฏิเสธ') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ข้อความ') }}</label>
            <textarea name="body" rows="6" maxlength="2000"
                placeholder="{{ __('พิมพ์ข้อความที่ต้องการสอบถาม (หรือแนบไฟล์/รูปภาพอย่างเดียวก็ได้)') }}"
                class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('body') }}</textarea>
        </div>

        <div x-show="attachmentName" class="flex items-center gap-2.5 rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
            <template x-if="attachmentIsImage">
                <img :src="attachmentPreview" class="h-11 w-11 shrink-0 rounded-lg object-cover">
            </template>
            <template x-if="! attachmentIsImage">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 18H18a2.25 2.25 0 002.25-2.25V11.25a9 9 0 00-9-9H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z"/></svg>
                </div>
            </template>
            <span class="min-w-0 flex-1 truncate text-xs font-medium text-slate-600 dark:text-slate-300" x-text="attachmentName"></span>
            <button type="button" @click="clearAttachment()" class="shrink-0 rounded-full p-1 text-slate-400 transition-colors hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex items-center gap-2">
            <input type="file" name="attachment" x-ref="attachmentInput" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" @change="onFileSelected($event)">
            <button type="button" @click="$refs.attachmentInput.click()" title="{{ __('แนบไฟล์') }}"
                class="shrink-0 rounded-xl p-2.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-brand-purple-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-brand-purple-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32a1.5 1.5 0 01-2.122-2.121l7.81-7.81"/></svg>
            </button>
            <button type="submit" class="flex-1 rounded-xl bg-brand-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:bg-brand-purple-800">
                {{ __('ส่งข้อความ') }}
            </button>
        </div>
    </form>
</div>
@endsection
