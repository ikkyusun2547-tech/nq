@extends('layouts.dashboard')

@section('content')
@php
    $statusLabel = ['auto_approved' => __('อนุมัติแล้ว'), 'flagged' => __('รอตรวจ'), 'rejected' => __('ไม่อนุมัติ')];
    // Same labels the student sees for their own flagged check-in — see
    // App\Models\Attendance::REASON_LABELS's docblock for why this must stay
    // the single source instead of a second hand-maintained copy here.
    $reasonLabel = collect(\App\Models\Attendance::REASON_LABELS)->map(fn ($label) => __($label))->all();
    $statusChip = [
        'auto_approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'flagged' => 'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
        'rejected' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    ];
    $statusDot = ['auto_approved' => 'bg-brand-green-500', 'flagged' => 'bg-amber-500', 'rejected' => 'bg-slate-400'];
    $flaggedCount = (int) ($statusCounts['flagged'] ?? 0);
    $checkedPct = $requiredCount > 0 ? min(100, round($checkedInCount / $requiredCount * 100)) : 0;
    // Status is picked with the summary tiles; keep the other filters when switching.
    $statusUrl = fn (?string $status) => route('admin.attendance.index', array_filter(
        array_merge(['activity' => $activity], request()->only(['search', 'faculty_id', 'major_id', 'sort', 'dir']), ['status' => $status]),
        fn ($v) => $v !== null && $v !== ''
    ));
@endphp

<div
    class="mx-auto max-w-[90rem]"
    x-data="{
        selected: [],
        lightboxUrl: null,
        showMissingModal: false,
        missingSearch: '',
        rejectUrl: null,
        rejectName: '',
        selectAllFlagged() {
            this.selected = Array.from(document.querySelectorAll('.row-checkbox[data-status=flagged]')).map(el => el.value);
        },
        approveSelected() {
            if (! this.selected.length) return;
            const form = this.$refs.bulkForm;
            form.querySelector('.ids-container').innerHTML = this.selected.map(id => `<input type='hidden' name='attendance_ids[]' value='${id}'>`).join('');
            form.submit();
        },
    }"
