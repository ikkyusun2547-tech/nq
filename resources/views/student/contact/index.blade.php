@extends('layouts.dashboard')

@section('content')
@php
    $statusBadge = [
        'open' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'closed' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    ];
    $statusLabel = ['open' => __('เปิดอยู่'), 'closed' => __('ปิดแล้ว')];
@endphp

<div class="mx-auto max-w-3xl">
    <x-brand-header eyebrow="{{ __('กองพัฒนานักศึกษา') }}" :title="__('ข้อความถึงเจ้าหน้าที่')">
        <x-slot:actions>
            <a href="{{ route('contact.create') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-500 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('ข้อความใหม่') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    <div class="space-y-2.5">
        @forelse ($threads as $thread)
            <a href="{{ route('contact.show', $thread) }}"
                class="flex items-start justify-between gap-3 rounded-2xl glass-card p-4 shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        @if ($thread->student_unread)
                            <span class="h-2 w-2 shrink-0 rounded-full bg-brand-purple-500"></span>
                        @endif
                        <p class="truncate font-medium text-slate-900 dark:text-slate-100">{{ $thread->subject }}</p>
                    </div>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                        {{ $thread->last_message_at?->format('d/m/Y H:i') }}
                    </p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$thread->status] }}">
                    {{ $statusLabel[$thread->status] }}
                </span>
            </a>
        @empty
            <div class="rounded-2xl glass-card p-8 text-center shadow-soft">
                <p class="text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีข้อความถึงเจ้าหน้าที่') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $threads->links() }}</div>

    <div class="mt-8 space-y-4">
        @include('partials.contact-info-panel')
        @include('partials.contact-faq')
    </div>
</div>
@endsection
