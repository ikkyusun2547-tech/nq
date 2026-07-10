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
    $hoursPct = min(100, $summary['required_hours'] > 0 ? round($summary['total_hours'] / $summary['required_hours'] * 100) : 0);
    $activitiesPct = min(100, $summary['required_activities'] > 0 ? round($summary['total_activities'] / $summary['required_activities'] * 100) : 0);
@endphp

<div class="mx-auto max-w-4xl" x-data="{ showDetail: false, detail: null }">
    <!-- VIP digital passport card -->
    <div class="relative overflow-hidden rounded-3xl brand-gradient p-6 text-white shadow-soft-lg sm:p-8">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/5 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-brand-green-500/10 blur-2xl"></div>

        <div class="relative flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-violet-200/70">Activity Passport · SRRU</p>
                <h1 class="mt-1.5 text-2xl font-bold tracking-tight sm:text-3xl">{{ auth()->user()->name_thai }}</h1>
                <p class="mt-1.5 text-sm font-light text-violet-100/80">
                    {{ auth()->user()->faculty?->name_th }} · {{ auth()->user()->major?->name_th }}
                </p>
            </div>
            @if ($summary['current_year'])
                <span class="shrink-0 rounded-xl bg-white/10 px-3 py-2 text-center ring-1 ring-white/15 backdrop-blur">
                    <span class="block text-[10px] uppercase tracking-wider text-violet-200/70">{{ __('ชั้นปีที่') }}</span>
                    <span class="block text-lg font-bold leading-none text-brand-green-400">{{ $summary['current_year'] }}</span>
                </span>
            @endif
        </div>

        <div class="relative mt-6 flex items-center justify-between border-t border-white/10 pt-4">
            <span class="font-mono text-xs tracking-widest text-violet-200/60">{{ __('รหัส :id', ['id' => auth()->user()->student_id]) }}</span>
            <span class="text-xs text-violet-200/60">{{ auth()->user()->program_type === 'special' ? __('ภาคพิเศษ (กศ.บป.)') : __('ภาคปกติ') }}</span>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <a href="{{ route('checkin.show') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-brand-green-500 p-4 text-center text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
            {{ __('สแกน QR เช็กชื่อ') }}
        </a>
        <a href="{{ route('external-activities.index') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-white p-4 text-center text-sm font-semibold text-brand-purple-700 shadow-soft ring-1 ring-brand-purple-100 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:text-brand-purple-400 dark:ring-brand-purple-500/20">
            {{ __('ยื่นคำร้องกิจกรรมภายนอก') }}
        </a>
    </div>

    <!-- Clearance status tile: icon + label carries meaning, never color alone -->
    <div class="mt-4 flex items-center gap-3 rounded-2xl p-5 shadow-soft ring-1
        {{ $summary['is_cleared'] ? 'bg-brand-green-50 ring-brand-green-100 dark:bg-brand-green-500/10 dark:ring-brand-green-500/20' : 'bg-amber-50 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/20' }}">
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

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Overall hours / activities meters -->
        <div class="space-y-5 rounded-2xl glass-card p-5 shadow-soft">
            <div>
                <div class="mb-1.5 flex items-baseline justify-between text-sm">
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ __('ชั่วโมงสะสมรวม') }}</span>
                    <span class="text-slate-400 dark:text-slate-400">{{ $summary['total_hours'] }} <span class="text-slate-300 dark:text-slate-500">/ {{ $summary['required_hours'] }} {{ __('ชม.') }}</span></span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-brand-purple-50 dark:bg-brand-purple-500/10">
                    <div class="h-full rounded-full bg-gradient-to-r from-brand-purple-500 to-brand-green-400 glow-emerald" style="width: {{ $hoursPct }}%"></div>
                </div>
            </div>
            <div>
                <div class="mb-1.5 flex items-baseline justify-between text-sm">
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ __('จำนวนกิจกรรมสะสม') }}</span>
                    <span class="text-slate-400 dark:text-slate-400">{{ $summary['total_activities'] }} <span class="text-slate-300 dark:text-slate-500">/ {{ $summary['required_activities'] }} {{ __('งาน') }}</span></span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-brand-purple-50 dark:bg-brand-purple-500/10">
                    <div class="h-full rounded-full bg-gradient-to-r from-brand-purple-500 to-brand-green-400 glow-emerald" style="width: {{ $activitiesPct }}%"></div>
                </div>
            </div>
            @if ($summary['yearly_target_hours'])
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('เป้าหมายชั่วโมงกิจกรรมของชั้นปีที่ :year คือ :hours ชั่วโมง/ปี', [
                    'year' => $summary['current_year'],
                    'hours' => $summary['yearly_target_hours'],
                ]) }}</p>
            @endif
        </div>

        <!-- Category breakdown (5 ด้าน) -->
        <div class="space-y-4 rounded-2xl glass-card p-5 shadow-soft">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ชั่วโมงสะสมแยกตามหมวดหมู่ (5 ด้าน)') }}</h2>
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
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Approved check-ins -->
        <div class="space-y-3 rounded-2xl glass-card p-5 shadow-soft">
            <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <span class="h-2 w-2 rounded-full bg-brand-green-500"></span>
                {{ __('กิจกรรมที่อนุมัติแล้ว') }}
            </h2>
            @forelse ($approvedActivities as $item)
                @php
                    $rowClass = 'flex w-full items-center justify-between gap-3 rounded-xl bg-brand-green-50/50 px-3.5 py-2.5 text-left transition-colors hover:bg-brand-green-100/60 dark:bg-brand-green-500/5 dark:hover:bg-brand-green-500/10';
                @endphp
                @if ($item->type === 'external')
                    <a href="{{ route('external-activities.index') }}" class="{{ $rowClass }}">
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
                            @elseif ($item->checkin_method === 'late_request')
                                · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('เช็กชื่อย้อนหลัง') }}</span>
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 text-xs font-medium text-brand-green-700 dark:text-brand-green-400">{{ __(':hours ชม.', ['hours' => $item->hours]) }}</span>
                @if ($item->type === 'external' || $item->checkin_method === 'late_request')
                    </a>
                @else
                    </button>
                @endif
            @empty
                <p class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีกิจกรรมที่ได้รับการอนุมัติ') }}</p>
            @endforelse
        </div>

        <!-- Pending review -->
        <div class="space-y-3 rounded-2xl glass-card p-5 shadow-soft">
            <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                {{ __('กิจกรรมที่ลงแล้วรออนุมัติ') }}
            </h2>
            @forelse ($pendingActivities as $item)
                <div class="flex items-center justify-between gap-3 rounded-xl bg-amber-50/50 px-3.5 py-2.5 dark:bg-amber-500/5">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $item->title }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">
                            {{ $item->date->translatedFormat('d M Y') }} · {{ __('รอเจ้าหน้าที่ตรวจสอบ') }}
                            @if ($item->type === 'external')
                                · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('กิจกรรมเทียบชั่วโมง') }}</span>
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 text-xs font-medium text-amber-600 dark:text-amber-400">{{ __(':hours ชม.', ['hours' => $item->hours]) }}</span>
                </div>
            @empty
                <p class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('ไม่มีกิจกรรมที่รอตรวจสอบ') }}</p>
            @endforelse
        </div>

        <!-- Rejected -->
        <div class="space-y-3 rounded-2xl glass-card p-5 shadow-soft">
            <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                {{ __('กิจกรรมที่ถูกปฏิเสธ') }}
            </h2>
            @forelse ($rejectedActivities as $item)
                @php
                    $rejectedRowClass = 'block w-full rounded-xl bg-red-50/50 px-3.5 py-2.5 text-left transition-colors hover:bg-red-100/60 dark:bg-red-500/5 dark:hover:bg-red-500/10';
                @endphp
                <a href="{{ $item->type === 'external' ? route('external-activities.index') : route('late-checkin.show', $item->activity_id) }}" class="{{ $rejectedRowClass }}">
                    <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $item->title }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        {{ $item->date->translatedFormat('d M Y') }}
                        @if ($item->type === 'external')
                            · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('กิจกรรมเทียบชั่วโมง') }}</span>
                        @else
                            · <span class="text-brand-purple-500 dark:text-brand-purple-400">{{ __('เช็กชื่อย้อนหลัง') }}</span>
                        @endif
                    </p>
                    @if ($item->reject_reason)
                        <p class="mt-1 truncate text-xs text-red-500 dark:text-red-400">{{ __('เหตุผล:') }} {{ $item->reject_reason }}</p>
                    @endif
                </a>
            @empty
                <p class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('ไม่มีกิจกรรมที่ถูกปฏิเสธ') }}</p>
            @endforelse
        </div>
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
                                <dt class="text-slate-400 dark:text-slate-500">{{ __('เวลาเช็กชื่อ') }}</dt>
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
