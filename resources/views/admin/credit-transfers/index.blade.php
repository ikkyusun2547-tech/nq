@extends('layouts.dashboard')

@section('content')
@php
    // Single source: App\Models\CreditTransferPosition (admin-configurable
    // via admin/credit-transfer-positions) — also reused by
    // admin/students/show.blade.php and the student dashboards.
    $positionLabels = collect(\App\Models\CreditTransferPosition::labelsMap())->map(fn ($label) => __($label))->all();
    $tabs = ['pending' => __('รอตรวจสอบ'), 'approved' => __('อนุมัติแล้ว'), 'rejected' => __('ปฏิเสธแล้ว'), 'all' => __('ทั้งหมด')];
    $statusDot = ['pending' => 'bg-amber-500', 'approved' => 'bg-brand-green-500', 'rejected' => 'bg-red-500'];
    $statusBadge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
@endphp

<div
    class="mx-auto max-w-[90rem]"
    x-data="{
        showModal: false,
        rejecting: false,
        approving: false,
        revoking: false,
        rejectReason: '',
        revokeReason: '',
        approveHours: null,
        approveComment: '',
        selected: null,
        approveUrlTemplate: '{{ route('admin.credit-transfers.approve', ['creditTransferRequest' => '__ID__']) }}',
        rejectUrlTemplate: '{{ route('admin.credit-transfers.reject', ['creditTransferRequest' => '__ID__']) }}',
        revokeUrlTemplate: '{{ route('admin.credit-transfers.revoke', ['creditTransferRequest' => '__ID__']) }}',
        open(item) {
            this.selected = item;
            this.showModal = true;
            this.rejecting = false;
            this.approving = false;
            this.revoking = false;
            this.rejectReason = '';
            this.revokeReason = '';
            this.approveHours = item.hours_requested;
            this.approveComment = '';
        },
    }"
