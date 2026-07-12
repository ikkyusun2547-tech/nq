@extends('layouts.dashboard')

@section('content')
@php
    $statusLabel = ['auto_approved' => __('ผ่านอัตโนมัติ'), 'flagged' => __('ติดธงแดง'), 'rejected' => __('ปฏิเสธ')];
    // Same labels the student sees for their own flagged check-in — see
    // App\Models\Attendance::REASON_LABELS's docblock for why this must stay
    // the single source instead of a second hand-maintained copy here.
    $reasonLabel = collect(\App\Models\Attendance::REASON_LABELS)->map(fn ($label) => __($label))->all();
    $badgeDot = [
        'auto_approved' => 'bg-brand-green-500',
        'flagged' => 'bg-red-500',
        'rejected' => 'bg-slate-400',
    ];
@endphp

<div
    class="mx-auto max-w-[90rem]"
    x-data="{
        selected: [],
        lightboxUrl: null,
        showMissingModal: false,
        missingSearch: '',
        toggleAll(checked) {
            this.selected = checked ? Array.from(document.querySelectorAll('.row-checkbox')).map(el => el.value) : [];
        },
        submitIds(ids, form) {
            if (! ids.length) { alert('{{ __('กรุณาเลือกอย่างน้อย 1 รายการ') }}'); return; }
            form.querySelector('.ids-container').innerHTML = ids.map(id => `<input type='hidden' name='attendance_ids[]' value='${id}'>`).join('');
            form.submit();
        },
        approveAllValid() {
            const ids = Array.from(document.querySelectorAll('.row-checkbox[data-status=auto_approved]')).map(el => el.value);
            this.submitIds(ids, this.$refs.bulkForm);
        },
        forceBypassSelected() {
            this.submitIds(this.selected, this.$refs.bulkForm);
        },
    }"
