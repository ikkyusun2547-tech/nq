@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">แผงควบคุมกองพัฒนานักศึกษา</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-sm text-gray-400 hover:text-gray-600">ออกจากระบบ</button>
        </form>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.activities.index') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-blue-300">
            <p class="font-medium text-gray-900">จัดการกิจกรรม</p>
            <p class="mt-1 text-sm text-gray-400">สร้าง/แก้ไขกิจกรรม กำหนดสิทธิ์ผู้เข้าร่วม</p>
        </a>
        <a href="{{ route('admin.external-activities.index') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-blue-300">
            <p class="font-medium text-gray-900">คำร้องกิจกรรมภายนอก</p>
            <p class="mt-1 text-sm text-gray-400">ตรวจสอบและอนุมัติ/ปฏิเสธคำร้อง</p>
        </a>
        <a href="{{ route('admin.reports.clearance', ['year' => 4]) }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-blue-300">
            <p class="font-medium text-gray-900">รายงานนักศึกษาพร้อมยื่นจบ (PDF)</p>
            <p class="mt-1 text-sm text-gray-400">ชั้นปีที่ 4 ที่ผ่านเกณฑ์ครบ 100% ส่งต่อสำนักทะเบียน</p>
        </a>
    </div>
</div>
@endsection
