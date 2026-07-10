@extends('layouts.dashboard')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $iconMeta = [
        'external' => ['tint' => 'bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400', 'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'check' => ['tint' => 'bg-brand-green-50 text-brand-green-600 dark:bg-brand-green-500/10 dark:text-brand-green-400', 'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'reject' => ['tint' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400', 'path' => 'M6 18L18 6M6 6l12 12'],
        'flag' => ['tint' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        'credit' => ['tint' => 'bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400', 'path' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15M6 12h.008v.008H6V12zm3 0h6'],
    ];
@endphp

<div class="mx-auto max-w-2xl">
    <div class="mb-4 flex items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('ศูนย์การแจ้งเตือน') }}</p>
            <h1 class="mt-1 text-lg font-bold text-white">{{ __('การแจ้งเตือน') }}</h1>
        </div>
        <a href="{{ $isAdmin ? route('admin.dashboard') : route('dashboard') }}"
            class="rounded-xl bg-white/10 px-3.5 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
            &larr; {{ __('กลับ') }}
        </a>
    </div>

    @if ($notifications->isNotEmpty())
        <div class="mb-4 flex justify-end gap-4">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('อ่านทั้งหมด') }}</button>
            </form>
            <form method="POST" action="{{ route('notifications.destroy-all') }}" onsubmit="return confirm('{{ __('ลบการแจ้งเตือนทั้งหมด? การลบไม่สามารถย้อนกลับได้') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-medium text-red-500 hover:underline dark:text-red-400">{{ __('ลบทั้งหมด') }}</button>
            </form>
        </div>

        <div class="space-y-2">
            @foreach ($notifications as $notification)
                @php $meta = $iconMeta[$notification->data['icon'] ?? 'check'] ?? $iconMeta['check']; @endphp
                <div class="group flex items-start gap-1 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:ring-brand-purple-300 dark:bg-slate-900 dark:ring-slate-700 {{ $notification->read_at ? '' : 'bg-brand-purple-50/40 dark:bg-brand-purple-500/[0.05]' }}">
                    @if ($isAdmin)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="min-w-0 flex-1">
                            @csrf
                            <button type="submit" class="flex w-full items-start gap-3 p-4 text-left">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $meta['tint'] }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['path'] }}"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-slate-100">{{ __($notification->data['title_key'] ?? '') }}</span>
                                    <span class="mt-0.5 block text-sm text-gray-500 dark:text-slate-400">{{ __($notification->data['body_key'] ?? '', $notification->data['body_params'] ?? []) }}</span>
                                    <span class="mt-1.5 block text-xs text-gray-400 dark:text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                                </span>
                                @unless ($notification->read_at)
                                    <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-purple-500"></span>
                                @endunless
                            </button>
                        </form>
                        <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="shrink-0 pr-3 pt-4"
                            onsubmit="return confirm('{{ __('ลบการแจ้งเตือนนี้?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="rounded-lg p-1.5 text-slate-300 opacity-0 transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-slate-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                aria-label="{{ __('ลบการแจ้งเตือน') }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    @else
                        <div class="flex w-full items-start gap-3 p-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $meta['tint'] }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['path'] }}"/></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium text-gray-900 dark:text-slate-100">{{ __($notification->data['title_key'] ?? '') }}</span>
                                <span class="mt-0.5 block text-sm text-gray-500 dark:text-slate-400">{{ __($notification->data['body_key'] ?? '', $notification->data['body_params'] ?? []) }}</span>
                                <span class="mt-1.5 block text-xs text-gray-400 dark:text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                            </span>
                            @unless ($notification->read_at)
                                <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-purple-500"></span>
                            @endunless
                        </div>
                        <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="shrink-0 pr-3 pt-4"
                            onsubmit="return confirm('{{ __('ลบการแจ้งเตือนนี้?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="rounded-lg p-1.5 text-slate-300 opacity-0 transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-slate-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                aria-label="{{ __('ลบการแจ้งเตือน') }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-700">
            <p class="text-sm text-gray-400 dark:text-slate-500">{{ __('ไม่มีการแจ้งเตือน') }}</p>
        </div>
    @endif
</div>
@endsection
