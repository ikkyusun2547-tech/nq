@extends('layouts.dashboard')

@section('content')
@php
    $categoryLabels = [
        'culture' => __('ทำนุบำรุงศิลปวัฒนธรรม'),
        'academic' => __('วิชาการ'),
        'sports' => __('กีฬาและส่งเสริมสุขภาพ'),
        'volunteer' => __('จิตอาสา/บำเพ็ญประโยชน์'),
        'ethics' => __('คุณธรรมจริยธรรม'),
    ];
    $tabs = ['pending' => __('รอตรวจสอบ'), 'approved' => __('อนุมัติแล้ว'), 'rejected' => __('ปฏิเสธแล้ว'), 'all' => __('ทั้งหมด')];
    $statusDot = ['pending' => 'bg-amber-500', 'approved' => 'bg-brand-green-500', 'rejected' => 'bg-red-500'];
    $statusBadge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'approved' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ];
@endphp

<div
    class="mx-auto max-w-5xl"
    x-data="{
        showModal: false,
        rejecting: false,
        rejectReason: '',
        selected: null,
        approveUrlTemplate: '{{ route('admin.external-activities.approve', ['externalActivityRequest' => '__ID__']) }}',
        rejectUrlTemplate: '{{ route('admin.external-activities.reject', ['externalActivityRequest' => '__ID__']) }}',
        open(item) { this.selected = item; this.showModal = true; this.rejecting = false; this.rejectReason = ''; },
    }"
>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('กองพัฒนานักศึกษา') }}</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ __('คำร้องกิจกรรมภายนอก') }}</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}"
            class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
            &larr; {{ __('กลับแดชบอร์ด') }}
        </a>
    </div>

    <div class="mb-4 mt-4 flex flex-wrap gap-2 text-sm">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('admin.external-activities.index', ['status' => $value]) }}"
                @class([
                    'rounded-full px-3.5 py-1.5 font-medium transition-all duration-200',
                    'bg-brand-purple-600 text-white shadow-soft' => $status === $value,
                    'bg-white text-slate-500 shadow-soft ring-1 ring-slate-200 hover:text-brand-purple-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-brand-purple-400' => $status !== $value,
                ])>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-2xl glass-card shadow-soft">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-brand-purple-100 dark:border-brand-purple-500/20">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('นักศึกษา') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อกิจกรรม') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('หมวดหมู่') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชั่วโมง') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('สถานะ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $req)
                    <tr
                        @class([
                            'cursor-pointer border-b border-slate-100 dark:border-slate-800 transition-colors last:border-0 hover:bg-brand-purple-50/40 dark:hover:bg-slate-800/60',
                            'bg-white dark:bg-slate-900' => $loop->even,
                            'bg-slate-50/50 dark:bg-slate-800/40' => $loop->odd,
                        ])
                        @click="open({{ \Illuminate\Support\Js::from([
                            'id' => $req->id,
                            'title' => $req->title,
                            'organization' => $req->organization,
                            'activity_date' => $req->activity_date->format('d/m/Y'),
                            'category' => $categoryLabels[$req->activity_category],
                            'hours_requested' => $req->hours_requested,
                            'status' => $req->status,
                            'reject_reason' => $req->reject_reason,
                            'proof_image_url' => asset('storage/'.$req->proof_image_path),
                            'student_name' => $req->user->name_thai ?? $req->user->name,
                            'student_id' => $req->user->student_id,
                        ]) }})"
                    >
                        <td class="whitespace-nowrap px-4 py-3">
                            <p class="font-medium text-slate-900 dark:text-slate-100">{{ $req->user->name_thai ?? $req->user->name }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ $req->user->student_id }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-300">{{ $req->title }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $categoryLabels[$req->activity_category] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $req->hours_requested }}</td>
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
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-brand-purple-950/60 p-4 backdrop-blur-sm">
        <div @click.outside="showModal = false" class="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-soft-lg" x-show="selected">
            <template x-if="selected">
                <div>
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100" x-text="selected.title"></p>
                            <p class="text-xs text-slate-400 dark:text-slate-500" x-text="selected.student_name + ' (' + selected.student_id + ')'"></p>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-400">&times;</button>
                    </div>

                    <dl class="mb-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('หน่วยงานผู้จัด') }}</dt><dd x-text="selected.organization"></dd></div>
                        <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('วันที่จัดกิจกรรม') }}</dt><dd x-text="selected.activity_date"></dd></div>
                        <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('หมวดหมู่') }}</dt><dd x-text="selected.category"></dd></div>
                        <div><dt class="text-xs text-slate-400 dark:text-slate-500">{{ __('ชั่วโมงที่ขอเทียบ') }}</dt><dd x-text="selected.hours_requested"></dd></div>
                    </dl>

                    <img :src="selected.proof_image_url" class="mb-4 max-h-96 w-full rounded-2xl object-contain shadow-soft">

                    <template x-if="selected.status === 'rejected' && selected.reject_reason">
                        <p class="mb-4 rounded-xl bg-red-50 px-3 py-2 text-xs text-red-600 dark:bg-red-500/10 dark:text-red-400" x-text="'{{ __('เหตุผลที่ปฏิเสธ:') }} ' + selected.reject_reason"></p>
                    </template>

                    <template x-if="selected.status === 'pending' && ! rejecting">
                        <div class="flex gap-3">
                            <form method="POST" :action="approveUrlTemplate.replace('__ID__', selected.id)" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full rounded-xl bg-brand-green-500 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                                    {{ __('อนุมัติ') }}
                                </button>
                            </form>
                            <button @click="rejecting = true" type="button"
                                class="flex-1 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-100 hover:shadow-lg dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20">
                                {{ __('ปฏิเสธ') }}
                            </button>
                        </div>
                    </template>

                    <template x-if="selected.status === 'pending' && rejecting">
                        <form method="POST" :action="rejectUrlTemplate.replace('__ID__', selected.id)" class="space-y-3">
                            @csrf
                            <textarea name="reject_reason" x-model="rejectReason" required rows="3" placeholder="{{ __('ระบุเหตุผล เช่น รูปเกียรติบัตรไม่ชัดเจน') }}"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 text-sm shadow-soft transition-all duration-200 focus:border-red-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-red-500/10 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"></textarea>
                            <div class="flex gap-3">
                                <button type="submit" class="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-700 hover:shadow-lg">
                                    {{ __('ยืนยันการปฏิเสธ') }}
                                </button>
                                <button @click="rejecting = false" type="button" class="flex-1 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700">
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
@endsection