>
    <x-brand-header :title="$activity->title">
        <x-slot:eyebrow>
            {{ __('เช็คชื่อหน้างาน') }}
            @if ($activity->activity_code)
                · <span class="font-mono">{{ $activity->activity_code }}</span>
            @endif
        </x-slot:eyebrow>
        <x-slot:actions>
            <a href="{{ route('admin.attendance.export', $activity) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-brand-purple-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-brand-purple-500/40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Excel
            </a>
            <a href="{{ route('admin.attendance.qr-display', $activity) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-purple-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-purple-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg>
                {{ __('แสดง QR') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    {{-- Summary tiles: each one is also the status filter. --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @php
            $tile = 'rounded-2xl border p-4 text-left transition-colors';
            $tileIdle = 'border-slate-200 bg-white hover:border-brand-purple-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-purple-500/40';
            $tileOn = 'border-brand-purple-600 bg-brand-purple-50 ring-1 ring-brand-purple-600 dark:border-brand-purple-500 dark:bg-brand-purple-500/10';
        @endphp
        <a href="{{ $statusUrl(null) }}" class="{{ $tile }} {{ request('status') ? $tileIdle : $tileOn }}">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('เช็คชื่อแล้ว') }}</p>
            <p class="mt-1 font-display text-2xl text-slate-900 dark:text-white">{{ $checkedInCount }} <span class="text-sm text-slate-500 dark:text-slate-400">/ {{ $requiredCount }}</span></p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-brand-purple-600" style="width: {{ $checkedPct }}%"></div></div>
        </a>
        <a href="{{ $statusUrl('auto_approved') }}" class="{{ $tile }} {{ request('status') === 'auto_approved' ? $tileOn : $tileIdle }}">
            <p class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400"><span class="h-2 w-2 rounded-full bg-brand-green-500"></span>{{ __('อนุมัติแล้ว') }}</p>
            <p class="mt-1 font-display text-2xl text-slate-900 dark:text-white">{{ (int) ($statusCounts['auto_approved'] ?? 0) }}</p>
        </a>
        <a href="{{ $statusUrl('flagged') }}" class="{{ $tile }} {{ request('status') === 'flagged' ? $tileOn : $tileIdle }}">
            <p class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ __('รอตรวจ') }}</p>
            <p class="mt-1 font-display text-2xl {{ $flaggedCount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-white' }}">{{ $flaggedCount }}</p>
        </a>
        <button type="button" @click="showMissingModal = true" class="{{ $tile }} {{ $tileIdle }}">
            <p class="flex items-center justify-between gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                {{ __('ยังไม่เช็คชื่อ') }}
                <span class="text-brand-purple-700 dark:text-brand-purple-300">{{ __('ดูรายชื่อ') }} ›</span>
            </p>
            <p class="mt-1 font-display text-2xl text-slate-900 dark:text-white">{{ $missingStudents->count() }}</p>
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

        $activeFilterCount = collect([request()->filled('faculty_id'), request()->filled('major_id')])->filter()->count();
    @endphp

    {{-- Search + faculty/major. Enter in the search box submits. --}}
    <form
        id="attendance-filters" method="GET" action="{{ route('admin.attendance.index', $activity) }}" class="mt-5"
        x-data="{
            filtersOpen: false,
            isDesktop: window.matchMedia('(min-width: 640px)').matches,
            init() {
                const mq = window.matchMedia('(min-width: 640px)');
                mq.addEventListener('change', (e) => { this.isDesktop = e.matches; });
            },
        }"
    >
        @if (request()->filled('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <div class="flex flex-wrap items-center gap-2">
            <label class="relative min-w-0 flex-1 sm:w-80 sm:flex-none">
                <span class="sr-only">{{ __('ค้นหาชื่อ หรือ รหัสนักศึกษา') }}</span>
                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อ หรือ รหัสนักศึกษา') }}"
                    class="h-11 w-full rounded-full border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-900 transition placeholder:text-slate-400 focus:border-brand-purple-400 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500">
            </label>

            <button type="button" @click="filtersOpen = true" aria-label="{{ __('ตัวกรอง') }}"
                class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors sm:hidden {{ $activeFilterCount > 0 ? 'bg-brand-purple-700 text-white' : 'border border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m9 12h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9-12H3.75m9 12H3.75m9-12H9m6 12v.007M12 6.75a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-6 6a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm0 0H3.75m3 0H12"/></svg>
                @if ($activeFilterCount > 0)
                    <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-[0.65rem] font-bold text-brand-purple-700 ring-2 ring-brand-purple-700">{{ $activeFilterCount }}</span>
                @endif
            </button>

            <div class="hidden flex-wrap items-center gap-2 sm:flex">
                <x-premium-select variant="chip" name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
                    placeholder="{{ __('ทุกคณะ') }}" autosubmit resets="major_id" x-bind:disabled="! isDesktop" />
                <x-premium-select variant="chip" name="major_id" :options="$majorOptions" :groups="$majorGroups" :selected="request('major_id')"
                    placeholder="{{ __('ทุกสาขา') }}" autosubmit x-bind:disabled="! isDesktop" />
                @if ($activeFilterCount > 0 || request()->filled('search') || request()->filled('status'))
                    <a href="{{ route('admin.attendance.index', $activity) }}" class="inline-flex h-9 items-center gap-1 rounded-full px-3 text-sm font-medium text-slate-500 hover:text-brand-purple-700 dark:text-slate-400 dark:hover:text-brand-purple-300">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ __('ล้างตัวกรอง') }}
                    </a>
                @endif
            </div>

            @if ($flaggedCount > 0)
                <button type="button" @click="selectAllFlagged()" class="ml-auto hidden h-9 items-center gap-1.5 rounded-full bg-amber-50 px-3.5 text-sm font-medium text-amber-800 transition-colors hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300 sm:inline-flex">
                    {{ __('เลือกที่รอตรวจทั้งหมด (:count)', ['count' => $flaggedCount]) }}
                </button>
            @endif
        </div>

        <x-filter-sheet form="attendance-filters" :clear-url="route('admin.attendance.index', array_merge(['activity' => $activity], request()->only('search')))" :groups="[
            ['name' => 'faculty_id', 'label' => __('คณะ'), 'all' => __('ทุกคณะ'), 'options' => $facultyOptions, 'selected' => request('faculty_id')],
            ['name' => 'major_id', 'label' => __('สาขา'), 'all' => __('ทุกสาขา'), 'selected' => request('major_id'), 'dependsOn' => 'faculty_id',
                'optionsByParent' => $faculties->mapWithKeys(fn ($f) => [$f->id => $f->majors->pluck('name_th', 'id')->all()])->all()],
        ]" />
    </form>

    <form method="POST" action="{{ route('admin.attendance.bulk-approve', $activity) }}" x-ref="bulkForm">
        @csrf
        <div class="ids-container"></div>
    </form>

    {{-- Only rows still waiting for a decision can be ticked; the bar below appears once something is. --}}
    <div class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800">
                    <th class="w-10 py-3 pl-4"></th>
                    <x-sortable-th field="name" :label="__('นักศึกษา')" />
                    <th class="hidden px-3 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 lg:table-cell">{{ __('คณะ / สาขา') }}</th>
                    <x-sortable-th field="checkin_time" :label="__('เวลา')" />
                    <x-sortable-th field="distance_meters" :label="__('ระยะ')" />
                    <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('สถานะ') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('จัดการ') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($attendances as $att)
                    @php
                        $name = $att->user->name_thai ?? $att->user->name;
                        $initials = mb_substr(preg_replace('/^(นาย|นางสาว|นาง)/u', '', (string) $name), 0, 2);
                        $canDecide = $att->status !== 'auto_approved';
                    @endphp
                    <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/40 {{ $att->status === 'flagged' ? 'bg-amber-50/40 dark:bg-amber-500/5' : '' }}">
                        <td class="py-3 pl-4 align-middle">
                            @if ($canDecide)
                                <input type="checkbox" class="row-checkbox rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600"
                                    value="{{ $att->id }}" data-status="{{ $att->status }}" x-model="selected" aria-label="{{ __('เลือก :name', ['name' => $name]) }}">
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3">
                                <span class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-xs font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300 sm:flex">{{ $initials }}</span>
                                <span class="min-w-0">
                                    <span class="block font-medium text-slate-900 dark:text-white">{{ $name }}</span>
                                    <span class="block font-mono text-xs text-slate-500 dark:text-slate-400">{{ $att->user->student_id }} · {{ __('ปี :year', ['year' => $att->user->current_year]) }}</span>
                                </span>
                            </div>
                        </td>
                        <td class="hidden max-w-[16rem] px-3 py-3 lg:table-cell">
                            <span class="block truncate text-slate-700 dark:text-slate-300" title="{{ $att->user->faculty?->name_th }}">{{ $att->user->faculty?->name_th ?? '-' }}</span>
                            <span class="block truncate text-xs text-slate-500 dark:text-slate-400" title="{{ $att->user->major?->name_th }}">{{ $att->user->major?->name_th ?? '-' }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 tabular-nums text-slate-600 dark:text-slate-300">{{ $att->checkin_time->format('H:i') }}</td>
                        <td class="whitespace-nowrap px-3 py-3 tabular-nums {{ str_contains((string) $att->flag_reason, 'GPS_OUT_OF_BOUNDS') ? 'font-semibold text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300' }}">
                            {{ is_null($att->distance_meters) ? '—' : $att->distance_meters.' m' }}
                        </td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $statusChip[$att->status] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDot[$att->status] }}"></span>
                                {{ $statusLabel[$att->status] }}
                            </span>
                            @if ($att->flag_reason && $att->status === 'flagged')
                                <p class="mt-1 max-w-[16rem] text-xs text-amber-800 dark:text-amber-300">{{ collect(explode(',', $att->flag_reason))->map(fn ($r) => $reasonLabel[$r] ?? $r)->join(', ') }}</p>
                            @elseif ($att->status === 'rejected' && $att->reject_reason)
                                <p class="mt-1 max-w-[16rem] text-xs text-slate-500 dark:text-slate-400">{{ $att->reject_reason }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" @click="lightboxUrl = '{{ asset('storage/'.$att->photo_path) }}'"
                                    title="{{ in_array($att->checkin_method, ['self_report', 'late_request'], true) ? __('รูปหลักฐาน') : __('รูปเซลฟี') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-brand-purple-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-brand-purple-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                </button>
                                @if ($att->student_lat !== null && $att->student_lng !== null)
                                    <a href="https://www.google.com/maps?q={{ $att->student_lat }},{{ $att->student_lng }}" target="_blank" rel="noopener" title="{{ __('แผนที่') }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-brand-purple-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-brand-purple-300">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    </a>
                                @endif
                                @if ($att->status === 'flagged')
                                    <form method="POST" action="{{ route('admin.attendance.approve', $att) }}">
                                        @csrf
                                        <button class="h-8 rounded-lg bg-brand-green-600 px-3 text-xs font-semibold text-white transition-colors hover:bg-brand-green-700">{{ __('อนุมัติ') }}</button>
                                    </form>
                                    <button type="button" @click="rejectUrl = '{{ route('admin.attendance.reject', $att) }}'; rejectName = @js($name)"
                                        class="h-8 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 transition-colors hover:border-rose-300 hover:text-rose-700 dark:border-slate-700 dark:text-slate-200">{{ __('ไม่อนุมัติ') }}</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">{{ request()->hasAny(['search', 'status', 'faculty_id', 'major_id']) ? __('ไม่พบรายการที่ตรงกับตัวกรอง') : __('ยังไม่มีผู้เช็คชื่อ') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Selection bar --}}
    <div x-show="selected.length" x-cloak x-transition.opacity
        class="sticky bottom-20 z-30 mx-auto mt-4 flex max-w-xl items-center gap-3 rounded-2xl bg-slate-900 px-4 py-3 text-white shadow-soft-lg dark:bg-slate-800 lg:bottom-4">
        <span class="text-sm">{{ __('เลือกแล้ว') }} <span class="font-semibold" x-text="selected.length"></span> {{ __('รายการ') }}</span>
        <button type="button" @click="selected = []" class="text-sm text-white/70 hover:text-white">{{ __('ยกเลิก') }}</button>
        <button type="button" @click="approveSelected()" class="ml-auto rounded-xl bg-brand-green-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-green-700">{{ __('อนุมัติที่เลือก') }}</button>
    </div>

    <!-- Reject modal -->
    <template x-teleport="body">
        <div x-show="rejectUrl" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @keydown.escape.window="rejectUrl = null">
            <form method="POST" :action="rejectUrl" @click.outside="rejectUrl = null" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-soft-lg dark:bg-slate-900">
                @csrf
                <p class="font-display text-lg text-slate-900 dark:text-white">{{ __('ไม่อนุมัติการเช็คชื่อ') }}</p>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400" x-text="rejectName"></p>
                <label class="mt-4 block text-sm font-medium text-slate-700 dark:text-slate-300" for="reject_reason">{{ __('เหตุผล (นักศึกษาจะเห็น)') }}</label>
                <textarea id="reject_reason" name="reject_reason" rows="3" required maxlength="500"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="rejectUrl = null" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('ยกเลิก') }}</button>
                    <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">{{ __('ไม่อนุมัติ') }}</button>
                </div>
            </form>
        </div>
    </template>

    <!-- Selfie lightbox -->
    <template x-teleport="body">
        <div x-show="lightboxUrl" x-cloak @click="lightboxUrl = null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4">
            <img :src="lightboxUrl" class="max-h-[80vh] max-w-full rounded-2xl shadow-soft-lg">
        </div>
    </template>

    <!-- Missing students modal -->
    <div
        x-show="showMissingModal" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
    >
        <div
            @click.outside="showMissingModal = false"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="w-full max-w-2xl rounded-[2rem] bg-slate-200 p-px shadow-soft-lg dark:bg-slate-800"
        >
            <div class="flex max-h-[85vh] flex-col rounded-[calc(2rem-1px)] bg-white dark:bg-slate-900">
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
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-sm transition-all duration-200 focus:border-brand-purple-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100"
                        >
                    </div>
                    <a href="{{ route('admin.attendance.missing-export', $activity) }}"
                        class="flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition-colors hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:text-brand-purple-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Excel
                    </a>
                </div>

                <div class="flex-1 overflow-y-auto p-2">
                    @forelse ($missingStudents as $student)
                        <div
                            x-show="missingSearch === '' || {{ Illuminate\Support\Js::from(strtolower(($student->name_thai ?? $student->name).' '.$student->student_id)) }}.includes(missingSearch.toLowerCase())"
                            class="flex items-center justify-between gap-3 rounded-xl px-3.5 py-2.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-purple-50 text-xs font-semibold text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">
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
                        <div class="flex flex-col items-center py-10 text-center">
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-green-50 text-brand-green-600 dark:bg-brand-green-500/15 dark:text-brand-green-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('ทุกคนเช็คชื่อครบแล้ว') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
