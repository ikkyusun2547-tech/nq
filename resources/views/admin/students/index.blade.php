@extends('layouts.dashboard')

@section('content')
@php
    $programLabel = ['normal' => __('ภาคปกติ'), 'special' => __('กศ.บป.')];
    $statusDot = ['active' => 'bg-brand-green-500', 'banned' => 'bg-red-500'];
    $statusBadge = [
        'active' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'banned' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
    $statusLabel = ['active' => __('ใช้งานปกติ'), 'banned' => __('ระงับการใช้งาน')];
    $graduatedBadge = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400';
    $enrollmentOptions = ['enrolled' => __('กำลังศึกษา'), 'graduated' => __('จบการศึกษาแล้ว'), 'all' => __('ทั้งหมด')];
@endphp

<div class="mx-auto max-w-[90rem]">
    <x-brand-header :title="__('ข้อมูลนักศึกษาในระบบ')" :eyebrow="__('กองพัฒนานักศึกษา')">
        <x-slot:actions>
            <span class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur">
                {{ __('ทั้งหมด :count คน', ['count' => $students->total()]) }}
            </span>
            @if ($bannedCount > 0)
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-red-500/20 px-4 py-2 text-sm font-medium text-red-100 shadow-soft ring-1 ring-red-300/30 backdrop-blur">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    {{ __('ระงับการใช้งาน :count คน', ['count' => $bannedCount]) }}
                </span>
            @endif
            <a href="{{ route('admin.students.import.create') }}"
                class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
                {{ __('นำเข้ารายชื่อนักศึกษา') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

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

        $yearOptions = collect([1, 2, 3, 4])->mapWithKeys(fn ($y) => [$y => __('ชั้นปีที่ :year', ['year' => $y])])->all();

        // Drives the mobile filter-sheet trigger's badge — see
        // admin/attendance/index.blade.php for the same pattern.
        // enrollment_status defaults to 'enrolled' server-side, so only
        // count it as "active" when explicitly set away from that default.
        $activeFilterCount = collect([
            request()->filled('faculty_id'),
            request()->filled('major_id'),
            request()->filled('year_level'),
            request()->filled('enrollment_status') && $enrollmentStatus !== 'enrolled',
        ])->filter()->count();
    @endphp

    <form
        method="GET" action="{{ route('admin.students.index') }}" class="mt-4 space-y-3"
        x-data="{
            filtersOpen: false,
            isDesktop: window.matchMedia('(min-width: 640px)').matches,
            init() {
                const mq = window.matchMedia('(min-width: 640px)');
                mq.addEventListener('change', (e) => { this.isDesktop = e.matches; });
            },
        }"
    >
        <div class="flex gap-2">
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อ หรือ รหัสนักศึกษา') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>

            <button type="submit"
                class="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:from-brand-purple-500 hover:to-brand-purple-400 hover:shadow-lg active:scale-[0.99] sm:px-6">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <span class="hidden sm:inline">{{ __('ค้นหา') }}</span>
            </button>

            {{-- Mobile: opens the filter sheet below instead of showing the
                 4 selects inline (see admin/attendance/index.blade.php,
                 same reasoning). --}}
            <button type="button" @click="filtersOpen = true" aria-label="{{ __('ตัวกรอง') }}"
                class="relative flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl shadow-soft transition-colors duration-200 sm:hidden {{ $activeFilterCount > 0 ? 'bg-brand-purple-600 text-white' : 'border border-slate-200 bg-white text-slate-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400' }}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m9 12h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9-12H3.75m9 12H3.75m9-12H9m6 12v.007M12 6.75a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-6 6a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm0 0H3.75m3 0H12"/></svg>
                @if ($activeFilterCount > 0)
                    <span class="absolute -right-1.5 -top-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-brand-green-500 text-[0.65rem] font-bold text-brand-purple-950">{{ $activeFilterCount }}</span>
                @endif
            </button>
        </div>

        {{-- Desktop/tablet: unchanged 4-column grid. --}}
        <div class="hidden sm:grid sm:grid-cols-4 sm:gap-3">
            <x-premium-select
                name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
                placeholder="{{ __('-- ทุกคณะ --') }}" autosubmit resets="major_id" x-bind:disabled="! isDesktop"
            />

            <x-premium-select
                name="major_id" :options="$majorOptions" :groups="$majorGroups" :selected="request('major_id')"
                placeholder="{{ __('-- ทุกสาขา --') }}" autosubmit x-bind:disabled="! isDesktop"
            />

            <x-premium-select
                name="year_level" :options="$yearOptions" :selected="request('year_level')"
                placeholder="{{ __('-- ทุกชั้นปี --') }}" autosubmit x-bind:disabled="! isDesktop"
            />

            <x-premium-select
                name="enrollment_status" :options="$enrollmentOptions" :selected="$enrollmentStatus"
                autosubmit :nullable="false" x-bind:disabled="! isDesktop"
            />
        </div>

        {{-- Mobile filter sheet — same 4 fields, stacked. --}}
        <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-50 sm:hidden">
            <div
                x-show="filtersOpen" x-cloak x-transition.opacity
                class="absolute inset-0 bg-slate-950/50"
                @click="filtersOpen = false"
            ></div>
            <div
                x-show="filtersOpen" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-3xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-soft-lg dark:bg-slate-900"
            >
                <div class="mx-auto mb-4 h-1.5 w-10 shrink-0 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ __('ตัวกรอง') }}</h3>
                    @if ($activeFilterCount > 0)
                        <a href="{{ route('admin.students.index', request()->only('search')) }}"
                            class="text-xs font-medium text-brand-purple-600 dark:text-brand-purple-400">
                            {{ __('ล้างตัวกรอง') }}
                        </a>
                    @endif
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ') }}</label>
                        <x-premium-select
                            name="faculty_id" :options="$facultyOptions" :selected="request('faculty_id')"
                            placeholder="{{ __('-- ทุกคณะ --') }}" autosubmit resets="major_id" x-bind:disabled="isDesktop"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สาขา') }}</label>
                        <x-premium-select
                            name="major_id" :options="$majorOptions" :groups="$majorGroups" :selected="request('major_id')"
                            placeholder="{{ __('-- ทุกสาขา --') }}" autosubmit x-bind:disabled="isDesktop"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชั้นปี') }}</label>
                        <x-premium-select
                            name="year_level" :options="$yearOptions" :selected="request('year_level')"
                            placeholder="{{ __('-- ทุกชั้นปี --') }}" autosubmit x-bind:disabled="isDesktop"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะการศึกษา') }}</label>
                        <x-premium-select
                            name="enrollment_status" :options="$enrollmentOptions" :selected="$enrollmentStatus"
                            autosubmit :nullable="false" x-bind:disabled="isDesktop"
                        />
                    </div>
                </div>

                <button type="button" @click="filtersOpen = false"
                    class="mt-5 w-full rounded-xl bg-brand-purple-600 px-4 py-3 text-sm font-semibold text-white shadow-soft transition-colors hover:bg-brand-purple-700">
                    {{ __('เสร็จสิ้น') }}
                </button>
            </div>
        </div>
    </form>

    @php $canBulkAct = auth()->user()->role === 'super_admin'; @endphp

    <form
        @if ($canBulkAct) method="POST" action="{{ route('admin.users.bulk-action') }}" @endif
        x-data="{
            selected: [],
            allIds: @js($students->pluck('id')->all()),
            graduatedMap: @js($students->mapWithKeys(fn ($s) => [$s->id => $s->isGraduated()])),
            get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length },
            get hasNotGraduatedSelected() { return this.selected.some(id => ! this.graduatedMap[id]); },
            get hasGraduatedSelected() { return this.selected.some(id => this.graduatedMap[id]); },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            confirmOpen: false,
            confirmAction: null,
            confirmMessage: '',
            confirmLabel: '',
            confirmTone: 'green',
            ask(action, message, label, tone) {
                if (this.selected.length === 0) return;
                this.confirmAction = action;
                this.confirmMessage = `${message} ({{ __(':count คน') }})`.replace(':count', this.selected.length);
                this.confirmLabel = label;
                this.confirmTone = tone;
                this.confirmOpen = true;
            },
            proceed() {
                this.$refs.bulkAction.value = this.confirmAction;
                this.confirmOpen = false;
                this.$root.submit();
            },
        }"
    >
        @if ($canBulkAct)
            @csrf
            <input type="hidden" name="bulk_action" x-ref="bulkAction">

            <div x-show="selected.length > 0" x-cloak x-transition
                class="mt-4 mb-3 flex flex-wrap items-center gap-3 rounded-2xl bg-gradient-to-r from-brand-green-50 to-brand-green-50/60 px-4 py-3 shadow-soft ring-1 ring-brand-green-100 dark:from-brand-green-500/10 dark:to-brand-green-500/5 dark:ring-brand-green-500/20">
                <span class="inline-flex items-center gap-2 text-sm font-medium text-brand-green-700 dark:text-brand-green-400">
                    <span class="flex h-6 min-w-6 items-center justify-center rounded-full bg-brand-green-600 px-1.5 text-xs font-bold text-white dark:bg-brand-green-500" x-text="selected.length"></span>
                    {{ __('รายการที่เลือก') }}
                </span>

                <span class="h-6 w-px bg-brand-green-100 dark:bg-brand-green-500/30"></span>

                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <button type="button" x-show="hasNotGraduatedSelected" x-cloak
                        @click="ask('graduate', {{ Js::from(__('ยืนยันทำเครื่องหมายจบการศึกษาสำหรับนักศึกษาที่เลือก')) }}, {{ Js::from(__('ทำเครื่องหมายจบการศึกษา')) }}, 'green')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-green-600 to-brand-green-500 px-3.5 py-2 text-xs font-semibold text-white shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg active:scale-[0.98]">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('ทำเครื่องหมายจบการศึกษา') }}
                    </button>
                    <button type="button" x-show="hasGraduatedSelected" x-cloak
                        @click="ask('ungraduate', {{ Js::from(__('ยืนยันยกเลิกสถานะจบการศึกษาสำหรับนักศึกษาที่เลือก')) }}, {{ Js::from(__('ยกเลิกสถานะจบ')) }}, 'slate')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 shadow-soft ring-1 ring-slate-200 transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-lg active:scale-[0.98] dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                        {{ __('ยกเลิกสถานะจบ') }}
                    </button>
                </div>
            </div>

            <div x-show="confirmOpen" x-cloak x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm"
                @keydown.escape.window="confirmOpen = false">
                <div
                    @click.outside="confirmOpen = false"
                    x-show="confirmOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="w-full max-w-sm rounded-[2rem] bg-gradient-to-br from-white/60 via-white/10 p-[1.5px] shadow-soft-lg dark:from-white/10 dark:via-white/5"
                    :class="confirmTone === 'green' ? 'to-brand-green-200/40 dark:to-brand-green-500/20' : 'to-slate-200/60 dark:to-slate-500/20'"
                >
                    <div class="rounded-[calc(2rem-1.5px)] bg-white p-7 text-center dark:bg-slate-900">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full ring-8"
                            :class="confirmTone === 'green' ? 'bg-brand-green-50 ring-brand-green-50/50 dark:bg-brand-green-500/10 dark:ring-brand-green-500/5' : 'bg-slate-100 ring-slate-100/50 dark:bg-slate-800 dark:ring-slate-800/50'">
                            <svg class="h-8 w-8 shrink-0" :class="confirmTone === 'green' ? 'text-brand-green-600 dark:text-brand-green-400' : 'text-slate-500 dark:text-slate-400'"
                                fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.362-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('ยืนยันการดำเนินการ') }}</h3>
                        <p class="mx-auto mt-2 max-w-[15rem] text-sm leading-relaxed text-slate-500 dark:text-slate-400" x-text="confirmMessage"></p>
                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <button type="button" @click="confirmOpen = false"
                                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition-colors hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-800">
                                {{ __('ยกเลิก') }}
                            </button>
                            <button type="button" @click="proceed()"
                                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-200 hover:shadow-lg active:scale-[0.98]"
                                :class="confirmTone === 'green' ? 'bg-gradient-to-r from-brand-green-600 to-brand-green-500' : 'bg-gradient-to-r from-slate-600 to-slate-500'">
                                <span x-text="confirmLabel"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-4 overflow-x-auto rounded-2xl glass-card shadow-soft">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                        @if ($canBulkAct)
                            <th class="w-10 whitespace-nowrap px-4 py-3">
                                <input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)"
                                    class="h-4 w-4 rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600">
                            </th>
                        @endif
                        <x-sortable-th field="name" :label="__('ชื่อ-นามสกุล')" />
                        <x-sortable-th field="student_id" :label="__('รหัสนักศึกษา')" />
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ / สาขา') }}</th>
                        <x-sortable-th field="year_level" :label="__('ชั้นปี')" />
                        <x-sortable-th field="hours" :label="__('ชั่วโมงสะสม')" />
                        <x-sortable-th field="program_type" :label="__('ภาค')" />
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                        <th class="whitespace-nowrap px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr @class([
                            'border-b border-slate-100 dark:border-slate-800 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:hover:bg-slate-800/60',
                            'bg-white dark:bg-slate-900' => $loop->even,
                            'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                        ])>
                            @if ($canBulkAct)
                                <td class="whitespace-nowrap px-4 py-3">
                                    <input type="checkbox" name="user_ids[]" value="{{ $student->id }}" x-model="selected"
                                        class="h-4 w-4 rounded border-slate-300 text-brand-purple-600 focus:ring-brand-purple-500 dark:border-slate-600">
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $student->name_thai ?? $student->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-slate-500 dark:text-slate-400">{{ $student->student_id ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                                {{ $student->faculty?->name_th ?? '-' }}
                                @if ($student->major)
                                    <span class="text-slate-300 dark:text-slate-600">·</span> {{ $student->major->name_th }}
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $student->year_level ? __('ปี :year', ['year' => $student->year_level]) : '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @php
                                    $progress = $progressByStudent[$student->id] ?? null;
                                    $hoursPct = $progress && $progress['required_hours'] > 0
                                        ? min(100, round($progress['total_hours'] / $progress['required_hours'] * 100))
                                        : 0;
                                @endphp
                                @if ($progress)
                                    <div class="min-w-[6.5rem]">
                                        <span @class([
                                            'font-medium',
                                            'text-brand-green-600 dark:text-brand-green-400' => $progress['is_cleared'],
                                            'text-slate-600 dark:text-slate-300' => ! $progress['is_cleared'],
                                        ])>{{ $progress['total_hours'] }}</span>
                                        <span class="text-slate-300 dark:text-slate-600">/ {{ $progress['required_hours'] }} {{ __('ชม.') }}</span>
                                        <div class="mt-1 h-1.5 w-12 overflow-hidden rounded-full bg-brand-purple-50 dark:bg-brand-purple-500/10">
                                            <div @class([
                                                'h-full rounded-full',
                                                'bg-brand-green-500' => $progress['is_cleared'],
                                                'bg-gradient-to-r from-brand-purple-500 to-brand-green-400' => ! $progress['is_cleared'],
                                            ]) style="width: {{ $hoursPct }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $programLabel[$student->program_type] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$student->account_status] ?? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$student->account_status] ?? 'bg-slate-400'])></span>
                                        <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$student->account_status] ?? 'bg-slate-400'])></span>
                                    </span>
                                    {{ $statusLabel[$student->account_status] ?? $student->account_status }}
                                </span>
                                @if ($student->isGraduated())
                                    <span class="{{ $graduatedBadge }}">{{ __('จบการศึกษา') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('ดูข้อมูล') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canBulkAct ? 9 : 8 }}" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ไม่พบนักศึกษาที่ตรงกับเงื่อนไข') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="mt-4">{{ $students->links() }}</div>
</div>
@endsection
