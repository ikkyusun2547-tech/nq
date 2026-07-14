@extends('layouts.dashboard')

@section('content')
@php
    $tabs = [
        'approved' => __('อนุมัติแล้ว'),
        'pending' => __('รออนุมัติ'),
        'rejected' => __('ถูกปฏิเสธ'),
    ];
    $rowTint = match ($status) {
        'approved' => 'bg-brand-green-50/50 dark:bg-brand-green-500/5',
        'pending' => 'bg-amber-50/50 dark:bg-amber-500/5',
        'rejected' => 'bg-red-50/50 dark:bg-red-500/5',
    };
    $typeLabel = fn ($item) => match ($item->type) {
        'external' => __('กิจกรรมเทียบชั่วโมง'),
        'credit_transfer' => __('เทียบโอนตำแหน่ง'),
        default => ($item->checkin_method ?? null) === 'late_request' ? __('เช็คชื่อย้อนหลัง') : null,
    };
    $href = fn ($item) => match (true) {
        $item->type === 'external' => route('hour-requests.index', ['tab' => 'external']),
        $item->type === 'credit_transfer' => route('hour-requests.index', ['tab' => 'credit']),
        ($item->checkin_method ?? null) === 'late_request' => route('late-checkin.show', $item->activity_id),
        default => null,
    };
@endphp

<div class="mx-auto max-w-3xl" x-data>
    <x-brand-header :title="__('ประวัติกิจกรรมของฉัน')" :back="route('dashboard')" />

    <div class="mt-4 flex gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('activity-history.index', ['status' => $key]) }}"
                @class([
                    'flex-1 rounded-xl px-3 py-2.5 text-center text-sm font-semibold shadow-soft transition-all duration-200',
                    'bg-brand-purple-700 text-white' => $status === $key,
                    'bg-white text-brand-purple-700 ring-1 ring-brand-purple-100 dark:bg-slate-900 dark:text-brand-purple-400 dark:ring-brand-purple-500/20' => $status !== $key,
                ])>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="mt-4 space-y-2.5">
        @forelse ($items as $item)
            @php $rowHref = $href($item); @endphp
            <{{ $rowHref ? 'a' : 'div' }} @if($rowHref) href="{{ $rowHref }}" @endif
                class="block w-full rounded-2xl {{ $rowTint }} px-4 py-3 shadow-soft transition-colors {{ $rowHref ? 'hover:opacity-80' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $item->title }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">
                            {{ $item->date->translatedFormat('d M Y') }}
                            @if ($typeLabel($item))
                                · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ $typeLabel($item) }}</span>
                            @endif
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
                @if ((! empty($item->flag_reason ?? null) || ! empty($item->reject_reason ?? null)) && config('support.email'))
                    <span @click.stop="window.location.href = 'mailto:{{ config('support.email') }}?subject={{ urlencode(__('สอบถามเรื่อง: :title', ['title' => $item->title])) }}'"
                        class="mt-1 inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">
                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        {{ __('ติดต่อเจ้าหน้าที่') }}
                    </span>
                @endif
            </{{ $rowHref ? 'a' : 'div' }}>
        @empty
            <p class="py-10 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ไม่มีรายการในหมวดนี้') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection
