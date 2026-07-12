@extends('layouts.dashboard')

@section('content')
@php
    $tabs = ['pending' => __('รอตรวจสอบ'), 'approved' => __('อนุมัติแล้ว'), 'rejected' => __('ปฏิเสธแล้ว'), 'all' => __('ทั้งหมด')];
    $statusDot = ['pending' => 'bg-amber-500', 'approved' => 'bg-brand-green-500', 'rejected' => 'bg-red-500'];
    $statusBadge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
@endphp

<div
    class="mx-auto max-w-7xl"
    x-data="{
        showModal: false,
        rejecting: false,
        approving: false,
        rejectReason: '',
        approveComment: '',
        approveHours: null,
        selected: null,
        approveUrlTemplate: '{{ route('admin.late-checkins.approve', ['lateCheckInRequest' => '__ID__']) }}',
        rejectUrlTemplate: '{{ route('admin.late-checkins.reject', ['lateCheckInRequest' => '__ID__']) }}',
        open(item) {
            this.selected = item;
            this.showModal = true;
            this.rejecting = false;
            this.approving = false;
            this.rejectReason = '';
            this.approveComment = '';
            this.approveHours = item.credit_hours;
        },
    }"
>
    <x-brand-header :title="__('คำร้องขอเช็คชื่อย้อนหลัง')" :eyebrow="__('กองพัฒนานักศึกษา')" :back="route('admin.dashboard')" />

    <div class="mb-4 mt-4 flex flex-wrap gap-2 text-sm">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('admin.late-checkins.index', array_merge(request()->only(['search']), ['status' => $value])) }}"
                @class([
                    'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 font-medium transition-all duration-200',
                    'bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 text-white shadow-soft' => $status === $value,
                    'bg-white text-slate-500 shadow-soft ring-1 ring-slate-200 hover:-translate-y-0.5 hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-brand-purple-400' => $status !== $value,
                ])>
                {{ $label }}
                <span @class([
                    'rounded-full px-1.5 py-0.5 text-[0.68rem] font-semibold tabular-nums',
                    'bg-white/20' => $status === $value,
                    'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => $status !== $value,
                ])>{{ number_format($tabCounts[$value] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.late-checkins.index') }}" class="mb-4">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อนักศึกษา รหัสนักศึกษา หรือชื่อกิจกรรม') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-700 placeholder:text-slate-400 shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>

            <button type="submit"
                class="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-6 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:from-brand-purple-500 hover:to-brand-purple-400 hover:shadow-lg active:scale-[0.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                {{ __('ค้นหา') }}
            </button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('นักศึกษา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('คณะ/สาขา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อกิจกรรม') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชั่วโมง') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $req)
                    <tr
                        @class([
                            'group cursor-pointer border-b border-slate-100 transition-colors duration-150 last:border-0 hover:bg-brand-purple-50/50 dark:border-slate-800 dark:hover:bg-slate-800/60',
                            'bg-white dark:bg-slate-900' => $loop->even,
                            'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                        ])
                        @click="open({{ \Illuminate\Support\Js::from([
                            'id' => $req->id,
                            'title' => $req->activity->title,
                            'activity_code' => $req->activity->activity_code,
                            'activity_date' => $req->activity->start_at->format('d/m/Y H:i'),
                            'credit_hours' => $req->activity->credit_hours,
                            'hours_credited' => $req->hours_credited,
                            'reason' => $req->reason,
                            'status' => $req->status,
                            'reject_reason' => $req->reject_reason,
                            'admin_comment' => $req->admin_comment,
                            'proof_image_url' => asset('storage/'.$req->proof_image_path),
                            'student_name' => $req->user->name_thai ?? $req->user->name,
                            'student_id' => $req->user->student_id,
                            'faculty' => $req->user->faculty?->name_th,
                            'major' => $req->user->major?->name_th,
                            'year_level' => $req->user->year_level,
                        ]) }})"
                    >
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-purple-500 to-brand-purple-700 text-xs font-semibold text-white shadow-soft">
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
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 transition-colors group-hover:text-brand-purple-700 dark:text-slate-300 dark:group-hover:text-brand-purple-400">{{ $req->activity->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">
                            @if ($req->status === 'approved' && $req->hours_approved !== null && $req->hours_approved != $req->activity->credit_hours)
                                <span class="text-slate-300 line-through dark:text-slate-600">{{ $req->activity->credit_hours }}</span> <span class="font-medium text-brand-green-700 dark:text-brand-green-400">{{ $req->hours_credited }}</span>
                            @else
                                {{ $req->activity->credit_hours }}
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
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ไม่มีคำร้องในหมวดนี้') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>

    <!-- Detail modal -->
    <div
        x-show="showModal" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/70 p-4 backdrop-blur-sm"
    >
        <div
            @click.outside="showModal = false" x-show="selected"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="w-full max-w-lg rounded-[2rem] bg-gradient-to-br from-white/60 via-white/10 to-brand-purple-200/40 p-[1.5px] shadow-soft-lg dark:from-white/10 dark:via-white/5 dark:to-brand-purple-500/20"
        >
            <div class="max-h-[90vh] overflow-y-auto rounded-[calc(2rem-1.5px)] bg-white p-6 dark:bg-slate-900">
                <template x-if="selected">
                    <div>
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-purple-500 to-brand-purple-700 text-sm font-semibold text-white shadow-soft" x-text="selected.student_name.charAt(0)"></span>
                                <div>
                                    <p class="font-semibold leading-snug text-slate-900 dark:text-slate-100" x-text="selected.title"></p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500" x-text="selected.student_name + ' · ' + selected.student_id"></p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">
                                        <span x-text="[selected.faculty, selected.major].filter(Boolean).join(' · ')"></span>
                                        <template x-if="selected.year_level"><span x-text="' · ปี ' + selected.year_level"></span></template>
                                    </p>
                                </div>
                            </div>
                            <button @click="showModal = false" class="shrink-0 rounded-full p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <dl class="mb-4 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50/70 p-3.5 text-sm dark:bg-slate-800/40">
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('วันที่จัดกิจกรรม') }}</dt><dd class="font-medium text-slate-700 dark:text-slate-200" x-text="selected.activity_date"></dd></div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('ชั่วโมงกิจกรรม') }}</dt>
                                    <dd class="font-medium text-slate-700 dark:text-slate-200">
                                        <template x-if="selected.status === 'approved' && selected.hours_credited != selected.credit_hours">
                                            <span><span class="text-slate-400 line-through" x-text="selected.credit_hours"></span> <span class="font-semibold text-brand-green-700 dark:text-brand-green-400" x-text="selected.hours_credited"></span></span>
                                        </template>
                                        <template x-if="! (selected.status === 'approved' && selected.hours_credited != selected.credit_hours)">
                                            <span x-text="selected.credit_hours"></span>
                                        </template>
                                    </dd>
                                </div>
                            </div>
                            <div class="col-span-2 flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                                <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('เหตุผลที่ไม่ได้เช็คชื่อตอนนั้น') }}</dt><dd class="font-medium text-slate-700 dark:text-slate-200" x-text="selected.reason"></dd></div>
                            </div>
                        </dl>

                        <div class="mb-4 overflow-hidden rounded-2xl bg-black/5 shadow-soft dark:bg-black/20">
                            <img :src="selected.proof_image_url" class="max-h-96 w-full object-contain">
                        </div>

                        <template x-if="selected.status === 'rejected' && selected.reject_reason">
                            <div class="mb-3 flex items-start gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-xs text-red-600 dark:bg-red-500/10 dark:text-red-400">
                                <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                <span x-text="'{{ __('เหตุผลที่ปฏิเสธ:') }} ' + selected.reject_reason"></span>
                            </div>
                        </template>

                        <template x-if="selected.admin_comment">
                            <div class="mb-4 flex items-start gap-2 rounded-xl bg-brand-purple-50 px-3 py-2.5 text-xs text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                                <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                                <span x-text="'{{ __('ความเห็นกองพัฒนานักศึกษา:') }} ' + selected.admin_comment"></span>
                            </div>
                        </template>

                        <template x-if="selected.status === 'pending' && ! rejecting && ! approving">
                            <div class="flex gap-3">
                                <button @click="approving = true" type="button"
                                    class="flex-1 rounded-xl bg-gradient-to-r from-brand-green-500 to-brand-green-400 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                                    {{ __('อนุมัติ') }}
                                </button>
                                <button @click="rejecting = true" type="button"
                                    class="flex-1 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-100 hover:shadow-lg dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20">
                                    {{ __('ปฏิเสธ') }}
                                </button>
                            </div>
                        </template>

                        <template x-if="selected.status === 'pending' && approving">
                            <form method="POST" :action="approveUrlTemplate.replace('__ID__', selected.id)" class="space-y-3 rounded-2xl bg-brand-green-50/50 p-3.5 dark:bg-brand-green-500/5">
                                @csrf
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('จำนวนชั่วโมงที่จะให้เครดิต') }}</label>
                                    <input type="number" name="hours_credited" x-model.number="approveHours" min="0" max="200"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-green-500 focus:outline-none focus:ring-4 focus:ring-brand-green-500/10 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100">
                                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500" x-show="approveHours != selected.credit_hours" x-text="'{{ __('ชั่วโมงปกติของกิจกรรมนี้คือ ') }}' + selected.credit_hours + ' {{ __('ชม.') }}'"></p>
                                </div>
                                <div>
                                    <label class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                                        <span>{{ __('ความเห็น (ถ้ามี)') }}</span>
                                        <span class="font-mono text-[0.65rem] text-slate-350 dark:text-slate-600" x-text="approveComment.length + '/500'"></span>
                                    </label>
                                    <textarea
                                        name="admin_comment" x-model="approveComment" rows="2" maxlength="500"
                                        placeholder="{{ __('เช่น ตรวจสอบหลักฐานแล้วถูกต้อง') }}"
                                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                        class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-green-500 focus:outline-none focus:ring-4 focus:ring-brand-green-500/10 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500"
                                    ></textarea>
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="flex-1 rounded-xl bg-gradient-to-r from-brand-green-500 to-brand-green-400 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                                        {{ __('ยืนยันอนุมัติ') }}
                                    </button>
                                    <button @click="approving = false" type="button" class="flex-1 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-soft transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700">
                                        {{ __('ยกเลิก') }}
                                    </button>
                                </div>
                            </form>
                        </template>

                        <template x-if="selected.status === 'pending' && rejecting">
                            <form method="POST" :action="rejectUrlTemplate.replace('__ID__', selected.id)" class="space-y-3 rounded-2xl bg-red-50/50 p-3.5 dark:bg-red-500/5">
                                @csrf
                                <div>
                                    <label class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                                        <span>{{ __('เหตุผลที่ปฏิเสธ') }}</span>
                                        <span class="font-mono text-[0.65rem] text-slate-350 dark:text-slate-600" x-text="rejectReason.length + '/500'"></span>
                                    </label>
                                    <textarea
                                        name="reject_reason" x-model="rejectReason" required rows="3" maxlength="500"
                                        placeholder="{{ __('ระบุเหตุผล เช่น หลักฐานไม่ชัดเจนว่าเข้าร่วมกิจกรรมนี้จริง') }}"
                                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                        class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-red-500 focus:outline-none focus:ring-4 focus:ring-red-500/10 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500"
                                    ></textarea>
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-700 hover:shadow-lg">
                                        {{ __('ยืนยันการปฏิเสธ') }}
                                    </button>
                                    <button @click="rejecting = false" type="button" class="flex-1 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-soft transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700">
                                        {{ __('ยกเลิก') }}
                                    </button>
                                </div>
                            </form>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
