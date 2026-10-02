@extends('layouts.dashboard')

@section('content')
@php
    $iconMeta = [
        'external' => ['tint' => 'bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400', 'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'check' => ['tint' => 'bg-brand-green-50 text-brand-green-600 dark:bg-brand-green-500/10 dark:text-brand-green-400', 'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'reject' => ['tint' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400', 'path' => 'M6 18L18 6M6 6l12 12'],
        'flag' => ['tint' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        'credit' => ['tint' => 'bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400', 'path' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15M6 12h.008v.008H6V12zm3 0h6'],
        'clock' => ['tint' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'path' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        'chat' => ['tint' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'path' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z'],
    ];
@endphp

<div class="mx-auto max-w-2xl">
    <x-brand-header
        eyebrow="{{ __('ศูนย์การแจ้งเตือน') }}"
        :title="__('การแจ้งเตือน')"
    />

    @if ($notifications->isNotEmpty())
        <div class="mb-4 flex justify-end gap-4">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('อ่านทั้งหมด') }}</button>
            </form>
            <form method="POST" action="{{ route('notifications.destroy-all') }}">
                @csrf
                @method('DELETE')
                <x-confirm-submit tone="red" :message="__('ลบการแจ้งเตือนทั้งหมด? การลบไม่สามารถย้อนกลับได้')" :label="__('ลบทั้งหมด')"
                    class="text-xs font-medium text-red-500 hover:underline dark:text-red-400">{{ __('ลบทั้งหมด') }}</x-confirm-submit>
            </form>
        </div>

        <div class="space-y-2">
            @foreach ($notifications as $notification)
                @php $meta = $iconMeta[$notification->data['icon'] ?? 'check'] ?? $iconMeta['check']; @endphp
                {{-- Swipe left to delete on touch devices ("ทำเหมือนแอพ"): dragging
                     past the threshold submits the existing delete form directly
                     (the same route/CSRF the trash-icon button already uses), so
                     there's no separate deletion code path to keep in sync. --}}
                <div class="relative overflow-hidden rounded-2xl" x-data="{ dragX: 0, dragging: false, startX: 0, startY: 0, horizontal: false }">
                    <div class="absolute inset-0 flex items-center justify-end rounded-2xl bg-red-500 px-6">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    </div>
                    <div
                        class="group relative flex items-start gap-1 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:ring-brand-purple-300 dark:bg-slate-900 dark:ring-slate-700 {{ $notification->read_at ? '' : 'bg-brand-purple-50/40 dark:bg-brand-purple-500/[0.05]' }}"
                        style="touch-action: pan-y;"
                        :style="`transform: translateX(${dragX}px); transition: ${dragging ? 'none' : 'transform 0.2s ease-out'};`"
                        @touchstart="startX = $event.touches[0].clientX; startY = $event.touches[0].clientY; dragging = true; horizontal = false"
                        @touchmove="
                            if (! dragging) return;
                            const dx = $event.touches[0].clientX - startX;
                            const dy = $event.touches[0].clientY - startY;
                            if (! horizontal && Math.abs(dx) > Math.abs(dy) + 4) horizontal = true;
                            if (horizontal) dragX = Math.min(0, dx);
                        "
                        @touchend="
                            dragging = false;
                            if (horizontal && dragX < -80) { dragX = -400; setTimeout(() => $refs.deleteForm.requestSubmit(), 150); }
                            else { dragX = 0; }
                            horizontal = false;
                        "
                    >
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="min-w-0 flex-1">
                            @csrf
                            <button type="submit" class="flex w-full items-start gap-3 p-4 text-left">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $meta['tint'] }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['path'] }}"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-slate-100">{{ __($notification->data['title_key'] ?? '', $notification->data['title_params'] ?? []) }}</span>
                                    <span class="mt-0.5 block text-sm text-gray-500 dark:text-slate-400">{{ __($notification->data['body_key'] ?? '', $notification->data['body_params'] ?? []) }}</span>
                                    <span class="mt-1.5 block text-xs text-gray-400 dark:text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                                </span>
                                @unless ($notification->read_at)
                                    <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-purple-500"></span>
                                @endunless
                            </button>
                        </form>
                        <form x-ref="deleteForm" method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="shrink-0 pr-3 pt-4">
                            @csrf
                            @method('DELETE')
                            <x-confirm-submit tone="red" :message="__('ลบการแจ้งเตือนนี้?')" :label="__('ลบ')"
                                class="rounded-lg p-1.5 text-slate-300 opacity-0 transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-slate-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                aria-label="{{ __('ลบการแจ้งเตือน') }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </x-confirm-submit>
                        </form>
                    </div>
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
