@extends('layouts.dashboard')

@section('content')
@php
    $tabs = [
        'approved' => __('อนุมัติแล้ว'),
        'pending' => __('รออนุมัติ'),
        'rejected' => __('ถูกปฏิเสธ'),
    ];
    $tabIcon = [
        'approved' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'pending' => 'M12 6.75V12l3.75 1.875M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'rejected' => 'M9 9l6 6m0-6l-6 6M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
    $rowTint = match ($status) {
        'approved' => 'bg-brand-green-50/50 dark:bg-brand-green-500/5',
        'pending' => 'bg-amber-50/50 dark:bg-amber-500/5',
        'rejected' => 'bg-red-50/50 dark:bg-red-500/5',
    };
    $typeFilters = [
        'all' => __('ทั้งหมด'),
        'checkin' => __('กิจกรรม'),
        'external' => __('กิจกรรมภายนอก'),
        'credit_transfer' => __('เทียบโอนตำแหน่ง'),
    ];
    $href = fn ($item) => match (true) {
        $item->type === 'external' => route('hour-requests.index', ['tab' => 'external']),
        $item->type === 'credit_transfer' => route('hour-requests.index', ['tab' => 'credit']),
        ($item->checkin_method ?? null) === 'late_request' => route('late-checkin.show', $item->activity_id),
        default => null,
    };
@endphp

<div class="mx-auto max-w-3xl" x-data>
    <x-brand-header :title="__('ประวัติกิจกรรมของฉัน')" />

    <div class="mt-4 flex gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('activity-history.index', array_filter(['status' => $key, 'type' => $type === 'all' ? null : $type])) }}"
                @class([
                    'flex-1 rounded-xl px-3 py-2.5 text-center text-sm font-semibold shadow-soft transition-all duration-200',
                    'bg-brand-purple-700 text-white' => $status === $key,
                    'bg-white text-brand-purple-700 ring-1 ring-brand-purple-100 dark:bg-slate-900 dark:text-brand-purple-400 dark:ring-brand-purple-500/20' => $status !== $key,
                ])>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="mt-3 flex gap-2 overflow-x-auto [scrollbar-width:none]">
        @foreach ($typeFilters as $key => $label)
            <a href="{{ route('activity-history.index', array_filter(['status' => $status, 'type' => $key === 'all' ? null : $key])) }}"
                @class([
                    'shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                    'bg-brand-purple-700 text-white' => $type === $key,
                    'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800' => $type !== $key,
                ])>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="mt-4">
        <x-section-card :icon="$tabIcon[$status]" :title="$tabs[$status]">
            @forelse ($items as $item)
                @php $rowHref = $href($item); @endphp
                <{{ $rowHref ? 'a' : 'div' }} @if($rowHref) href="{{ $rowHref }}" @endif
                    class="block w-full rounded-xl {{ $rowTint }} px-3.5 py-2.5 text-left transition-colors {{ $rowHref ? 'hover:opacity-80' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $item->title }}</p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500">
                                <x-history-type-badge :type="$item->type" />
                                {{ $item->date->translatedFormat('d M Y') }}
                                @if (($item->checkin_method ?? null) === 'late_request') · {{ __('เช็คชื่อย้อนหลัง') }} @endif
                            </p>
                        </div>
                        @if (isset($item->hours) && $item->hours !== null)
                            <span class="shrink-0 text-xs font-medium text-brand-green-700 dark:text-brand-green-400">{{ __(':hours ชม.', ['hours' => $item->hours]) }}</span>
                        @endif
                    </div>

                    @if (! empty($item->flag_reason ?? null))
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('เหตุผลที่ต้องตรวจสอบ:') }} {{ $item->flag_reason }}</p>
                    @endif
                    @if (! empty($item->reject_reason ?? null))
                        <p class="mt-1 text-xs text-red-500 dark:text-red-400">{{ __('เหตุผล:') }} {{ $item->reject_reason }}</p>
                    @endif
                    @if (! empty($item->flag_reason ?? null) || ! empty($item->reject_reason ?? null))
                        <span @click.stop="window.location.href = '{{ route('contact.create', ['subject' => __('สอบถามเรื่อง: :title', ['title' => $item->title]), 'context_type' => $item->type, 'context_id' => $item->activity_id ?? null]) }}'"
                            class="mt-1 inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">
                            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                            {{ __('ติดต่อเจ้าหน้าที่') }}
                        </span>
                    @endif
                </{{ $rowHref ? 'a' : 'div' }}>
            @empty
                <p class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ไม่มีรายการในหมวดนี้') }}</p>
            @endforelse
        </x-section-card>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection
