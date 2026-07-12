@extends('layouts.dashboard')

@section('content')
@php
    $tabs = ['flagged' => __('ติดธงแดง'), 'rejected' => __('ปฏิเสธแล้ว'), 'all' => __('ทั้งหมด')];
    $statusDot = ['flagged' => 'bg-red-500', 'rejected' => 'bg-slate-400'];
    $statusBadge = [
        'flagged' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'rejected' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    ];
    $statusText = ['flagged' => __('ติดธงแดง'), 'rejected' => __('ปฏิเสธแล้ว')];
    $checkinMethodLabel = [
        'realtime' => __('สแกน QR + GPS + เซลฟี'),
        'self_report' => __('แนบรูปหลักฐาน (รายงานตนเอง)'),
        'late_request' => __('เช็คชื่อย้อนหลัง'),
    ];
@endphp

<div
    class="mx-auto max-w-7xl"
    x-data="{
        showModal: false,
        rejecting: false,
        selected: null,
        approveUrlTemplate: '{{ route('admin.attendance.approve', ['attendance' => '__ID__']) }}',
        rejectUrlTemplate: '{{ route('admin.attendance.reject', ['attendance' => '__ID__']) }}',
        rejectReason: '',
        open(item) {
            this.selected = item;
            this.showModal = true;
            this.rejecting = false;
            this.rejectReason = '';
        },
    }"
