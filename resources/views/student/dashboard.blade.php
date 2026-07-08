@extends('layouts.dashboard')

@section('content')
@php
    $categoryMeta = [
        'culture' => ['label' => 'ทำนุบำรุงศิลปวัฒนธรรม', 'bar' => 'bg-sky-500'],
        'academic' => ['label' => 'วิชาการ', 'bar' => 'bg-brand-green-600'],
        'sports' => ['label' => 'กีฬาและส่งเสริมสุขภาพ', 'bar' => 'bg-amber-500'],
        'volunteer' => ['label' => 'จิตอาสา/บำเพ็ญประโยชน์', 'bar' => 'bg-brand-purple-600'],
        'ethics' => ['label' => 'คุณธรรมจริยธรรม', 'bar' => 'bg-fuchsia-500'],
    ];
    $hoursPct = min(100, $summary['required_hours'] > 0 ? round($summary['total_hours'] / $summary['required_hours'] * 100) : 0);
    $activitiesPct = min(100, $summary['required_activities'] > 0 ? round($summary['total_activities'] / $summary['required_activities'] * 100) : 0);
@endphp

<div class="mx-auto max-w-4xl">
    <div class="overflow-hidden rounded-2xl brand-gradient p-6 text-white shadow-sm sm:p-8">
        <h1 class="text-xl font-semibold sm:text-2xl">สวัสดี, {{ auth()->user()->name_thai }}</h1>
        <p class="mt-1 text-sm text-white/80">
            {{ auth()->user()->faculty?->name_th }} · {{ auth()->user()->major?->name_th }}
            @if ($summary['current_year']) · ชั้นปีที่ {{ $summary['current_year'] }} @endif
        </p>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <a href="{{ route('checkin.show') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-brand-green-600 p-4 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-brand-green-700">
            สแกน QR เช็กชื่อ
        </a>
        <a href="{{ route('external-activities.index') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-white p-4 text-center text-sm font-semibold text-brand-purple-700 shadow-sm ring-1 ring-gray-200 transition hover:bg-brand-purple-50">
            ยื่นคำร้องกิจกรรมภายนอก
        </a>
    </div>

    <!-- Clearance status tile: icon + label carries meaning, never color alone -->
    <div class="mt-4 flex items-center gap-3 rounded-2xl p-5 shadow-sm ring-1
        {{ $summary['is_cleared'] ? 'bg-brand-green-50 ring-brand-green-100' : 'bg-amber-50 ring-amber-200' }}">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg font-bold
            {{ $summary['is_cleared'] ? 'bg-brand-green-600 text-white' : 'bg-amber-500 text-white' }}">
            {{ $summary['is_cleared'] ? '✓' : '!' }}
        </div>
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
        <div class="space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <div>
                <div class="mb-1 flex items-baseline justify-between text-sm">
                    <span class="font-medium text-gray-700">ชั่วโมงสะสมรวม</span>
                    <span class="text-gray-500">{{ $summary['total_hours'] }} / {{ $summary['required_hours'] }} ชม.</span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-brand-green-600" style="width: {{ $hoursPct }}%"></div>
                </div>
            </div>
            <div>
                <div class="mb-1 flex items-baseline justify-between text-sm">
                    <span class="font-medium text-gray-700">จำนวนกิจกรรมสะสม</span>
                    <span class="text-gray-500">{{ $summary['total_activities'] }} / {{ $summary['required_activities'] }} งาน</span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-brand-purple-600" style="width: {{ $activitiesPct }}%"></div>
                </div>
            </div>
            @if ($summary['yearly_target_hours'])
                <p class="text-xs text-gray-400">เป้าหมายชั่วโมงกิจกรรมของชั้นปีที่ {{ $summary['current_year'] }} คือ {{ $summary['yearly_target_hours'] }} ชั่วโมง/ปี</p>
            @endif
        </div>

        <!-- Category breakdown (5 ด้าน) -->
        <div class="space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">ชั่วโมงสะสมแยกตามหมวดหมู่ (5 ด้าน)</h2>
            @foreach ($categoryMeta as $key => $meta)
                @php $hours = $summary['category_hours'][$key] ?? 0; @endphp
                <div>
                    <div class="mb-1 flex items-baseline justify-between text-xs">
                        <span class="flex items-center gap-1.5 font-medium text-gray-600">
                            <span class="h-2 w-2 rounded-full {{ $meta['bar'] }}"></span>
                            {{ $meta['label'] }}
                        </span>
                        <span class="text-gray-400">{{ $hours }} ชม.</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                        @php $pct = min(100, $summary['required_hours'] > 0 ? round($hours / $summary['required_hours'] * 100) : 0); @endphp
                        <div class="h-full rounded-full {{ $meta['bar'] }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
