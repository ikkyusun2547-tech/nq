@extends('layouts.app')

@section('content')
@php
    $categoryLabels = [
        'culture' => 'ทำนุบำรุงศิลปวัฒนธรรม',
        'academic' => 'วิชาการ',
        'sports' => 'กีฬาและส่งเสริมสุขภาพ',
        'volunteer' => 'จิตอาสา/บำเพ็ญประโยชน์',
        'ethics' => 'คุณธรรมจริยธรรม',
    ];
    $tabs = ['pending' => 'รอตรวจสอบ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ปฏิเสธแล้ว', 'all' => 'ทั้งหมด'];
@endphp

<div
    class="mx-auto max-w-5xl px-4 py-8"
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
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">คำร้องกิจกรรมภายนอก</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600">&larr; กลับแดชบอร์ด</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="mb-4 flex gap-2 text-sm">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('admin.external-activities.index', ['status' => $value]) }}"
                class="rounded-full px-3 py-1.5 {{ $status === $value ? 'bg-blue-600 text-white' : 'bg-white text-gray-500 ring-1 ring-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">นักศึกษา</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">ชื่อกิจกรรม</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">หมวดหมู่</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">ชั่วโมง</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">สถานะ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($requests as $req)
                    <tr
                        class="cursor-pointer hover:bg-gray-50"
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
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $req->user->name_thai ?? $req->user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $req->user->student_id }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $req->title }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $categoryLabels[$req->activity_category] }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $req->hours_requested }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-amber-100 text-amber-700' => $req->status === 'pending',
                                'bg-green-100 text-green-700' => $req->status === 'approved',
                                'bg-red-100 text-red-700' => $req->status === 'rejected',
                            ])>
                                {{ ['pending' => 'รอตรวจสอบ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ปฏิเสธแล้ว'][$req->status] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">ไม่มีคำร้องในหมวดนี้</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>

    <!-- Detail modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div @click.outside="showModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl" x-show="selected">
            <template x-if="selected">
                <div>
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-900" x-text="selected.title"></p>
                            <p class="text-xs text-gray-400" x-text="selected.student_name + ' (' + selected.student_id + ')'"></p>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <dl class="mb-4 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-gray-400">หน่วยงานผู้จัด</dt><dd x-text="selected.organization"></dd></div>
                        <div><dt class="text-xs text-gray-400">วันที่จัดกิจกรรม</dt><dd x-text="selected.activity_date"></dd></div>
                        <div><dt class="text-xs text-gray-400">หมวดหมู่</dt><dd x-text="selected.category"></dd></div>
                        <div><dt class="text-xs text-gray-400">ชั่วโมงที่ขอเทียบ</dt><dd x-text="selected.hours_requested"></dd></div>
                    </dl>

                    <img :src="selected.proof_image_url" class="mb-4 max-h-96 w-full rounded-xl object-contain ring-1 ring-gray-200">

                    <template x-if="selected.status === 'rejected' && selected.reject_reason">
                        <p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600" x-text="'เหตุผลที่ปฏิเสธ: ' + selected.reject_reason"></p>
                    </template>

                    <template x-if="selected.status === 'pending' && ! rejecting">
                        <div class="flex gap-3">
                            <form method="POST" :action="approveUrlTemplate.replace('__ID__', selected.id)" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                                    อนุมัติ
                                </button>
                            </form>
                            <button @click="rejecting = true" type="button"
                                class="flex-1 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-100">
                                ปฏิเสธ
                            </button>
                        </div>
                    </template>

                    <template x-if="selected.status === 'pending' && rejecting">
                        <form method="POST" :action="rejectUrlTemplate.replace('__ID__', selected.id)" class="space-y-3">
                            @csrf
                            <textarea name="reject_reason" x-model="rejectReason" required rows="3" placeholder="ระบุเหตุผล เช่น รูปเกียรติบัตรไม่ชัดเจน"
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-red-500 focus:ring-red-500"></textarea>
                            <div class="flex gap-3">
                                <button type="submit" class="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                                    ยืนยันการปฏิเสธ
                                </button>
                                <button @click="rejecting = false" type="button" class="flex-1 rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-600">
                                    ยกเลิก
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