>
    <x-brand-header :title="__('การเช็คชื่อติดธงแดง')" :eyebrow="__('กองพัฒนานักศึกษา')" :back="route('admin.dashboard')" />

    <div class="mb-4 mt-4 flex flex-wrap gap-2 text-sm">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('admin.attendance.flagged', array_merge(request()->only(['search']), ['status' => $value])) }}"
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

    <form method="GET" action="{{ route('admin.attendance.flagged') }}" class="mb-4">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <input
                    type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ค้นหาชื่อนักศึกษาหรือรหัสนักศึกษา') }}"
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
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อกิจกรรม') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('เวลาเช็คชื่อ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('เหตุผลที่ต้องตรวจสอบ') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $att)
                    <tr
                        @class([
                            'group cursor-pointer border-b border-slate-100 transition-colors duration-150 last:border-0 hover:bg-brand-purple-50/50 dark:border-slate-800 dark:hover:bg-slate-800/60',
                            'bg-white dark:bg-slate-900' => $loop->even,
                            'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                        ])
                        @click="open({{ \Illuminate\Support\Js::from([
                            'id' => $att->id,
                            'title' => $att->activity->title,
                            'activity_code' => $att->activity->activity_code,
                            'checkin_time' => $att->checkin_time->translatedFormat('d M Y H:i'),
                            'checkin_method' => $checkinMethodLabel[$att->checkin_method] ?? $att->checkin_method,
                            'distance_meters' => $att->distance_meters,
                            'credit_hours' => $att->credited_hours ?? $att->activity->credit_hours,
                            'status' => $att->status,
                            'flag_reason' => $att->flagReasonLabel(),
                            'reject_reason' => $att->reject_reason,
                            'photo_url' => asset('storage/'.$att->photo_path),
                            'student_name' => $att->user->name_thai ?? $att->user->name,
                            'student_id' => $att->user->student_id,
                            'faculty' => $att->user->faculty?->name_th,
                            'major' => $att->user->major?->name_th,
                            'year_level' => $att->user->year_level,
                        ]) }})"
                    >
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-purple-500 to-brand-purple-700 text-xs font-semibold text-white shadow-soft">
                                    {{ mb_substr($att->user->name_thai ?? $att->user->name, 0, 1) }}
                                </span>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-100">{{ $att->user->name_thai ?? $att->user->name }}</p>
                                    <p class="font-mono text-xs text-slate-400 dark:text-slate-500">{{ $att->user->student_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 transition-colors group-hover:text-brand-purple-700 dark:text-slate-300 dark:group-hover:text-brand-purple-400">{{ $att->activity->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $att->checkin_time->translatedFormat('d M Y H:i') }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-slate-500 dark:text-slate-400">{{ $att->flagReasonLabel() ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadge[$att->status] ?? '' }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span @class(['absolute inline-flex h-full w-full animate-ping rounded-full opacity-60', $statusDot[$att->status] ?? 'bg-slate-400'])></span>
                                    <span @class(['relative inline-flex h-1.5 w-1.5 rounded-full', $statusDot[$att->status] ?? 'bg-slate-400'])></span>
                                </span>
                                {{ $statusText[$att->status] ?? $att->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ไม่มีรายการในหมวดนี้') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $attendances->links() }}</div>

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
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('เวลาเช็คชื่อ') }}</dt><dd class="font-medium text-slate-700 dark:text-slate-200" x-text="selected.checkin_time"></dd></div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('วิธีเช็คชื่อ') }}</dt><dd class="font-medium text-slate-700 dark:text-slate-200" x-text="selected.checkin_method"></dd></div>
                            </div>
                            <div class="flex items-start gap-2" x-show="selected.distance_meters !== null">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('ระยะห่างจากจุดจัดกิจกรรม') }}</dt><dd class="font-medium text-slate-700 dark:text-slate-200" x-text="selected.distance_meters + ' ม.'"></dd></div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20V10M12 20V4M20 20V14"/></svg>
                                <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('ชั่วโมงกิจกรรม') }}</dt><dd class="font-medium text-slate-700 dark:text-slate-200" x-text="selected.credit_hours"></dd></div>
                            </div>
                            <div class="col-span-2 flex items-start gap-2" x-show="selected.flag_reason">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('เหตุผลที่ต้องตรวจสอบ') }}</dt><dd class="font-medium text-slate-700 dark:text-slate-200" x-text="selected.flag_reason"></dd></div>
                            </div>
                        </dl>

                        <div class="mb-4 overflow-hidden rounded-2xl bg-black/5 shadow-soft dark:bg-black/20">
                            <img :src="selected.photo_url" class="max-h-96 w-full object-contain">
                        </div>

                        <template x-if="selected.status === 'rejected' && selected.reject_reason">
                            <div class="mb-4 flex items-start gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-xs text-red-600 dark:bg-red-500/10 dark:text-red-400">
                                <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                <span x-text="'{{ __('เหตุผลที่ปฏิเสธ:') }} ' + selected.reject_reason"></span>
                            </div>
                        </template>

                        <template x-if="selected.status === 'flagged' && ! rejecting">
                            <div class="flex gap-3">
                                <form method="POST" :action="approveUrlTemplate.replace('__ID__', selected.id)" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                        class="w-full rounded-xl bg-gradient-to-r from-brand-green-500 to-brand-green-400 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                                        {{ __('อนุมัติ') }}
                                    </button>
                                </form>
                                <button @click="rejecting = true" type="button"
                                    class="flex-1 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-100 hover:shadow-lg dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20">
                                    {{ __('ปฏิเสธ') }}
                                </button>
                            </div>
                        </template>

                        <template x-if="selected.status === 'flagged' && rejecting">
                            <form method="POST" :action="rejectUrlTemplate.replace('__ID__', selected.id)" class="space-y-3 rounded-2xl bg-red-50/50 p-3.5 dark:bg-red-500/5">
                                @csrf
                                <div>
                                    <label class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                                        <span>{{ __('เหตุผลที่ปฏิเสธ') }}</span>
                                        <span class="font-mono text-[0.65rem] text-slate-350 dark:text-slate-600" x-text="rejectReason.length + '/500'"></span>
                                    </label>
                                    <textarea
                                        name="reject_reason" x-model="rejectReason" required rows="3" maxlength="500"
                                        placeholder="{{ __('ระบุเหตุผล เช่น ภาพเซลฟีไม่ตรงกับตัวตนในระบบ') }}"
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