>
    <x-brand-header :title="$activity->title" :back="route('admin.activities.index')">
        <x-slot:eyebrow>
            Live Event Control
            @if ($activity->activity_code)
                · <span class="font-mono">{{ $activity->activity_code }}</span>
            @endif
        </x-slot:eyebrow>
        <x-slot:subtitle>{{ __('ผู้เช็คชื่อทั้งหมด :count คน', ['count' => $attendances->count()]) }}</x-slot:subtitle>
        <x-slot:actions>
            <button
                type="button" @click="showMissingModal = true"
                class="flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15"
                title="{{ __('คำนวณจากรายชื่อนักศึกษาที่มีสิทธิ์เข้าร่วมในระบบ') }}"
            >
                {{ __('เช็คชื่อแล้ว :checked / ต้องเข้าร่วม :required คน', ['checked' => $checkedInCount, 'required' => $requiredCount]) }}
                @if ($missingStudents->isNotEmpty())
                    <span class="rounded-full bg-amber-400 px-1.5 py-0.5 text-[0.65rem] font-bold text-brand-purple-950">{{ $missingStudents->count() }}</span>
                @endif
            </button>
            <a href="{{ route('admin.attendance.export', $activity) }}"
                class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
                Export Excel
            </a>
        </x-slot:actions>
    </x-brand-header>

    <!-- Filter bar -->
    <form method="GET" action="{{ route('admin.attendance.index', $activity) }}" class="mt-4 space-y-3">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อ หรือ รหัสนักศึกษา') }}"
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
            $facultyOptions = $faculties->pluck('name_th', 'id')->all();

            if (request('faculty_id')) {
                $selectedFaculty = $faculties->firstWhere('id', (int) request('faculty_id'));
                $majorOptions = $selectedFaculty?->majors->pluck('name_th', 'id')->all() ?? [];
                $majorGroups = null;
            } else {
                $majorOptions = null;
                $majorGroups = $faculties->filter(fn ($f) => $f->majors->isNotEmpty())
                    ->mapWithKeys(fn ($f) => [$f->name_th => $f->majors->pluck('name_th', 'id')->all()])
                    ->all();
            }
        @endphp

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-premium-select
                name="status" :options="$statusLabel" :selected="request('status')"
                placeholder="{{ __('-- ทุกสถานะ --') }}" autosubmit
            />

            <x-premium-select
                name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
                placeholder="{{ __('-- ทุกคณะ --') }}" autosubmit resets="major_id"
            />

            <x-premium-select
                name="major_id" :options="$majorOptions" :groups="$majorGroups" :selected="request('major_id')"
                placeholder="{{ __('-- ทุกสาขา --') }}" autosubmit
            />
        </div>
    </form>

    <!-- Bulk action bar -->
    <div class="mb-3 mt-4 flex flex-wrap items-center gap-3 rounded-2xl glass-card p-4 shadow-soft">
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('เลือกแล้ว') }} <span class="font-semibold text-brand-purple-700 dark:text-brand-purple-400" x-text="selected.length"></span> {{ __('รายการ') }}</span>
        <button @click="approveAllValid()" type="button"
            class="rounded-xl bg-brand-green-500 px-4 py-2 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
            {{ __('อนุมัติทั้งหมดที่ถูกต้อง') }}
        </button>
        <button @click="forceBypassSelected()" type="button"
            class="rounded-xl bg-red-500 px-4 py-2 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-lg">
            {{ __('บังคับอนุมัติที่เลือก') }}
        </button>
        <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('ใช้ "Force Bypass" เมื่อพบปัญหาหน้างาน เช่น GPS คลาดเคลื่อนทั้งอาคาร') }}</span>
    </div>

    <form method="POST" action="{{ route('admin.attendance.bulk-approve', $activity) }}" x-ref="bulkForm">
        @csrf
        <div class="ids-container"></div>
    </form>

    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600"></th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('รหัสนักศึกษา') }}</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อ-นามสกุล') }}</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ/สาขา') }}</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชั้นปี') }}</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('เวลาเช็คชื่อ') }}</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ระยะห่าง') }}</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $att)
                    <tr @class([
                        'border-b border-slate-100 dark:border-slate-800 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:hover:bg-slate-800/60',
                        'bg-red-50/60 dark:bg-red-500/10' => $att->status === 'flagged',
                        'bg-white dark:bg-slate-900' => $att->status !== 'flagged' && $loop->even,
                        'bg-slate-50/50 dark:bg-slate-800/40' => $att->status !== 'flagged' && $loop->odd,
                    ])>
                        <td class="whitespace-nowrap px-3 py-3">
                            <input type="checkbox" class="row-checkbox rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600"
                                value="{{ $att->id }}" data-status="{{ $att->status }}"
                                x-model="selected">
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $att->user->student_id }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-700 dark:text-slate-300">{{ $att->user->name_thai ?? $att->user->name }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-500 dark:text-slate-400">{{ $att->user->faculty?->name_th }} / {{ $att->user->major?->name_th }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-500 dark:text-slate-400">{{ $att->user->current_year }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-500 dark:text-slate-400">{{ $att->checkin_time->format('H:i:s') }}</td>
                        <td class="whitespace-nowrap px-3 py-3 @class(['font-semibold text-red-600 dark:text-red-400' => $att->status === 'flagged', 'text-slate-500 dark:text-slate-400' => $att->status !== 'flagged'])">
                            @if (is_null($att->distance_meters))
                                <span class="text-slate-400 dark:text-slate-500">—</span>
                            @else
                                {{ $att->distance_meters }} m
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400' => $att->status === 'auto_approved',
                                'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' => $att->status === 'flagged',
                                'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => $att->status === 'rejected',
                            ])>
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $badgeDot[$att->status]])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $badgeDot[$att->status]])></span>
                                </span>
                                {{ $statusLabel[$att->status] }}
                            </span>
                            @if ($att->flag_reason)
                                <p class="mt-1 whitespace-normal text-xs text-red-500 dark:text-red-400">
                                    {{ collect(explode(',', $att->flag_reason))->map(fn($r) => $reasonLabel[$r] ?? $r)->join(', ') }}
                                </p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">
                            <button @click="lightboxUrl = '{{ asset('storage/'.$att->photo_path) }}'" type="button" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">
                                {{ in_array($att->checkin_method, ['self_report', 'late_request'], true) ? __('รูปหลักฐาน') : __('รูปเซลฟี') }}
                            </button>
                            @if ($att->student_lat !== null && $att->student_lng !== null)
                                <a href="https://www.google.com/maps?q={{ $att->student_lat }},{{ $att->student_lng }}" target="_blank" class="ml-2 font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('แผนที่') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีผู้เช็คชื่อ') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Selfie lightbox -->
    <div x-show="lightboxUrl" x-cloak @click="lightboxUrl = null" class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm">
        <img :src="lightboxUrl" class="max-h-[80vh] max-w-full rounded-2xl shadow-soft-lg">
    </div>

    <!-- Missing students modal -->
    <div
        x-show="showMissingModal" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm"
    >
        <div
            @click.outside="showMissingModal = false"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="w-full max-w-2xl rounded-[2rem] bg-gradient-to-br from-white/60 via-white/10 to-amber-200/40 p-[1.5px] shadow-soft-lg dark:from-white/10 dark:via-white/5 dark:to-amber-500/20"
        >
            <div class="flex max-h-[85vh] flex-col rounded-[calc(2rem-1.5px)] bg-white dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5 dark:border-slate-800">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ __('รายชื่อที่ยังไม่เข้าร่วม') }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ __(':count คน จากผู้มีสิทธิ์ทั้งหมด :required คน', ['count' => $missingStudents->count(), 'required' => $requiredCount]) }}</p>
                    </div>
                    <button @click="showMissingModal = false" class="shrink-0 rounded-full p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex items-center gap-2 border-b border-slate-100 p-4 dark:border-slate-800">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-slate-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        </span>
                        <input
                            type="text" x-model="missingSearch" placeholder="{{ __('ค้นหาในรายชื่อนี้') }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-sm shadow-soft transition-all duration-200 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100"
                        >
                    </div>
                    <a href="{{ route('admin.attendance.missing-export', $activity) }}"
                        class="flex shrink-0 items-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-2 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-amber-400 hover:shadow-lg">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Excel
                    </a>
                </div>

                <div class="flex-1 overflow-y-auto p-2">
                    @forelse ($missingStudents as $student)
                        <div
                            x-show="missingSearch === '' || {{ Illuminate\Support\Js::from(strtolower(($student->name_thai ?? $student->name).' '.$student->student_id)) }}.includes(missingSearch.toLowerCase())"
                            class="flex items-center justify-between gap-3 rounded-xl px-3.5 py-2.5 transition-colors hover:bg-amber-50/60 dark:hover:bg-slate-800/60"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-xs font-semibold text-brand-purple-950 shadow-soft">
                                    {{ mb_substr($student->name_thai ?? $student->name, 0, 1) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ $student->name_thai ?? $student->name }}</p>
                                    <p class="truncate text-xs text-slate-400 dark:text-slate-500">
                                        <span class="font-mono">{{ $student->student_id }}</span>
                                        · {{ $student->faculty?->name_th }} / {{ $student->major?->name_th }}
                                    </p>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400 dark:text-slate-500">{{ __('ปี :year', ['year' => $student->current_year]) }}</span>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-slate-400 dark:text-slate-500">{{ __('ทุกคนเช็คชื่อครบแล้ว 🎉') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
