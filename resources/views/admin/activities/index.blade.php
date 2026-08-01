@extends('layouts.dashboard')

@section('content')
@php
    $statusDot = [
        'draft' => 'bg-slate-400',
        'open' => 'bg-brand-green-500',
        'full' => 'bg-amber-500',
        'ongoing' => 'bg-brand-purple-500',
        'closed' => 'bg-slate-400',
        'cancelled' => 'bg-red-500',
    ];
    $statusBadge = [
        'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        'open' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'full' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'ongoing' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400',
        'closed' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
        'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $statusLabel = [
        'draft' => __('ร่าง'), 'open' => __('เปิดรับสมัคร'), 'full' => __('เต็มแล้ว'),
        'ongoing' => __('กำลังดำเนินการ'), 'closed' => __('ปิดกิจกรรม'), 'cancelled' => __('ถูกยกเลิก'),
    ];
    $semesterShort = ['1' => __('เทอม 1'), '2' => __('เทอม 2'), '3' => __('ฤดูร้อน')];

    // Same color families as the status badges above, but as solid chip
    // treatments (icon-bg + border) matching the dashboard's KPI-card
    // language, so the two most-visited admin pages read as one system.
    $statusChipColor = [
        'draft' => ['bg' => 'bg-slate-50 dark:bg-slate-800/60', 'border' => 'border-slate-200 dark:border-slate-700', 'dot' => 'bg-slate-400'],
        'open' => ['bg' => 'bg-brand-green-50 dark:bg-brand-green-500/10', 'border' => 'border-brand-green-100 dark:border-brand-green-500/20', 'dot' => 'bg-brand-green-500'],
        'full' => ['bg' => 'bg-amber-50 dark:bg-amber-500/10', 'border' => 'border-amber-100 dark:border-amber-500/20', 'dot' => 'bg-amber-500'],
        'ongoing' => ['bg' => 'bg-brand-purple-50 dark:bg-brand-purple-500/10', 'border' => 'border-brand-purple-100 dark:border-brand-purple-500/20', 'dot' => 'bg-brand-purple-500'],
        'closed' => ['bg' => 'bg-slate-50 dark:bg-slate-800/60', 'border' => 'border-slate-200 dark:border-slate-700', 'dot' => 'bg-slate-400'],
        'cancelled' => ['bg' => 'bg-red-50 dark:bg-red-500/10', 'border' => 'border-red-100 dark:border-red-500/20', 'dot' => 'bg-red-500'],
    ];
    $totalActivityCount = $statusCounts->sum();
@endphp

