@extends('layouts.dashboard')

@section('content')
@php
    $categoryMeta = [
        'culture' => ['label' => __('ทำนุบำรุงศิลปวัฒนธรรม'), 'dot' => 'bg-sky-400'],
        'academic' => ['label' => __('วิชาการ'), 'dot' => 'bg-brand-green-500'],
        'sports' => ['label' => __('กีฬาและส่งเสริมสุขภาพ'), 'dot' => 'bg-amber-400'],
        'volunteer' => ['label' => __('จิตอาสา/บำเพ็ญประโยชน์'), 'dot' => 'bg-brand-purple-500'],
        'ethics' => ['label' => __('คุณธรรมจริยธรรม'), 'dot' => 'bg-fuchsia-400'],
    ];
@endphp

<div class="mx-auto max-w-4xl" x-data="{ showDetail: false, detail: null }">
    <x-brand-header
        eyebrow="Activity Passport · SRRU"
        :title="auth()->user()->name_thai"
        :subtitle="trim((auth()->user()->faculty?->name_th ?? '').' · '.(auth()->user()->major?->name_th ?? ''), ' ·')"
        :decorated="true"
    >
        @if ($summary['current_year'])
            <x-slot:actions>
                <span class="shrink-0 rounded-xl bg-white/10 px-3 py-2 text-center ring-1 ring-white/15 backdrop-blur">
                    <span class="block text-[10px] uppercase tracking-wider text-violet-200/70">{{ __('ชั้นปีที่') }}</span>
                    <span class="block text-lg font-bold leading-none text-brand-green-400">{{ $summary['current_year'] }}</span>
                </span>
            </x-slot:actions>
        @endif
        <x-slot:footer>
            <div class="flex items-center justify-between">
                <span class="font-mono text-xs tracking-widest text-violet-200/60">{{ __('รหัส :id', ['id' => auth()->user()->student_id]) }}</span>
                <span class="text-xs text-violet-200/60">{{ auth()->user()->program_type === 'special' ? __('ภาคพิเศษ (กศ.บป.)') : __('ภาคปกติ') }}</span>
            </div>
        </x-slot:footer>
    </x-brand-header>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <a href="{{ route('checkin.show') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-brand-green-500 p-4 text-center text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
            {{ __('สแกน QR เช็คชื่อ') }}
        </a>
        <a href="{{ route('hour-requests.index', ['tab' => 'external']) }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-white p-4 text-center text-sm font-semibold text-brand-purple-700 shadow-soft ring-1 ring-brand-purple-100 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:text-brand-purple-400 dark:ring-brand-purple-500/20">
            {{ __('ยื่นคำร้องกิจกรรมภายนอก') }}
        </a>
        <a href="{{ route('hour-requests.index', ['tab' => 'credit']) }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-white p-4 text-center text-sm font-semibold text-brand-purple-700 shadow-soft ring-1 ring-brand-purple-100 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:text-brand-purple-400 dark:ring-brand-purple-500/20">
            {{ __('เทียบโอนชั่วโมงจากตำแหน่ง') }}
        </a>
    </div>

    <!-- Clearance status tile: icon + label carries meaning, never color alone -->
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-5 shadow-soft ring-1
        {{ $summary['is_cleared'] ? 'bg-brand-green-50 ring-brand-green-100 dark:bg-brand-green-500/10 dark:ring-brand-green-500/20' : 'bg-amber-50 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/20' }}">
        <div class="flex items-center gap-3">
            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center">
                <span @class([
                    'absolute inline-flex h-full w-full animate-ping rounded-full opacity-40',
                    'bg-brand-green-400' => $summary['is_cleared'],
                    'bg-amber-400' => ! $summary['is_cleared'],
                ])></span>
                <span @class([
                    'relative flex h-10 w-10 items-center justify-center rounded-full text-lg font-bold text-white',
                    'bg-brand-green-500' => $summary['is_cleared'],
                    'bg-amber-500' => ! $summary['is_cleared'],
                ])>{{ $summary['is_cleared'] ? '✓' : '!' }}</span>
            </span>
            <div>
                @if ($summary['is_cleared'])
                    <p class="text-sm font-semibold text-brand-green-800 dark:text-brand-green-400">{{ __('ผ่านเกณฑ์รับใบรับรองกิจกรรมแล้ว') }}</p>
                    <p class="text-xs text-brand-green-700 dark:text-brand-green-400">{{ __('สะสมครบ :activities กิจกรรม / :hours ชั่วโมง', ['activities' => $summary['total_activities'], 'hours' => $summary['total_hours']]) }}</p>
                @else
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-400">{{ __('ยังไม่ผ่านเกณฑ์') }}</p>
                    <p class="text-xs text-amber-700 dark:text-amber-400">
                        {{ __('ขาดอีก :activities กิจกรรม และ :hours ชั่วโมง', [
                            'activities' => max(0, $summary['required_activities'] - $summary['total_activities']),
                            'hours' => max(0, $summary['required_hours'] - $summary['total_hours']),
                        ]) }}
                    </p>
                @endif
            </div>
        </div>
        <a href="{{ route('transcript.download') }}"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-white/70 px-3 py-2 text-xs font-semibold text-brand-purple-700 shadow-soft ring-1 ring-brand-purple-100 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900/50 dark:text-brand-purple-400 dark:ring-brand-purple-500/20">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            {{ __('ใบสรุปกิจกรรม (PDF)') }}
        </a>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Overall hours / activities meters -->
        <div class="flex flex-col gap-3">
            <x-progress-card
                :activities="$summary['total_activities']"
                :required-activities="$summary['required_activities']"
                :hours="$summary['total_hours']"
                :required-hours="$summary['required_hours']"
            />
            @if ($summary['yearly_target_hours'])
                <p class="px-1 text-xs text-slate-400 dark:text-slate-500">{{ __('เป้าหมายชั่วโมงกิจกรรมของชั้นปีที่ :year คือ :hours ชั่วโมง/ปี', [
                    'year' => $summary['current_year'],
                    'hours' => $summary['yearly_target_hours'],
                ]) }}</p>
            @endif
        </div>

        <!-- Category breakdown (5 ด้าน) -->
        <x-section-card icon="M4 20V10M12 20V4M20 20V14" :title="__('ชั่วโมงสะสมแยกตามหมวดหมู่ (5 ด้าน)')">
            @foreach ($categoryMeta as $key => $meta)
                @php $hours = $summary['category_hours'][$key] ?? 0; @endphp
                <div>
                    <div class="mb-1 flex items-baseline justify-between text-xs">
                        <span class="flex items-center gap-1.5 font-medium text-slate-600 dark:text-slate-400">
                            <span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>
                            {{ $meta['label'] }}
                        </span>
                        <span class="text-slate-400 dark:text-slate-500">{{ $hours }} {{ __('ชม.') }}</span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-brand-purple-50 dark:bg-brand-purple-500/10">
                        @php $pct = min(100, $summary['required_hours'] > 0 ? round($hours / $summary['required_hours'] * 100) : 0); @endphp
                        <div class="h-full rounded-full bg-brand-green-500 glow-emerald" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </x-section-card>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Approved check-ins -->
        <x-section-card icon="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" :title="__('กิจกรรมที่อนุมัติแล้ว')">
            @if ($hasMoreApproved)
                <x-slot:action>
                    <a href="{{ route('activity-history.index', ['status' => 'approved']) }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('ดูทั้งหมด') }} &rarr;</a>
                </x-slot:action>
            @endif
            @forelse ($approvedActivities as $item)
                @php
                    $rowClass = 'flex w-full items-center justify-between gap-3 rounded-xl bg-brand-green-50/50 px-3.5 py-2.5 text-left transition-colors hover:bg-brand-green-100/60 dark:bg-brand-green-500/5 dark:hover:bg-brand-green-500/10';
                @endphp
                @if ($item->type === 'external')
                    <a href="{{ route('hour-requests.index', ['tab' => 'external']) }}" class="{{ $rowClass }}">
                @elseif ($item->type === 'credit_transfer')
                    <a href="{{ route('hour-requests.index', ['tab' => 'credit']) }}" class="{{ $rowClass }}">
                @elseif ($item->checkin_method === 'late_request')
                    <a href="{{ route('late-checkin.show', $item->activity_id) }}" class="{{ $rowClass }}">
                @else
                    <button type="button" @click="detail = {{ Illuminate\Support\Js::from([
                        'title' => $item->title,
                        'date' => $item->date->translatedFormat('d M Y H:i'),
                        'hours' => $item->hours,
                        'location' => $item->location_name,
                        'photo' => $item->photo_url,
                    ]) }}; showDetail = true" class="{{ $rowClass }}">
                @endif
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $item->title }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">
                            {{ $item->date->translatedFormat('d M Y') }}
                            @if ($item->type === 'external')
                                · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('กิจกรรมเทียบชั่วโมง') }}</span>
                            @elseif ($item->type === 'credit_transfer')
                                · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('เทียบโอนตำแหน่ง') }}</span>
                            @elseif ($item->checkin_method === 'late_request')
                                · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('เช็คชื่อย้อนหลัง') }}</span>
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 text-xs font-medium text-brand-green-700 dark:text-brand-green-400">{{ __(':hours ชม.', ['hours' => $item->hours]) }}</span>
                @if ($item->type === 'external' || $item->type === 'credit_transfer' || $item->checkin_method === 'late_request')
                    </a>
                @else
                    </button>
                @endif
            @empty
                <p class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีกิจกรรมที่ได้รับการอนุมัติ') }}</p>
            @endforelse
        </x-section-card>

        <!-- Pending review -->
        <x-section-card icon="M12 6.75V12l3.75 1.875M21 12a9 9 0 11-18 0 9 9 0 0118 0z" :title="__('กิจกรรมที่ลงแล้วรออนุมัติ')">
            @if ($hasMorePending)
                <x-slot:action>
                    <a href="{{ route('activity-history.index', ['status' => 'pending']) }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('ดูทั้งหมด') }} &rarr;</a>
                </x-slot:action>
            @endif
            @forelse ($pendingActivities as $item)
                <div class="flex items-center justify-between gap-3 rounded-xl bg-amber-50/50 px-3.5 py-2.5 dark:bg-amber-500/5">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $item->title }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">
                            {{ $item->date->translatedFormat('d M Y') }} · {{ __('รอเจ้าหน้าที่ตรวจสอบ') }}
                            @if ($item->type === 'external')
                                · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('กิจกรรมเทียบชั่วโมง') }}</span>
                            @elseif ($item->type === 'credit_transfer')
                                · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('เทียบโอนตำแหน่ง') }}</span>
                            @endif
                        </p>
                        @if (! empty($item->flag_reason))
                            <p class="mt-1 truncate text-xs text-amber-600 dark:text-amber-400">{{ __('เหตุผลที่ต้องตรวจสอบ:') }} {{ $item->flag_reason }}</p>
                            @if (config('support.email'))
                                <a href="mailto:{{ config('support.email') }}?subject={{ urlencode(__('สอบถามเรื่องการเช็คชื่อติดธงแดง: :title', ['title' => $item->title])) }}"
                                    class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">
                                    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                    {{ __('ติดต่อเจ้าหน้าที่') }}
                                </a>
                            @endif
                        @endif
                    </div>
                    <span class="shrink-0 text-xs font-medium text-amber-600 dark:text-amber-400">{{ __(':hours ชม.', ['hours' => $item->hours]) }}</span>
                </div>
            @empty
                <p class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('ไม่มีกิจกรรมที่รอตรวจสอบ') }}</p>
            @endforelse
        </x-section-card>

        <!-- Rejected -->
        <x-section-card icon="M9 9l6 6m0-6l-6 6M21 12a9 9 0 11-18 0 9 9 0 0118 0z" :title="__('กิจกรรมที่ถูกปฏิเสธ')">
            @if ($hasMoreRejected)
                <x-slot:action>
                    <a href="{{ route('activity-history.index', ['status' => 'rejected']) }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('ดูทั้งหมด') }} &rarr;</a>
                </x-slot:action>
            @endif
            @forelse ($rejectedActivities as $item)
                @php
                    $rejectedRowClass = 'block w-full rounded-xl bg-red-50/50 px-3.5 py-2.5 text-left transition-colors hover:bg-red-100/60 dark:bg-red-500/5 dark:hover:bg-red-500/10';
                @endphp
                @php
                    // A rejected external/credit request can be resubmitted, and a
                    // rejected late check-in request has its own review page — but
                    // a rejected real-time/self-report check-in (checkin_method
                    // realtime/self_report) has no resubmission path (the event
                    // already happened), so it stays a plain, non-clickable row.
                    $rejectedHref = match (true) {
                        $item->type === 'external' => route('hour-requests.index', ['tab' => 'external']),
                        $item->type === 'credit_transfer' => route('hour-requests.index', ['tab' => 'credit']),
                        ($item->checkin_method ?? null) === 'late_request' => route('late-checkin.show', $item->activity_id),
                        default => null,
                    };
                @endphp
                <{{ $rejectedHref ? 'a' : 'div' }} @if($rejectedHref) href="{{ $rejectedHref }}" @endif class="{{ $rejectedRowClass }}">
                    <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $item->title }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        {{ $item->date->translatedFormat('d M Y') }}
                        @if ($item->type === 'external')
                            · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('กิจกรรมเทียบชั่วโมง') }}</span>
                        @elseif ($item->type === 'credit_transfer')
                            · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('เทียบโอนตำแหน่ง') }}</span>
                        @elseif (($item->checkin_method ?? null) === 'late_request')
                            · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('เช็คชื่อย้อนหลัง') }}</span>
                        @endif
                    </p>
                    @if ($item->reject_reason)
                        <p class="mt-1 truncate text-xs text-red-500 dark:text-red-400">{{ __('เหตุผล:') }} {{ $item->reject_reason }}</p>
                        @if (config('support.email'))
                            {{-- A plain <a> would be an invalid nested anchor when
                                 $rejectedHref wraps this whole row in <a> already —
                                 @click.stop keeps this tap from also triggering the
                                 row's own resubmission-page link underneath it. --}}
                            <span @click.stop="window.location.href = 'mailto:{{ config('support.email') }}?subject={{ urlencode(__('สอบถามเรื่องคำร้องที่ถูกปฏิเสธ: :title', ['title' => $item->title])) }}'"
                                class="mt-1 inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                {{ __('ติดต่อเจ้าหน้าที่') }}
                            </span>
                        @endif
                    @endif
                </{{ $rejectedHref ? 'a' : 'div' }}>
            @empty
                <p class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('ไม่มีกิจกรรมที่ถูกปฏิเสธ') }}</p>
            @endforelse
        </x-section-card>
    </div>

    <!-- Check-in detail popup (realtime/self-report only — external and late-request rows link out instead) -->
    <div
        x-show="showDetail" x-cloak @click.outside="showDetail = false" @keydown.escape.window="showDetail = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm"
    >
        <div
            @click.outside="showDetail = false" x-show="detail"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="w-full max-w-sm rounded-[2rem] bg-gradient-to-br from-white/60 via-white/10 to-brand-green-200/40 p-[1.5px] shadow-soft-lg dark:from-white/10 dark:via-white/5 dark:to-brand-green-500/20"
        >
            <div class="rounded-[calc(2rem-1.5px)] bg-white p-5 dark:bg-slate-900">
                <template x-if="detail">
                    <div>
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <p class="font-semibold leading-snug text-slate-900 dark:text-slate-100" x-text="detail.title"></p>
                            <button @click="showDetail = false" class="shrink-0 rounded-full p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mb-3 overflow-hidden rounded-2xl bg-black/5 shadow-soft dark:bg-black/20">
                            <img :src="detail.photo" class="max-h-72 w-full object-contain">
                        </div>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400 dark:text-slate-500">{{ __('เวลาเช็คชื่อ') }}</dt>
                                <dd class="font-medium text-slate-700 dark:text-slate-200" x-text="detail.date"></dd>
                            </div>
                            <div class="flex justify-between gap-3" x-show="detail.location">
                                <dt class="text-slate-400 dark:text-slate-500">{{ __('สถานที่') }}</dt>
                                <dd class="font-medium text-slate-700 dark:text-slate-200" x-text="detail.location"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400 dark:text-slate-500">{{ __('ชั่วโมงที่ได้รับ') }}</dt>
                                <dd class="font-semibold text-brand-green-700 dark:text-brand-green-400" x-text="detail.hours + ' {{ __('ชม.') }}'"></dd>
                            </div>
                        </dl>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
