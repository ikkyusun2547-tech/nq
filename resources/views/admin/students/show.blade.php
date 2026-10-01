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
    $attendanceBadge = [
        'auto_approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'flagged' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $attendanceLabel = ['auto_approved' => __('อนุมัติแล้ว'), 'flagged' => __('ถูกตรวจสอบ'), 'rejected' => __('ไม่อนุมัติ')];
    $externalBadge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $externalLabel = ['pending' => __('รอตรวจสอบ'), 'approved' => __('อนุมัติแล้ว'), 'rejected' => __('ปฏิเสธแล้ว')];
    // Same badge/label sets apply to late check-ins and credit transfers —
    // all three request types share the pending/approved/rejected enum.
    $positionLabels = collect(\App\Models\CreditTransferPosition::labelsMap())->map(fn ($label) => __($label))->all();
    $categoryOptions = collect($categoryMeta)->map(fn ($meta) => $meta['label'])->all();
    $currentAcademicYear = \App\Services\AcademicYearCalculator::forDate(now());
    $missingActivities = max(0, $summary['required_activities'] - $summary['total_activities']);
    $missingHours = max(0, $summary['required_hours'] - $summary['total_hours']);
    $historyTabs = [
        'attendance' => [__('เช็คชื่อ'), $attendances->count()],
        'external' => [__('กิจกรรมภายนอก'), $externalRequests->count()],
        'late' => [__('เช็คชื่อย้อนหลัง'), $lateCheckIns->count()],
        'credit' => [__('เทียบโอนตำแหน่ง'), $creditTransfers->count()],
    ];
    $statusChip = 'shrink-0 rounded-full px-2.5 py-1 text-xs font-medium';
    $neutralChip = 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400';
@endphp

<div class="mx-auto max-w-4xl" x-data="{ grant: {{ $errors->any() ? 'true' : 'false' }} }">
    @php $initials = mb_substr(preg_replace('/^(นาย|นางสาว|นาง)/u', '', (string) ($student->name_thai ?? $student->name)), 0, 2); @endphp
    <div class="flex flex-wrap items-center gap-4">
        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 font-display text-xl text-brand-purple-700 ring-1 ring-brand-purple-100 dark:bg-brand-purple-500/15 dark:text-brand-purple-300 dark:ring-brand-purple-500/20">{{ $initials }}</span>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Activity Passport · SRRU</p>
            <h1 class="mt-0.5 font-display text-2xl leading-tight text-slate-900 dark:text-white sm:text-[1.75rem]">{{ $student->name_thai ?? $student->name }}</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $student->faculty?->name_th ?? '-' }} · {{ $student->major?->name_th ?? '-' }}</p>
        </div>
        @if (auth()->user()->role === 'super_admin')
            <button type="button" @click="grant = ! grant; if (grant) $nextTick(() => document.getElementById('grant-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' }))" :aria-expanded="grant"
                class="inline-flex items-center gap-1.5 rounded-full bg-brand-purple-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('เพิ่มชั่วโมงเทียบโอนตำแหน่ง') }}
            </button>
        @endif
    </div>
    <div class="mt-4 flex flex-wrap gap-2 text-xs">
        @if ($summary['current_year'])
            <span class="rounded-full bg-brand-purple-50 px-3 py-1 font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">{{ __('ชั้นปีที่') }} {{ $summary['current_year'] }}</span>
        @endif
        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 font-mono text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">{{ __('รหัส') }} {{ $student->student_id ?? '-' }}</span>
        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">{{ $student->program_type === 'special' ? __('ภาคพิเศษ (กศ.บป.)') : __('ภาคปกติ') }}</span>
        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">{{ $student->email }}</span>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($student->isGraduated())
        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl bg-brand-purple-50 p-4 text-sm ring-1 ring-brand-purple-100 dark:bg-brand-purple-500/10 dark:ring-brand-purple-500/20">
            <span class="font-medium text-brand-purple-700 dark:text-brand-purple-400">
                {{ __('นักศึกษาจบการศึกษาแล้วเมื่อ :date', ['date' => $student->graduated_at->format('d/m/Y')]) }}
            </span>
            @if (auth()->user()->role === 'super_admin')
                <form method="POST" action="{{ route('admin.users.ungraduate', $student) }}" class="ml-auto">
                    @csrf
                    <x-confirm-submit tone="slate" :message="__('ยืนยันยกเลิกสถานะจบการศึกษา?')" :label="__('ยกเลิกสถานะจบ')"
                        class="rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200 transition-colors hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600">{{ __('ยกเลิกสถานะจบ') }}</x-confirm-submit>
                </form>
            @endif
        </div>
    @endif

    @if (auth()->user()->role === 'super_admin')
        {{-- Opened from the button next to the student's name; reopens by
             itself when the form comes back with validation errors. --}}
        <div id="grant-panel" x-show="grant" x-cloak x-transition.opacity class="mt-6 rounded-3xl border-2 border-brand-purple-200 bg-white dark:border-brand-purple-500/30 dark:bg-slate-900">
            <div class="flex items-center gap-3 p-5 sm:px-6">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-purple-700 text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('เพิ่มชั่วโมงเทียบโอนตำแหน่ง') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('บันทึกชั่วโมงให้นักศึกษาคนนี้โดยตรง (เฉพาะ Admin สูงสุด)') }}</p>
                </div>
                <button type="button" @click="grant = false" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="{{ __('ปิด') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.credit-transfers.grant', $student) }}" enctype="multipart/form-data"
                class="space-y-3 border-t border-slate-100 p-5 dark:border-slate-800 sm:px-6">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <span class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('ตำแหน่ง') }}</span>
                        <x-premium-select name="position" :options="$positionLabels" :selected="old('position')" placeholder="{{ __('-- เลือกตำแหน่ง --') }}" />
                    </div>
                    <div>
                        <span class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('หมวดหมู่') }}</span>
                        <x-premium-select name="activity_category" :options="$categoryOptions" :selected="old('activity_category')" placeholder="{{ __('-- เลือกหมวดหมู่ --') }}" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('ปีการศึกษา') }}</span>
                        <input type="number" name="academic_year" value="{{ old('academic_year', $currentAcademicYear) }}" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('ชั่วโมง') }}</span>
                        <input type="number" name="hours_approved" value="{{ old('hours_approved') }}" min="0" max="200"
                            placeholder="{{ __('ค่ามาตรฐานตามตำแหน่ง') }}"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('หลักฐาน (ถ้ามี)') }}</span>
                        <input type="file" name="proof_image" accept=".jpg,.jpeg,.png,.pdf"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-brand-purple-50 file:px-2.5 file:py-1.5 file:text-xs file:font-medium file:text-brand-purple-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-400">
                    </label>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('เว้นว่างช่องชั่วโมง = ใช้ชั่วโมงมาตรฐานของตำแหน่งที่เลือก') }}</p>

                <div class="flex justify-end">
                    <x-confirm-submit tone="purple" :message="__('ยืนยันเพิ่มชั่วโมงเทียบโอนตำแหน่งให้นักศึกษาคนนี้?')" :label="__('เพิ่มชั่วโมง')"
                        class="rounded-full bg-brand-purple-700 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">{{ __('เพิ่มชั่วโมง') }}</x-confirm-submit>
                </div>
            </form>
        </div>
    @endif

    {{-- Progress: one card that answers "has this student passed, and how far off are they?" --}}
    <div class="mt-6 rounded-3xl glass-card p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('ภาพรวมชั่วโมงกิจกรรม') }}</h2>
            @if ($summary['is_cleared'])
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-green-50 px-3 py-1 text-xs font-semibold text-brand-green-800 dark:bg-brand-green-500/15 dark:text-brand-green-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    {{ __('ผ่านเกณฑ์รับใบรับรองกิจกรรมแล้ว') }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    {{ __('ยังไม่ผ่านเกณฑ์') }}
                </span>
            @endif
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
            @foreach ([
                [__('ชั่วโมงสะสมรวม'), $summary['total_hours'], $summary['required_hours'], __('ชม.'), $hoursPct, $missingHours],
                [__('จำนวนกิจกรรมสะสม'), $summary['total_activities'], $summary['required_activities'], __('งาน'), $activitiesPct, $missingActivities],
            ] as [$label, $have, $need, $unit, $pct, $missing])
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
                    <p class="mt-1 flex items-baseline gap-1.5">
                        <span class="font-display text-3xl text-slate-900 dark:text-white">{{ $have }}</span>
                        <span class="text-sm text-slate-500 dark:text-slate-400">/ {{ $need }} {{ $unit }}</span>
                    </p>
                    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                        <div @class(['h-full rounded-full', 'bg-brand-green-500' => $missing <= 0, 'bg-brand-purple-600' => $missing > 0]) style="width: {{ $pct }}%"></div>
                    </div>
                    <p class="mt-2 text-xs {{ $missing <= 0 ? 'text-brand-green-700 dark:text-brand-green-400' : 'text-slate-500 dark:text-slate-400' }}">
                        {{ $missing <= 0 ? __('ครบแล้ว') : __('ขาดอีก :n :unit', ['n' => $missing, 'unit' => $unit]) }}
                    </p>
                </div>
            @endforeach
        </div>

        @if ($summary['yearly_target_hours'])
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('เป้าหมายชั่วโมงกิจกรรมของชั้นปีที่ :year คือ :hours ชั่วโมง/ปี', ['year' => $summary['current_year'], 'hours' => $summary['yearly_target_hours']]) }}</p>
        @endif

        <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ชั่วโมงสะสมแยกตามหมวดหมู่ (5 ด้าน)') }}</h3>
            <div class="mt-3 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                @foreach ($categoryMeta as $key => $meta)
                    @php
                        $hours = $summary['category_hours'][$key] ?? 0;
                        $pct = min(100, $summary['required_hours'] > 0 ? round($hours / $summary['required_hours'] * 100) : 0);
                    @endphp
                    <div>
                        <div class="mb-1 flex items-baseline justify-between gap-2 text-sm">
                            <span class="flex min-w-0 items-center gap-2 text-slate-700 dark:text-slate-300">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $meta['dot'] }}"></span>
                                <span class="truncate">{{ $meta['label'] }}</span>
                            </span>
                            <span class="shrink-0 font-medium {{ $hours > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">{{ $hours }} {{ __('ชม.') }}</span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full {{ $meta['dot'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- History: one card, one list at a time instead of four boxes side by side. --}}
    <div class="mt-4 rounded-3xl glass-card p-5 sm:p-6" x-data="{ tab: 'attendance' }">
        <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('ประวัติและคำร้องล่าสุด') }}</h2>
        <div class="mt-3 flex flex-wrap gap-1.5" role="tablist">
            @foreach ($historyTabs as $key => [$label, $count])
                <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-brand-purple-700 text-white dark:bg-brand-purple-600' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors">
                    {{ $label }}
                    <span class="rounded-full bg-black/10 px-1.5 text-xs dark:bg-white/10">{{ $count }}</span>
                </button>
            @endforeach
        </div>

        <div class="mt-4">
            <ul x-show="tab === 'attendance'" class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($attendances as $att)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ $att->activity->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $att->checkin_time->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="{{ $statusChip }} {{ $attendanceBadge[$att->status] ?? $neutralChip }}">{{ $attendanceLabel[$att->status] ?? $att->status }}</span>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีประวัติเช็คชื่อ') }}</li>
                @endforelse
            </ul>

            <ul x-show="tab === 'external'" x-cloak class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($externalRequests as $req)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ $req->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ $req->activity_date->format('d/m/Y') }} ·
                                @if ($req->status === 'approved' && $req->hours_approved !== null && $req->hours_approved != $req->hours_requested)
                                    <span class="text-slate-400 line-through">{{ $req->hours_requested }}</span> {{ $req->hours_credited }} {{ __('ชม.') }}
                                @else
                                    {{ $req->hours_requested }} {{ __('ชม.') }}
                                @endif
                            </p>
                        </div>
                        <span class="{{ $statusChip }} {{ $externalBadge[$req->status] ?? $neutralChip }}">{{ $externalLabel[$req->status] ?? $req->status }}</span>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีคำร้องกิจกรรมภายนอก') }}</li>
                @endforelse
            </ul>

            <ul x-show="tab === 'late'" x-cloak class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($lateCheckIns as $req)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ $req->activity->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ $req->created_at->format('d/m/Y') }} ·
                                @if ($req->status === 'approved' && $req->hours_approved !== null && $req->hours_approved != $req->activity->credit_hours)
                                    <span class="text-slate-400 line-through">{{ $req->activity->credit_hours }}</span> {{ $req->hours_credited }} {{ __('ชม.') }}
                                @else
                                    {{ $req->activity->credit_hours }} {{ __('ชม.') }}
                                @endif
                            </p>
                        </div>
                        <span class="{{ $statusChip }} {{ $externalBadge[$req->status] ?? $neutralChip }}">{{ $externalLabel[$req->status] ?? $req->status }}</span>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีคำร้องเช็คชื่อย้อนหลัง') }}</li>
                @endforelse
            </ul>

            <ul x-show="tab === 'credit'" x-cloak class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($creditTransfers as $req)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ $positionLabels[$req->position] ?? $req->position }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('ปีการศึกษา :year', ['year' => $req->academic_year]) }} ·
                                @if ($req->status === 'approved' && $req->hours_approved !== null && $req->hours_approved != $req->hours_requested)
                                    <span class="text-slate-400 line-through">{{ $req->hours_requested }}</span> {{ $req->hours_credited }} {{ __('ชม.') }}
                                @else
                                    {{ $req->hours_requested }} {{ __('ชม.') }}
                                @endif
                            </p>
                        </div>
                        <span class="{{ $statusChip }} {{ $externalBadge[$req->status] ?? $neutralChip }}">{{ $externalLabel[$req->status] ?? $req->status }}</span>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีคำร้องเทียบโอนตำแหน่ง') }}</li>
                @endforelse
            </ul>
        </div>
    </div>

</div>
@endsection