>
    <x-brand-header :title="__('คำร้องเทียบโอนชั่วโมงจากตำแหน่ง')" :eyebrow="__('กองพัฒนานักศึกษา')" />

    {{-- Horizontally scrollable on mobile instead of wrapping onto several
         lines (same pattern as admin/activities/index.blade.php). --}}
    <div class="mb-4 mt-4 flex snap-x gap-2 overflow-x-auto pb-1 text-sm sm:flex-wrap sm:overflow-visible sm:pb-0">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('admin.credit-transfers.index', array_merge(request()->only(['search', 'position']), ['status' => $value])) }}"
                @class([
                    'inline-flex h-9 shrink-0 snap-start items-center gap-1.5 rounded-full border px-3.5 transition-colors',
                    'border-brand-purple-200 bg-brand-purple-50 font-semibold text-brand-purple-800 dark:border-brand-purple-500/30 dark:bg-brand-purple-500/15 dark:text-brand-purple-200' => $status === $value,
                    'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800' => $status !== $value,
                ])>
                {{ $label }}
                <span @class([
                    'rounded-full px-1.5 py-0.5 text-[0.68rem] font-semibold tabular-nums',
                    'bg-brand-purple-100 text-brand-purple-800 dark:bg-brand-purple-500/25 dark:text-brand-purple-100' => $status === $value,
                    'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => $status !== $value,
                ])>{{ number_format($tabCounts[$value] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.credit-transfers.index') }}" class="mb-4 space-y-3">
        <input type="hidden" name="status" value="{{ $status }}">

        {{-- Search+button share a row at every width (was stacking into
             separate rows on phones), with the position filter wrapping to
             its own line on mobile via `order` — but sitting back inline
             between them on desktop (sm:order-2), unchanged from before. --}}
        <div class="flex flex-wrap gap-2">
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อนักศึกษา หรือรหัสนักศึกษา') }}"
                    class="h-11 w-full rounded-full border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-900 transition placeholder:text-slate-400 focus:border-brand-purple-400 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500"
                >
            </div>

            <button type="submit" class="order-2 h-11 flex shrink-0 items-center justify-center gap-2 rounded-full bg-brand-purple-700 px-4 text-sm font-semibold text-white transition-all duration-300 active:scale-[0.99] sm:order-3 sm:px-6 hover:bg-brand-purple-800">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <span class="hidden sm:inline">{{ __('ค้นหา') }}</span>
            </button>

            <div class="order-3 sm:order-2">
                <x-premium-select
                    variant="chip" name="position" :options="$positionLabels" :selected="request('position')"
                    placeholder="{{ __('ทุกตำแหน่ง') }}" autosubmit
                />
            </div>
        </div>
    </form>

    <div class="overflow-x-auto rounded-3xl glass-card">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800">
                    <x-sortable-th field="name" :label="__('นักศึกษา')" />
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ/สาขา') }}</th>
                    <x-sortable-th field="position" :label="__('ตำแหน่ง')" />
                    <x-sortable-th field="academic_year" :label="__('ปีการศึกษา')" />
                    <x-sortable-th field="hours_requested" :label="__('ชั่วโมง')" />
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $req)
                    <tr
                        @class([
                            'group cursor-pointer border-b border-slate-100 transition-colors duration-150 last:border-0 hover:bg-brand-purple-50/50 dark:border-slate-800 dark:hover:bg-slate-800/60',
                        ])
                        @click="open({{ \Illuminate\Support\Js::from([
                            'id' => $req->id,
                            'position' => $positionLabels[$req->position],
                            'academic_year' => $req->academic_year,
                            'submitted_at' => $req->created_at->format('d/m/Y H:i'),
                            'hours_requested' => $req->hours_requested,
                            'hours_credited' => $req->hours_credited,
                            'status' => $req->status,
                            'reject_reason' => $req->reject_reason,
                            'admin_comment' => $req->admin_comment,
                            'proof_image_url' => asset('storage/'.$req->proof_image_path),
                            'proof_is_pdf' => str_ends_with(strtolower($req->proof_image_path), '.pdf'),
                            'student_name' => $req->user->name_thai ?? $req->user->name,
                            'student_id' => $req->user->student_id,
                            'faculty' => $req->user->faculty?->name_th,
                            'major' => $req->user->major?->name_th,
                            'year_level' => $req->user->year_level,
                        ]) }})"
                    >
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-purple-700 text-xs font-semibold text-white shadow-soft">
                                    {{ mb_substr($req->user->name_thai ?? $req->user->name, 0, 1) }}
                                </span>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-100">{{ $req->user->name_thai ?? $req->user->name }}</p>
                                    <p class="font-mono text-xs text-slate-400 dark:text-slate-500">{{ $req->user->student_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            {{ $req->user->faculty?->name_th ?? '-' }}
                            @if ($req->user->major)
                                <span class="text-slate-300 dark:text-slate-600">·</span> {{ $req->user->major->name_th }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 transition-colors group-hover:text-brand-purple-700 dark:text-slate-300 dark:group-hover:text-brand-purple-400">{{ $positionLabels[$req->position] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $req->academic_year }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            @if ($req->status === 'approved' && $req->hours_approved !== null && $req->hours_approved != $req->hours_requested)
                                <span class="text-slate-300 line-through dark:text-slate-600">{{ $req->hours_requested }}</span> <span class="font-medium text-brand-green-700 dark:text-brand-green-400">{{ $req->hours_credited }}</span>
                            @else
                                {{ $req->hours_requested }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$req->status] }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$req->status]])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$req->status]])></span>
                                </span>
                                {{ ['pending' => __('รอตรวจสอบ'), 'approved' => __('อนุมัติแล้ว'), 'rejected' => __('ปฏิเสธแล้ว')][$req->status] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ไม่มีคำร้องในหมวดนี้') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>

    {{-- Detail modal: a bottom sheet on phones, a centred card from sm up.
         Header and actions stay put while the proof scrolls between them. --}}
    <div
        x-show="showModal" x-cloak @keydown.escape.window="showModal = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/55 backdrop-blur-sm sm:items-center sm:p-4"
    >
        <div
            @click.outside="showModal = false" x-show="selected" role="dialog" aria-modal="true"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-2 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            class="flex max-h-[92dvh] w-full max-w-xl flex-col overflow-hidden rounded-t-[2rem] bg-white shadow-soft-lg ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:max-h-[90dvh] sm:rounded-[2rem]"
        >
            <template x-if="selected">
                <div class="flex min-h-0 flex-1 flex-col">
                    {{-- Header --}}
                    <div class="relative shrink-0 border-b border-slate-100 bg-gradient-to-br from-brand-purple-50 via-white to-white px-5 pb-4 pt-3 dark:border-slate-800 dark:from-brand-purple-500/10 dark:via-slate-900 dark:to-slate-900 sm:px-6 sm:pt-5">
                        <span class="mx-auto mb-3 block h-1 w-10 rounded-full bg-slate-300 dark:bg-slate-700 sm:hidden" aria-hidden="true"></span>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[0.7rem] font-semibold uppercase tracking-wider text-brand-purple-600 dark:text-brand-purple-300">{{ __('เทียบโอนชั่วโมงจากตำแหน่ง') }}</span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="{
                                            'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300': selected.status === 'pending',
                                            'bg-brand-green-100 text-brand-green-800 dark:bg-brand-green-500/15 dark:text-brand-green-300': selected.status === 'approved',
                                            'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300': selected.status === 'rejected',
                                        }">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                            :class="{ 'bg-amber-500': selected.status === 'pending', 'bg-brand-green-500': selected.status === 'approved', 'bg-red-500': selected.status === 'rejected' }"></span>
                                        <span x-text="{ pending: '{{ __('รอตรวจสอบ') }}', approved: '{{ __('อนุมัติแล้ว') }}', rejected: '{{ __('ปฏิเสธแล้ว') }}' }[selected.status]"></span>
                                    </span>
                                </div>
                                <h2 class="mt-1.5 font-display text-xl leading-snug text-slate-900 dark:text-white" x-text="selected.position"></h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="'{{ __('ยื่นเมื่อ') }} ' + selected.submitted_at"></p>
                            </div>
                            <button type="button" @click="showModal = false" aria-label="{{ __('ปิด') }}"
                                class="-mr-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Student --}}
                        <div class="mt-4 flex items-center gap-3 rounded-2xl bg-white/80 p-3 ring-1 ring-slate-200/70 dark:bg-slate-800/60 dark:ring-slate-700/60">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-purple-700 text-sm font-semibold text-white" x-text="selected.student_name.charAt(0)"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white" x-text="selected.student_name"></p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                    <span class="font-mono" x-text="selected.student_id"></span>
                                    <span x-text="[selected.faculty, selected.major].filter(Boolean).map(v => ' · ' + v).join('')"></span>
                                    <template x-if="selected.year_level"><span x-text="' · {{ __('ปี') }} ' + selected.year_level"></span></template>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4 sm:px-6">
                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="rounded-2xl bg-brand-purple-50 p-3.5 dark:bg-brand-purple-500/10">
                                <p class="text-xs font-medium text-brand-purple-700/80 dark:text-brand-purple-300/80">{{ __('ชั่วโมงตามตำแหน่ง') }}</p>
                                <p class="mt-1 flex items-baseline gap-1.5 font-display text-3xl leading-none text-brand-purple-800 dark:text-brand-purple-200">
                                    <template x-if="selected.status === 'approved' && selected.hours_credited != selected.hours_requested">
                                        <span class="flex items-baseline gap-1.5">
                                            <span class="text-lg text-slate-400 line-through" x-text="selected.hours_requested"></span>
                                            <span class="text-brand-green-700 dark:text-brand-green-400" x-text="selected.hours_credited"></span>
                                        </span>
                                    </template>
                                    <template x-if="! (selected.status === 'approved' && selected.hours_credited != selected.hours_requested)">
                                        <span x-text="selected.hours_requested"></span>
                                    </template>
                                    <span class="font-sans text-sm font-medium text-brand-purple-700/70 dark:text-brand-purple-300/70">{{ __('ชม.') }}</span>
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/60">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ปีการศึกษาที่ดำรงตำแหน่ง') }}</p>
                                <p class="mt-1 font-display text-3xl leading-none text-slate-900 dark:text-white" x-text="selected.academic_year"></p>
                            </div>
                        </div>

                        {{-- Proof --}}
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('หลักฐาน') }}</p>
                                <a :href="selected.proof_image_url" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold text-brand-purple-700 transition-colors hover:bg-brand-purple-50 dark:text-brand-purple-300 dark:hover:bg-brand-purple-500/15">
                                    {{ __('เปิดเต็มจอ') }}
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                </a>
                            </div>
                            <template x-if="!selected.proof_is_pdf">
                                <a :href="selected.proof_image_url" target="_blank" rel="noopener"
                                    class="group block overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200/80 dark:bg-slate-800 dark:ring-slate-700">
                                    <img :src="selected.proof_image_url" alt="{{ __('หลักฐาน') }}" class="mx-auto max-h-80 w-full object-contain transition-transform duration-300 group-hover:scale-[1.02]">
                                </a>
                            </template>
                            <template x-if="selected.proof_is_pdf">
                                <a :href="selected.proof_image_url" target="_blank" rel="noopener"
                                    class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-4 ring-1 ring-slate-200/80 transition-colors hover:bg-brand-purple-50 dark:bg-slate-800/60 dark:ring-slate-700 dark:hover:bg-brand-purple-500/10">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-100 text-xs font-bold text-red-600 dark:bg-red-500/15 dark:text-red-300">PDF</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('เปิดไฟล์ PDF หลักฐาน') }}</span>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ __('เปิดในแท็บใหม่') }}</span>
                                    </span>
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </a>
                            </template>
                        </div>

                        <template x-if="selected.status === 'rejected' && selected.reject_reason">
                            <div class="flex items-start gap-2.5 rounded-2xl bg-red-50 px-3.5 py-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                <div><p class="text-xs font-semibold">{{ __('เหตุผลที่ปฏิเสธ') }}</p><p class="mt-0.5" x-text="selected.reject_reason"></p></div>
                            </div>
                        </template>

                        <template x-if="selected.admin_comment">
                            <div class="flex items-start gap-2.5 rounded-2xl bg-brand-purple-50 px-3.5 py-3 text-sm text-brand-purple-800 dark:bg-brand-purple-500/10 dark:text-brand-purple-200">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                                <div><p class="text-xs font-semibold">{{ __('ความเห็นกองพัฒนานักศึกษา') }}</p><p class="mt-0.5" x-text="selected.admin_comment"></p></div>
                            </div>
                        </template>
                    </div>

                    {{-- Actions: review a pending request; super admins can also revoke an approval --}}
                    <template x-if="selected.status === 'pending'{{ auth()->user()->role === 'super_admin' ? " || selected.status === 'approved'" : '' }}">
                        <div class="shrink-0 border-t border-slate-100 bg-white px-5 pb-[max(1rem,env(safe-area-inset-bottom))] pt-3.5 dark:border-slate-800 dark:bg-slate-900 sm:px-6">
                            <template x-if="selected.status === 'pending' && ! rejecting && ! approving">
                                <div class="flex gap-2.5">
                                    <button @click="rejecting = true" type="button"
                                        class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-full border border-red-200 bg-white text-sm font-semibold text-red-600 transition-colors hover:bg-red-50 dark:border-red-500/30 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-500/10">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        {{ __('ปฏิเสธ') }}
                                    </button>
                                    <button @click="approving = true" type="button"
                                        class="inline-flex h-12 flex-[1.4] items-center justify-center gap-2 rounded-full bg-brand-green-600 text-sm font-semibold text-white shadow-soft transition-colors hover:bg-brand-green-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        {{ __('อนุมัติ') }}
                                    </button>
                                </div>
                            </template>

                            <template x-if="approving">
                                <form method="POST" :action="approveUrlTemplate.replace('__ID__', selected.id)" class="space-y-3">
                                    @csrf
                                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-brand-green-50 p-2 pl-4 dark:bg-brand-green-500/10">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-brand-green-900 dark:text-brand-green-200">{{ __('ชั่วโมงที่จะให้เครดิต') }}</p>
                                            <p class="text-xs text-brand-green-800/70 dark:text-brand-green-300/70" x-show="approveHours != selected.hours_requested" x-text="'{{ __('ตามตำแหน่งคือ ') }}' + selected.hours_requested + ' {{ __('ชม.') }}'"></p>
                                        </div>
                                        <div class="flex shrink-0 items-center rounded-full bg-white p-1 shadow-sm ring-1 ring-brand-green-200 dark:bg-slate-900 dark:ring-brand-green-500/30">
                                            <button type="button" @click="approveHours = Math.max(0, (approveHours || 0) - 1)" aria-label="{{ __('ลด') }}"
                                                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">−</button>
                                            <input type="number" name="hours_approved" x-model.number="approveHours" min="0" max="200" aria-label="{{ __('จำนวนชั่วโมงที่จะให้เครดิต') }}"
                                                class="w-12 border-0 bg-transparent p-0 text-center text-base font-semibold tabular-nums text-slate-900 focus:ring-0 dark:text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                            <button type="button" @click="approveHours = Math.min(200, (approveHours || 0) + 1)" aria-label="{{ __('เพิ่ม') }}"
                                                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">+</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                                            <span>{{ __('ความเห็น (ถ้ามี)') }}</span>
                                            <span class="font-mono text-[0.65rem] text-slate-400 dark:text-slate-600" x-text="approveComment.length + '/500'"></span>
                                        </label>
                                        <textarea
                                            name="admin_comment" x-model="approveComment" rows="2" maxlength="500"
                                            placeholder="{{ __('เช่น ตรวจสอบหลักฐานคำสั่งแต่งตั้งแล้ว') }}"
                                            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                            class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm transition-all duration-200 focus:border-brand-green-500 focus:outline-none focus:ring-4 focus:ring-brand-green-500/10 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500"
                                        ></textarea>
                                    </div>
                                    <div class="flex gap-2.5">
                                        <button @click="approving = false" type="button" class="h-12 flex-1 rounded-full bg-slate-100 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                            {{ __('ยกเลิก') }}
                                        </button>
                                        <button type="submit" class="h-12 flex-[1.4] rounded-full bg-brand-green-600 text-sm font-semibold text-white shadow-soft transition-colors hover:bg-brand-green-700">
                                            {{ __('ยืนยันอนุมัติ') }}
                                        </button>
                                    </div>
                                </form>
                            </template>

                            <template x-if="rejecting">
                                <form method="POST" :action="rejectUrlTemplate.replace('__ID__', selected.id)" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                                            <span>{{ __('เหตุผลที่ปฏิเสธ') }} <span class="text-red-500">*</span></span>
                                            <span class="font-mono text-[0.65rem] text-slate-400 dark:text-slate-600" x-text="rejectReason.length + '/500'"></span>
                                        </label>
                                        <textarea
                                            name="reject_reason" x-model="rejectReason" required rows="3" maxlength="500" x-init="$nextTick(() => $el.focus())"
                                            placeholder="{{ __('ระบุเหตุผล เช่น ไม่พบหลักฐานคำสั่งแต่งตั้งที่ชัดเจน') }}"
                                            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                            class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm transition-all duration-200 focus:border-red-500 focus:outline-none focus:ring-4 focus:ring-red-500/10 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500"
                                        ></textarea>
                                    </div>
                                    <div class="flex gap-2.5">
                                        <button @click="rejecting = false" type="button" class="h-12 flex-1 rounded-full bg-slate-100 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                            {{ __('ยกเลิก') }}
                                        </button>
                                        <button type="submit" class="h-12 flex-[1.4] rounded-full bg-red-600 text-sm font-semibold text-white shadow-soft transition-colors hover:bg-red-700">
                                            {{ __('ยืนยันการปฏิเสธ') }}
                                        </button>
                                    </div>
                                </form>
                            </template>

                            @if (auth()->user()->role === 'super_admin')
                                <template x-if="selected.status === 'approved' && ! revoking">
                                    <button @click="revoking = true" type="button"
                                        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full border border-red-200 bg-white text-sm font-semibold text-red-600 transition-colors hover:bg-red-50 dark:border-red-500/30 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-500/10">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                        {{ __('ยกเลิกการอนุมัติ') }}
                                    </button>
                                </template>

                                <template x-if="selected.status === 'approved' && revoking">
                                    <form method="POST" :action="revokeUrlTemplate.replace('__ID__', selected.id)" class="space-y-3">
                                        @csrf
                                        <div>
                                            <label class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                                                <span>{{ __('เหตุผลที่ยกเลิกการอนุมัติ') }} <span class="text-red-500">*</span></span>
                                                <span class="font-mono text-[0.65rem] text-slate-400 dark:text-slate-600" x-text="revokeReason.length + '/500'"></span>
                                            </label>
                                            <textarea
                                                name="reject_reason" x-model="revokeReason" required rows="3" maxlength="500" x-init="$nextTick(() => $el.focus())"
                                                placeholder="{{ __('เช่น อนุมัติผิดตำแหน่ง นักศึกษาไม่ได้ดำรงตำแหน่งนี้จริง') }}"
                                                @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                                class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm transition-all duration-200 focus:border-red-500 focus:outline-none focus:ring-4 focus:ring-red-500/10 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500"
                                            ></textarea>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('ชั่วโมงที่เคยให้เครดิตจะถูกตัดออกจากยอดสะสมของนักศึกษาทันที') }}</p>
                                        </div>
                                        <div class="flex gap-2.5">
                                            <button @click="revoking = false" type="button" class="h-12 flex-1 rounded-full bg-slate-100 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                                {{ __('ยกเลิก') }}
                                            </button>
                                            <button type="submit" class="h-12 flex-[1.4] rounded-full bg-red-600 text-sm font-semibold text-white shadow-soft transition-colors hover:bg-red-700">
                                                {{ __('ยืนยันยกเลิกการอนุมัติ') }}
                                            </button>
                                        </div>
                                    </form>
                                </template>
                            @endif
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