<div class="mx-auto max-w-[90rem]">
    <x-brand-header :title="__('รายการกิจกรรมทั้งหมด')" :eyebrow="__('กองพัฒนานักศึกษา')">
        <x-slot:actions>
            <a href="{{ route('admin.activities.create') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-500 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('สร้างกิจกรรม') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    <!-- Status chips: at-a-glance counts, doubling as one-click filters -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.activities.index', array_filter(['academic_year' => $academicYear])) }}"
            @class([
                'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-medium shadow-soft transition hover:-translate-y-0.5',
                'border-brand-purple-300 bg-brand-purple-100 text-brand-purple-800 dark:border-brand-purple-500/40 dark:bg-brand-purple-500/20 dark:text-brand-purple-300' => ! request('status'),
                'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => request('status'),
            ])>
            {{ __('ทั้งหมด') }} <span class="tabular-nums font-semibold">{{ number_format($totalActivityCount) }}</span>
        </a>
        @foreach ($statusLabel as $statusKey => $label)
            @php $count = $statusCounts[$statusKey] ?? 0; @endphp
            <a href="{{ route('admin.activities.index', array_filter(['academic_year' => $academicYear, 'status' => $statusKey])) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-medium shadow-soft transition hover:-translate-y-0.5',
                    $statusChipColor[$statusKey]['bg'], $statusChipColor[$statusKey]['border'],
                    'text-slate-800 dark:text-slate-100 ring-1 ring-inset ring-black/5' => request('status') === $statusKey,
                    'text-slate-500 dark:text-slate-400' => request('status') !== $statusKey,
                ])>
                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $statusChipColor[$statusKey]['dot'] }}"></span>
                {{ $label }} <span class="tabular-nums font-semibold">{{ number_format($count) }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.activities.index') }}" class="mt-4 space-y-3">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อกิจกรรม') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>

            <button type="submit"
                class="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-6 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:from-brand-purple-500 hover:to-brand-purple-400 hover:shadow-lg active:scale-[0.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                {{ __('ค้นหา') }}
            </button>
        </div>

        @php
            $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => $y])->all();
        @endphp

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-premium-select
                name="status" :options="$statusLabel" :selected="request('status')"
                placeholder="{{ __('-- ทุกสถานะ --') }}" autosubmit
            />

            <x-premium-select
                name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
                placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit
            />

            <x-premium-select
                name="semester" :options="$semesterShort" :selected="request('semester')"
                placeholder="{{ __('-- ทุกภาคเรียน --') }}" autosubmit
            />
        </div>
    </form>

    <div
        x-data="{
            selected: [],
            allIds: @js($activities->pluck('id')->all()),
            statusMap: @js($activities->mapWithKeys(fn ($a) => [$a->id => $a->status])),
            get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length },
            get hasCloseable() { return this.selected.some(id => ! ['closed', 'cancelled'].includes(this.statusMap[id])); },
            get hasCancellable() { return this.selected.some(id => this.statusMap[id] !== 'cancelled'); },
            get hasReopenable() { return this.selected.some(id => ['closed', 'cancelled'].includes(this.statusMap[id])); },
            get confirmPalette() {
                const palette = {
                    red: { border: 'to-red-200/40 dark:to-red-500/20', iconBg: 'bg-red-50 ring-red-50/50 dark:bg-red-500/10 dark:ring-red-500/5', iconText: 'text-red-600 dark:text-red-400', button: 'bg-gradient-to-r from-red-600 to-red-500' },
                    green: { border: 'to-brand-green-100/40 dark:to-brand-green-500/20', iconBg: 'bg-brand-green-50 ring-brand-green-50/50 dark:bg-brand-green-500/10 dark:ring-brand-green-500/5', iconText: 'text-brand-green-600 dark:text-brand-green-400', button: 'bg-gradient-to-r from-brand-green-600 to-brand-green-500' },
                    slate: { border: 'to-slate-200/60 dark:to-slate-500/20', iconBg: 'bg-slate-100 ring-slate-100/50 dark:bg-slate-800 dark:ring-slate-800/50', iconText: 'text-slate-500 dark:text-slate-400', button: 'bg-gradient-to-r from-slate-600 to-slate-500' },
                };
                return palette[this.confirmTone] ?? palette.slate;
            },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            confirmOpen: false,
            confirmAction: null,
            confirmMessage: '',
            confirmLabel: '',
            confirmTone: 'slate',
            ask(action, message, label, tone) {
                if (this.selected.length === 0) return;
                this.confirmAction = action;
                this.confirmMessage = `${message} ({{ __(':count กิจกรรม') }})`.replace(':count', this.selected.length);
                this.confirmLabel = label;
                this.confirmTone = tone;
                this.confirmOpen = true;
            },
            proceed() {
                this.$refs.bulkAction.value = this.confirmAction;
                this.confirmOpen = false;
                this.$refs.bulkForm.submit();
            },
        }"
    >
        <div x-show="selected.length > 0" x-cloak x-transition
            class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl bg-gradient-to-r from-slate-100 to-slate-100/60 px-4 py-3 shadow-soft ring-1 ring-slate-200 dark:from-slate-800/60 dark:to-slate-800/30 dark:ring-slate-700">
            <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                <span class="flex h-6 min-w-6 items-center justify-center rounded-full bg-slate-600 px-1.5 text-xs font-bold text-white dark:bg-slate-500" x-text="selected.length"></span>
                {{ __('รายการที่เลือก') }}
            </span>

            <span class="h-6 w-px bg-slate-200 dark:bg-slate-600"></span>

            <div class="ml-auto flex flex-wrap items-center gap-2">
                <button type="button" x-show="hasCloseable" x-cloak
                    @click="ask('close', {{ Js::from(__('ยืนยันปิดกิจกรรมที่เลือก')) }}, {{ Js::from(__('ปิดกิจกรรม')) }}, 'slate')"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-slate-600 to-slate-500 px-3.5 py-2 text-xs font-semibold text-white shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg active:scale-[0.98]">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/></svg>
                    {{ __('ปิดกิจกรรม') }}
                </button>
                <button type="button" x-show="hasCancellable" x-cloak
                    @click="ask('cancel', {{ Js::from(__('ยืนยันยกเลิกกิจกรรมที่เลือก')) }}, {{ Js::from(__('ยกเลิกกิจกรรม')) }}, 'red')"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-2 text-xs font-semibold text-red-600 shadow-soft ring-1 ring-red-200 transition-all duration-200 hover:-translate-y-0.5 hover:bg-red-50 hover:shadow-lg active:scale-[0.98] dark:bg-slate-800 dark:text-red-400 dark:ring-red-500/30">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    {{ __('ยกเลิกกิจกรรม') }}
                </button>
                <button type="button" x-show="hasReopenable" x-cloak
                    @click="ask('reopen', {{ Js::from(__('ยืนยันเปิดกิจกรรมที่เลือกกลับ')) }}, {{ Js::from(__('เปิดกลับ')) }}, 'green')"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-2 text-xs font-semibold text-brand-green-600 shadow-soft ring-1 ring-brand-green-100 transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand-green-50 hover:shadow-lg active:scale-[0.98] dark:bg-slate-800 dark:text-brand-green-400 dark:ring-brand-green-500/30">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0113.5-4.5M19.5 12a7.5 7.5 0 01-13.5 4.5M4.5 4.5v4.5h4.5M19.5 19.5V15h-4.5"/></svg>
                    {{ __('เปิดกลับ') }}
                </button>
            </div>
        </div>

        <div x-show="confirmOpen" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm"
            @keydown.escape.window="confirmOpen = false">
            <div
                @click.outside="confirmOpen = false"
                x-show="confirmOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-sm rounded-[2rem] bg-gradient-to-br from-white/60 via-white/10 p-[1.5px] shadow-soft-lg dark:from-white/10 dark:via-white/5"
                :class="confirmPalette.border"
            >
                <div class="rounded-[calc(2rem-1.5px)] bg-white p-7 text-center dark:bg-slate-900">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full ring-8" :class="confirmPalette.iconBg">
                        <svg class="h-8 w-8 shrink-0" :class="confirmPalette.iconText"
                            fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.362-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('ยืนยันการดำเนินการ') }}</h3>
                    <p class="mx-auto mt-2 max-w-[15rem] text-sm leading-relaxed text-slate-500 dark:text-slate-400" x-text="confirmMessage"></p>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button type="button" @click="confirmOpen = false"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-800">
                            {{ __('ยกเลิก') }}
                        </button>
                        <button type="button" @click="proceed()"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-200 hover:shadow-lg active:scale-[0.98]"
                            :class="confirmPalette.button">
                            <span x-text="confirmLabel"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.activities.bulk-action') }}" x-ref="bulkForm" class="hidden">
            @csrf
            <input type="hidden" name="bulk_action" x-ref="bulkAction">
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="activity_ids[]" :value="id">
            </template>
        </form>

    <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="w-10 whitespace-nowrap px-4 py-3">
                        <input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)"
                            class="h-4 w-4 rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600">
                    </th>
                    <x-sortable-th field="activity_code" :label="__('รหัสกิจกรรม')" />
                    <x-sortable-th field="title" :label="__('ชื่อกิจกรรม')" />
                    <x-sortable-th field="start_at" :label="__('วันที่จัด')" />
                    <x-sortable-th field="academic_year" :label="__('ปีการศึกษา')" />
                    <x-sortable-th field="attendances_count" :label="__('ผู้เช็คชื่อแล้ว')" />
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr @class([
                        'border-b border-slate-100 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:border-slate-800 dark:hover:bg-slate-800/60',
                        'bg-white dark:bg-slate-900' => $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-4 py-3">
                            <input type="checkbox" value="{{ $activity->id }}" x-model="selected"
                                class="h-4 w-4 rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600">
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-brand-purple-600 dark:text-brand-purple-400">{{ $activity->activity_code ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $activity->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $activity->start_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            @if ($activity->academic_year)
                                {{ $activity->academic_year }} · {{ $semesterShort[$activity->semester] ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                            @php
                                $checkinRatio = $activity->eligible_count > 0
                                    ? min(100, round($activity->attendances_count / $activity->eligible_count * 100))
                                    : 0;
                            @endphp
                            <div class="flex min-w-[7rem] items-center gap-2">
                                <div class="min-w-[3.5rem]">
                                    <span class="whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-200">{{ $activity->attendances_count }}</span><span class="text-slate-400 dark:text-slate-500">/{{ $activity->eligible_count }}</span>
                                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                        <div class="h-full rounded-full bg-brand-purple-500" style="width: {{ $checkinRatio }}%"></div>
                                    </div>
                                </div>
                                @if ($activity->flagged_count > 0)
                                    <a href="{{ route('admin.attendance.index', $activity) }}"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 ring-1 ring-red-100 transition-colors hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20 dark:hover:bg-red-500/20"
                                        title="{{ __('มีการเช็คชื่อรอตรวจสอบ') }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                        {{ $activity->flagged_count }} {{ __('รอตรวจสอบ') }}
                                    </a>
                                @endif
                                @if ($activity->pending_late_checkin_count > 0)
                                    <a href="{{ route('admin.late-checkins.index', ['activity_id' => $activity->id]) }}"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600 ring-1 ring-amber-100 transition-colors hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20 dark:hover:bg-amber-500/20"
                                        title="{{ __('มีคำร้องเช็คชื่อย้อนหลังรอตรวจสอบ') }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $activity->pending_late_checkin_count }} {{ __('ย้อนหลัง') }}
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$activity->status] }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$activity->status]])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$activity->status]])></span>
                                </span>
                                {{ $statusLabel[$activity->status] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.attendance.qr-display', $activity) }}" class="font-medium text-brand-green-600 transition-colors hover:text-brand-green-800 dark:text-brand-green-400 dark:hover:text-brand-green-300">{{ __('แสดง QR') }}</a>
                            <a href="{{ route('admin.attendance.index', $activity) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('หน้างาน') }}</a>
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">{{ __('แก้ไข') }}</a>
                            <form method="POST" action="{{ route('admin.activities.duplicate', $activity) }}" class="inline">
                                @csrf
                                <button type="submit" class="font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">{{ __('คัดลอก') }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <x-confirm-submit tone="red" :message="__('ยืนยันลบกิจกรรม \':title\'? การลบไม่สามารถย้อนกลับได้', ['title' => $activity->title])" :label="__('ลบ')"
                                    class="font-medium text-red-500 transition-colors hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">{{ __('ลบ') }}</x-confirm-submit>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีกิจกรรม') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
@endsection
