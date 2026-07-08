@extends('layouts.app')

@section('content')
@php
    $categoryMeta = [
        'culture' => ['label' => 'ทำนุบำรุงศิลปวัฒนธรรม', 'bar' => 'bg-blue-600'],
        'academic' => ['label' => 'วิชาการ', 'bar' => 'bg-teal-600'],
        'sports' => ['label' => 'กีฬาและส่งเสริมสุขภาพ', 'bar' => 'bg-amber-500'],
        'volunteer' => ['label' => 'จิตอาสา/บำเพ็ญประโยชน์', 'bar' => 'bg-green-600'],
        'ethics' => ['label' => 'คุณธรรมจริยธรรม', 'bar' => 'bg-violet-600'],
    ];
    $hoursPct = min(100, $summary['required_hours'] > 0 ? round($summary['total_hours'] / $summary['required_hours'] * 100) : 0);
    $activitiesPct = min(100, $summary['required_activities'] > 0 ? round($summary['total_activities'] / $summary['required_activities'] * 100) : 0);
@endphp

<div class="mx-auto max-w-md px-4 py-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">สวัสดี, {{ auth()->user()->name_thai }}</h1>
            <p class="text-sm text-gray-500">
                {{ auth()->user()->faculty->name_th }} · {{ auth()->user()->major->name_th }}
                @if ($summary['current_year']) · ชั้นปีที่ {{ $summary['current_year'] }} @endif
            </p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-sm text-gray-400 hover:text-gray-600">ออกจากระบบ</button>
        </form>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-3">
        <a href="{{ route('checkin.show') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-blue-600 p-4 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            สแกน QR เช็กชื่อ
        </a>
        <a href="{{ route('external-activities.index') }}"
            class="flex items-center justify-center gap-2 rounded-2xl bg-white p-4 text-center text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50">
            ยื่นคำร้องกิจกรรมภายนอก
        </a>
    </div>

    <!-- Clearance status tile: icon + label carries meaning, never color alone -->
    <div class="mt-4 flex items-center gap-3 rounded-2xl p-5 shadow-sm ring-1
        {{ $summary['is_cleared'] ? 'bg-green-50 ring-green-200' : 'bg-amber-50 ring-amber-200' }}">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg font-bold
            {{ $summary['is_cleared'] ? 'bg-green-600 text-white' : 'bg-amber-500 text-white' }}">
            {{ $summary['is_cleared'] ? '✓' : '!' }}
        </div>
        <div>
            @if ($summary['is_cleared'])
                <p class="text-sm font-semibold text-green-800">ผ่านเกณฑ์รับใบรับรองกิจกรรมแล้ว</p>
                <p class="text-xs text-green-600">สะสมครบ {{ $summary['total_activities'] }} กิจกรรม / {{ $summary['total_hours'] }} ชั่วโมง</p>
            @else
                <p class="text-sm font-semibold text-amber-800">ยังไม่ผ่านเกณฑ์</p>
                <p class="text-xs text-amber-700">
                    ขาดอีก {{ max(0, $summary['required_activities'] - $summary['total_activities']) }} กิจกรรม
                    และ {{ max(0, $summary['required_hours'] - $summary['total_hours']) }} ชั่วโมง
                </p>
            @endif
        </div>
    </div>

    <!-- Overall hours / activities meters -->
    <div class="mt-4 space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
        <div>
            <div class="mb-1 flex items-baseline justify-between text-sm">
                <span class="font-medium text-gray-700">ชั่วโมงสะสมรวม</span>
                <span class="text-gray-500">{{ $summary['total_hours'] }} / {{ $summary['required_hours'] }} ชม.</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-blue-600" style="width: {{ $hoursPct }}%"></div>
            </div>
        </div>
        <div>
            <div class="mb-1 flex items-baseline justify-between text-sm">
                <span class="font-medium text-gray-700">จำนวนกิจกรรมสะสม</span>
                <span class="text-gray-500">{{ $summary['total_activities'] }} / {{ $summary['required_activities'] }} งาน</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-teal-600" style="width: {{ $activitiesPct }}%"></div>
            </div>
        </div>
        @if ($summary['yearly_target_hours'])
            <p class="text-xs text-gray-400">เป้าหมายชั่วโมงกิจกรรมของชั้นปีที่ {{ $summary['current_year'] }} คือ {{ $summary['yearly_target_hours'] }} ชั่วโมง/ปี</p>
        @endif
    </div>

    <!-- Category breakdown (5 ด้าน) -->
    <div class="mt-4 space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
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
@endsection
