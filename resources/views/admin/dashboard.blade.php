@extends('layouts.dashboard')

@section('content')
@php
    $categoryMeta = [
        'culture' => ['label' => __('ทำนุบำรุงศิลปวัฒนธรรม'), 'bar' => 'bg-sky-400'],
        'academic' => ['label' => __('วิชาการ'), 'bar' => 'bg-brand-green-500'],
        'sports' => ['label' => __('กีฬาและส่งเสริมสุขภาพ'), 'bar' => 'bg-amber-400'],
        'volunteer' => ['label' => __('จิตอาสา/บำเพ็ญประโยชน์'), 'bar' => 'bg-brand-purple-500'],
        'ethics' => ['label' => __('คุณธรรมจริยธรรม'), 'bar' => 'bg-fuchsia-400'],
    ];
    $statusBadge = [
        'open' => ['label' => __('เปิดรับสมัคร'), 'class' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400'],
        'ongoing' => ['label' => __('กำลังดำเนินการ'), 'class' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400'],
        'draft' => ['label' => __('ร่าง'), 'class' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'],
    ];
    $maxCategoryHours = max(1, max($categoryHours));
    $maxTrend = max(1, $monthlyTrend->max('count'));
    $maxFaculty = max(1, $facultyParticipation->max('total') ?? 1);
    $academicYearScopeLabel = $academicYear !== '' ? __('ปีการศึกษา :year', ['year' => $academicYear]) : __('ทุกปีการศึกษา');
@endphp
<div class="mx-auto max-w-[90rem]">
    <x-brand-header :title="__('แผงควบคุมกองพัฒนานักศึกษา')" :subtitle="__('มหาวิทยาลัยราชภัฏสุรินทร์')" />

    <form method="GET" action="{{ route('admin.dashboard') }}" class="mt-4 max-w-xs">
        @php $academicYearOptions = $academicYears->mapWithKeys(fn ($y) => [$y => __('ปีการศึกษา :year', ['year' => $y])])->all(); @endphp
        <x-premium-select
            name="academic_year" :options="$academicYearOptions" :selected="$academicYear"
            placeholder="{{ __('-- ทุกปีการศึกษา --') }}" autosubmit
        />
    </form>

    <!-- Stat cards -->
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        @php
            $cards = [
                ['label' => __('นักศึกษาทั้งหมด'), 'value' => $stats['total_students'], 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'tint' => 'text-brand-purple-600 bg-brand-purple-50 dark:text-brand-purple-400 dark:bg-brand-purple-500/10'],
                ['label' => __('กิจกรรมที่เปิดอยู่'), 'value' => $stats['open_activities'], 'suffix' => __('/ :total ทั้งหมด', ['total' => $stats['total_activities']]), 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5', 'tint' => 'text-brand-green-600 bg-brand-green-50 dark:text-brand-green-400 dark:bg-brand-green-500/10'],
                ['label' => __('เช็กชื่อเดือนนี้'), 'value' => $stats['checkins_this_month'], 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'tint' => 'text-sky-600 bg-sky-50 dark:text-sky-400 dark:bg-sky-500/10'],
                ['label' => __('คำร้องภายนอกรออนุมัติ'), 'value' => $stats['pending_external_requests'], 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'tint' => 'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-500/10', 'href' => route('admin.external-activities.index')],
                ['label' => __('เทียบโอนตำแหน่งรออนุมัติ'), 'value' => $stats['pending_credit_transfers'], 'icon' => 'M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15', 'tint' => 'text-brand-purple-600 bg-brand-purple-50 dark:text-brand-purple-400 dark:bg-brand-purple-500/10', 'href' => route('admin.credit-transfers.index')],
                ['label' => __('การเช็กชื่อติดธงแดง'), 'value' => $stats['flagged_attendances'], 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z', 'tint' => 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-500/10'],
                ['label' => __('นักศึกษาปี 4 พร้อมจบ'), 'value' => $stats['graduating_cleared'], 'icon' => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5', 'tint' => 'text-brand-green-600 bg-brand-green-50 dark:text-brand-green-400 dark:bg-brand-green-500/10', 'href' => route('admin.reports.clearance', ['year' => 4])],
            ];
        @endphp
        @foreach ($cards as $card)
            @php $tag = isset($card['href']) ? 'a' : 'div'; @endphp
            <{{ $tag }} @if(isset($card['href'])) href="{{ $card['href'] }}" @endif
                class="rounded-2xl glass-card p-4 shadow-soft transition @if(isset($card['href'])) hover:-translate-y-0.5 hover:shadow-lg @endif">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $card['tint'] }}">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $card['icon'] }}"/></svg>
                </span>
                <p class="mt-3 text-2xl font-semibold tabular-nums text-gray-900 dark:text-slate-100">{{ number_format($card['value']) }}<span class="text-xs font-normal text-gray-400 dark:text-slate-500">{{ $card['suffix'] ?? '' }}</span></p>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">{{ $card['label'] }}</p>
            </{{ $tag }}>
        @endforeach
    </div>

    <!-- Charts -->
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-section-card icon="M4 20V10M12 20V4M20 20V14" :title="__('ชั่วโมงกิจกรรมแยกตามหมวดหมู่')">
            <x-slot:action>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $academicYearScopeLabel }}</span>
            </x-slot:action>
            @foreach ($categoryHours as $category => $hours)
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                        <span>{{ $categoryMeta[$category]['label'] ?? $category }}</span>
                        <span class="tabular-nums font-medium text-gray-700 dark:text-slate-200">{{ __(':hours ชม.', ['hours' => number_format($hours)]) }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800">
                        <div class="h-full rounded-full {{ $categoryMeta[$category]['bar'] ?? 'bg-slate-400' }}" style="width: {{ max(3, round($hours / $maxCategoryHours * 100)) }}%"></div>
                    </div>
                </div>
            @endforeach
        </x-section-card>

        <x-section-card icon="M3 17l6-6 4 4 8-8M21 7v6h-6" :title="$academicYear !== '' ? __('แนวโน้มการเช็กชื่อรายเดือน') : __('แนวโน้มการเช็กชื่อ 6 เดือนล่าสุด')">
            <x-slot:action>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $academicYearScopeLabel }}</span>
            </x-slot:action>
            <div class="flex h-40 items-end justify-between gap-2">
                @foreach ($monthlyTrend as $point)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span class="text-[0.65rem] font-medium tabular-nums text-gray-500 dark:text-slate-400">{{ $point['count'] }}</span>
                        <div class="flex w-full items-end justify-center" style="height: 6.5rem;">
                            <div class="w-full max-w-8 rounded-t-lg bg-brand-purple-400 dark:bg-brand-purple-500/70" style="height: {{ max(4, round($point['count'] / $maxTrend * 100)) }}%"></div>
                        </div>
                        <span class="text-[0.65rem] text-gray-400 dark:text-slate-500">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-section-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Faculty participation -->
        <x-section-card icon="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" :title="__('การเข้าร่วมแยกตามคณะ')">
            <x-slot:action>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $academicYearScopeLabel }}</span>
            </x-slot:action>
            @forelse ($facultyParticipation as $row)
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                        <span class="truncate pr-2">{{ $row->faculty }}</span>
                        <span class="shrink-0 tabular-nums font-medium text-gray-700 dark:text-slate-200">{{ number_format($row->total) }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-brand-green-500" style="width: {{ max(3, round($row->total / $maxFaculty * 100)) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-xs text-gray-400 dark:text-slate-500">{{ __('ยังไม่มีข้อมูลการเช็กชื่อ') }}</p>
            @endforelse
        </x-section-card>

        <!-- Upcoming activities -->
        <x-section-card icon="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" :title="__('กิจกรรมที่กำลังจะถึง')" class="lg:col-span-2">
            <x-slot:action>
                <a href="{{ route('admin.activities.index') }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('ดูทั้งหมด') }} &rarr;</a>
            </x-slot:action>
            <div class="divide-y divide-gray-100 dark:divide-slate-800">
                @forelse ($upcomingActivities as $activity)
                    <a href="{{ route('admin.attendance.index', $activity) }}" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0 hover:opacity-80">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">{{ $activity->title }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $activity->start_at->translatedFormat('d M Y H:i') }}</p>
                        </div>
                        <span class="shrink-0 text-xs tabular-nums text-gray-400 dark:text-slate-500">{{ $activity->attendances_count }}/{{ $activity->required_count }}</span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadge[$activity->status]['class'] ?? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ $statusBadge[$activity->status]['label'] ?? $activity->status }}
                        </span>
                    </a>
                @empty
                    <p class="py-6 text-center text-xs text-gray-400 dark:text-slate-500">{{ __('ไม่มีกิจกรรมที่กำลังจะถึง') }}</p>
                @endforelse
            </div>
        </x-section-card>
    </div>

    @if ($pendingRequests->isNotEmpty())
        <x-section-card icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" :title="__('คำร้องกิจกรรมภายนอกล่าสุด')" class="mt-6">
            <x-slot:action>
                <a href="{{ route('admin.external-activities.index') }}" class="text-xs font-medium text-brand-purple-600 hover:underline dark:text-brand-purple-400">{{ __('ดูทั้งหมด') }} &rarr;</a>
            </x-slot:action>
            <div class="divide-y divide-gray-100 dark:divide-slate-800">
                @foreach ($pendingRequests as $request)
                    <a href="{{ route('admin.external-activities.index') }}" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0 hover:opacity-80">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-xs font-semibold text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                            {{ mb_substr($request->user->name_thai ?? $request->user->name, 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">{{ $request->title }}</p>
                            <p class="truncate text-xs text-gray-400 dark:text-slate-500">{{ $request->user->name_thai ?? $request->user->name }} &middot; {{ $request->organization }}</p>
                        </div>
                        <span class="shrink-0 text-xs tabular-nums font-medium text-amber-600 dark:text-amber-400">{{ __(':hours ชม.', ['hours' => $request->hours_requested]) }}</span>
                    </a>
                @endforeach
            </div>
        </x-section-card>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.activities.index') }}" class="group rounded-2xl glass-card p-6 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lg">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-green-50 text-brand-green-600 group-hover:bg-brand-green-100 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </span>
            <p class="mt-3 font-medium text-gray-900 dark:text-slate-100">{{ __('จัดการกิจกรรม') }}</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">{{ __('สร้าง/แก้ไขกิจกรรม กำหนดสิทธิ์ผู้เข้าร่วม') }}</p>
        </a>
        <a href="{{ route('admin.external-activities.index') }}" class="group rounded-2xl glass-card p-6 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lg">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-purple-50 text-brand-purple-600 group-hover:bg-brand-purple-100 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <p class="mt-3 font-medium text-gray-900 dark:text-slate-100">{{ __('คำร้องกิจกรรมภายนอก') }}</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">{{ __('ตรวจสอบและอนุมัติ/ปฏิเสธคำร้อง') }}</p>
        </a>
        <a href="{{ route('admin.credit-transfers.index') }}" class="group rounded-2xl glass-card p-6 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lg">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-purple-50 text-brand-purple-600 group-hover:bg-brand-purple-100 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 6.75h15m-15 0A2.25 2.25 0 002.25 9v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 15V9a2.25 2.25 0 00-2.25-2.25m-15 0V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25v1.5m-15 0h15"/></svg>
            </span>
            <p class="mt-3 font-medium text-gray-900 dark:text-slate-100">{{ __('คำร้องเทียบโอนชั่วโมงจากตำแหน่ง') }}</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">{{ __('ตรวจสอบ อนุมัติหมวดหมู่ และให้เครดิตชั่วโมง') }}</p>
        </a>
        <a href="{{ route('admin.reports.clearance', ['year' => 4]) }}" class="group rounded-2xl glass-card p-6 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lg">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-green-50 text-brand-green-600 group-hover:bg-brand-green-100 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </span>
            <p class="mt-3 font-medium text-gray-900 dark:text-slate-100">{{ __('รายงานนักศึกษาพร้อมยื่นจบ (PDF)') }}</p>
            <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">{{ __('ชั้นปีที่ 4 ที่ผ่านเกณฑ์ครบ 100% ส่งต่อสำนักทะเบียน') }}</p>
        </a>
    </div>
</div>
@endsection
