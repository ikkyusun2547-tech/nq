@extends('layouts.dashboard')

@section('content')
@php
    $categoryMeta = [
        'culture' => ['label' => 'ทำนุบำรุงศิลปวัฒนธรรม', 'dot' => 'bg-sky-400'],
        'academic' => ['label' => 'วิชาการ', 'dot' => 'bg-brand-green-500'],
        'sports' => ['label' => 'กีฬาและส่งเสริมสุขภาพ', 'dot' => 'bg-amber-400'],
        'volunteer' => ['label' => 'จิตอาสา/บำเพ็ญประโยชน์', 'dot' => 'bg-brand-purple-500'],
        'ethics' => ['label' => 'คุณธรรมจริยธรรม', 'dot' => 'bg-fuchsia-400'],
    ];
    $hoursPct = min(100, $summary['required_hours'] > 0 ? round($summary['total_hours'] / $summary['required_hours'] * 100) : 0);
    $activitiesPct = min(100, $summary['required_activities'] > 0 ? round($summary['total_activities'] / $summary['required_activities'] * 100) : 0);
@endphp

<div class="mx-auto max-w-4xl">
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
                    <span class="block text-[10px] uppercase tracking-wider text-violet-200/70">ชั้นปีที่</span>
                    <span class="block text-lg font-bold leading-none text-brand-green-400">{{ $summary['current_year'] }}</span>
                </span>
            @endif
        </div>

        <div class="relative mt-6 flex items-center justify-between border-t border-white/10 pt-4">
            <span class="font-mono text-xs tracking-widest text-violet-200/60">รหัส {{ auth()->user()->student_id }}</span>
            <span class="text-xs text-violet-200/60">{{ auth()->user()->program_type === 'special' ? 'ภาคพิเศษ (กศ.บป.)' : 'ภาคปกติ' }}</span>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <a href="{{ route('checkin.show') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-brand-green-500 p-4 text-center text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
            สแกน QR เช็กชื่อ
        </a>
        <a href="{{ route('external-activities.index') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-white p-4 text-center text-sm font-semibold text-brand-purple-700 shadow-soft ring-1 ring-brand-purple-100 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
            ยื่นคำร้องกิจกรรมภายนอก
        </a>
    </div>

    <!-- Clearance status tile: icon + label carries meaning, never color alone -->
    <div class="mt-4 flex items-center gap-3 rounded-2xl p-5 shadow-soft ring-1
        {{ $summary['is_cleared'] ? 'bg-brand-green-50 ring-brand-green-100' : 'bg-amber-50 ring-amber-200' }}">
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
                <p class="text-sm font-semibold text-brand-green-800">ผ่านเกณฑ์รับใบรับรองกิจกรรมแล้ว</p>
                <p class="text-xs text-brand-green-700">สะสมครบ {{ $summary['total_activities'] }} กิจกรรม / {{ $summary['total_hours'] }} ชั่วโมง</p>
            @else
                <p class="text-sm font-semibold text-amber-800">ยังไม่ผ่านเกณฑ์</p>
                <p class="text-xs text-amber-700">
                    ขาดอีก {{ max(0, $summary['required_activities'] - $summary['total_activities']) }} กิจกรรม
                    และ {{ max(0, $summary['required_hours'] - $summary['total_hours']) }} ชั่วโมง
                </p>
            @endif
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Overall hours / activities meters -->
        <div class="space-y-5 rounded-2xl glass-card p-5 shadow-soft">
            <div>
                <div class="mb-1.5 flex items-baseline justify-between text-sm">
                    <span class="font-medium text-slate-700">ชั่วโมงสะสมรวม</span>
                    <span class="text-slate-400">{{ $summary['total_hours'] }} <span class="text-slate-300">/ {{ $summary['required_hours'] }} ชม.</span></span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-brand-purple-50">
                    <div class="h-full rounded-full bg-gradient-to-r from-brand-purple-500 to-brand-green-400 glow-emerald" style="width: {{ $hoursPct }}%"></div>
                </div>
            </div>
            <div>
                <div class="mb-1.5 flex items-baseline justify-between text-sm">
                    <span class="font-medium text-slate-700">จำนวนกิจกรรมสะสม</span>
                    <span class="text-slate-400">{{ $summary['total_activities'] }} <span class="text-slate-300">/ {{ $summary['required_activities'] }} งาน</span></span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-brand-purple-50">
                    <div class="h-full rounded-full bg-gradient-to-r from-brand-purple-500 to-brand-green-400 glow-emerald" style="width: {{ $activitiesPct }}%"></div>
                </div>
            </div>
            @if ($summary['yearly_target_hours'])
                <p class="text-xs text-slate-400">เป้าหมายชั่วโมงกิจกรรมของชั้นปีที่ {{ $summary['current_year'] }} คือ {{ $summary['yearly_target_hours'] }} ชั่วโมง/ปี</p>
            @endif
        </div>

        <!-- Category breakdown (5 ด้าน) -->
        <div class="space-y-4 rounded-2xl glass-card p-5 shadow-soft">
            <h2 class="text-sm font-semibold text-slate-900">ชั่วโมงสะสมแยกตามหมวดหมู่ (5 ด้าน)</h2>
            @foreach ($categoryMeta as $key => $meta)
                @php $hours = $summary['category_hours'][$key] ?? 0; @endphp
                <div>
                    <div class="mb-1 flex items-baseline justify-between text-xs">
                        <span class="flex items-center gap-1.5 font-medium text-slate-600">
                            <span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>
                            {{ $meta['label'] }}
                        </span>
                        <span class="text-slate-400">{{ $hours }} ชม.</span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-brand-purple-50">
                        @php $pct = min(100, $summary['required_hours'] > 0 ? round($hours / $summary['required_hours'] * 100) : 0); @endphp
                        <div class="h-full rounded-full bg-brand-green-500 glow-emerald" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
