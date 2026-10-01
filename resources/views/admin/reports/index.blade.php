@extends('layouts.dashboard')

@section('content')
@php
    $reports = [
        [
            'route' => 'admin.reports.clearance',
            'title' => __('นักศึกษาที่ผ่านเกณฑ์กิจกรรม'),
            'subtitle' => __('รายชื่อปี 4 ที่ครบเกณฑ์ พร้อมยื่นจบ'),
            'color' => 'green',
            'icon' => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347',
        ],
        [
            'route' => 'admin.reports.at-risk',
            'title' => __('นักศึกษาที่ยังไม่ผ่านเกณฑ์'),
            'subtitle' => __('ติดตามก่อนนักศึกษาจบการศึกษา เรียงคนใกล้ผ่านก่อน'),
            'color' => 'amber',
            'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        ],
        [
            'route' => 'admin.reports.faculty-participation',
            'title' => __('สรุปการเข้าร่วมกิจกรรมรายคณะ'),
            'subtitle' => __('จำนวนนักศึกษา, อัตราผ่านเกณฑ์, ชั่วโมง/กิจกรรมเฉลี่ยต่อคณะ'),
            'color' => 'purple',
            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        ],
        [
            'route' => 'admin.reports.activity-participation',
            'title' => __('อัตราเข้าร่วมต่อกิจกรรม'),
            'subtitle' => __('กิจกรรมไหนคนเข้าเยอะ/น้อย เทียบกับผู้มีสิทธิ์'),
            'color' => 'teal',
            'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'route' => 'admin.reports.category',
            'title' => __('ชั่วโมงสะสมแยกตามหมวดหมู่ (5 ด้าน)'),
            'subtitle' => __('ภาพรวมทั้งมหาวิทยาลัย ใช้วางแผนกิจกรรมปีถัดไป'),
            'color' => 'sky',
            'icon' => 'M4 20V10M12 20V4M20 20V14',
        ],
        [
            'route' => 'admin.reports.request-stats',
            'title' => __('สถิติคำร้อง'),
            'subtitle' => __('ปริมาณ อัตราอนุมัติ และเวลาตรวจสอบเฉลี่ย'),
            'color' => 'cyan',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        ],
        [
            'route' => 'admin.reports.survey',
            'title' => __('ความพึงพอใจต่อกิจกรรม'),
            'subtitle' => __('ค่าเฉลี่ย/S.D. จากแบบประเมินหลังกิจกรรม แยกรายกิจกรรมและหมวด'),
            'color' => 'amber',
            'icon' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
        ],
    ];

    $colorClasses = [
        'green' => ['border' => 'border-brand-green-100 dark:border-brand-green-500/20', 'bg' => 'bg-brand-green-50 dark:bg-brand-green-500/10', 'well' => 'bg-brand-green-500'],
        'amber' => ['border' => 'border-amber-100 dark:border-amber-500/20', 'bg' => 'bg-amber-50 dark:bg-amber-500/10', 'well' => 'bg-amber-500'],
        'purple' => ['border' => 'border-brand-purple-100 dark:border-brand-purple-500/20', 'bg' => 'bg-brand-purple-50 dark:bg-brand-purple-500/10', 'well' => 'bg-brand-purple-500'],
        'teal' => ['border' => 'border-teal-100 dark:border-teal-500/20', 'bg' => 'bg-teal-50 dark:bg-teal-500/10', 'well' => 'bg-teal-500'],
        'sky' => ['border' => 'border-sky-100 dark:border-sky-500/20', 'bg' => 'bg-sky-50 dark:bg-sky-500/10', 'well' => 'bg-sky-500'],
        'cyan' => ['border' => 'border-cyan-100 dark:border-cyan-500/20', 'bg' => 'bg-cyan-50 dark:bg-cyan-500/10', 'well' => 'bg-cyan-600'],
    ];
@endphp
<div class="mx-auto max-w-5xl">
    <x-brand-header :title="__('รายงาน')" :eyebrow="__('กองพัฒนานักศึกษา')" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($reports as $report)
            @php $c = $colorClasses[$report['color']]; @endphp
            <a href="{{ route($report['route']) }}"
                class="flex flex-col gap-3 rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-5 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $c['well'] }} text-white shadow-soft">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $report['icon'] }}"/></svg>
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $report['title'] }}</h2>
                    <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $report['subtitle'] }}</p>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
