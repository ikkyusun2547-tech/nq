@extends('errors.layout')

@section('code', '500')
@section('title', __('ระบบขัดข้องชั่วคราว'))
@section('tone', 'red')
@section('icon', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z')
@section('message')
    {{ __('ไม่ใช่ความผิดของคุณ ระบบบันทึกปัญหาไว้แล้ว ลองใหม่อีกครั้งในอีกสักครู่ ถ้ายังไม่ได้ แจ้งเจ้าหน้าที่') }}
@endsection
@section('actions')
    <button type="button" onclick="location.reload()"
        class="flex h-11 items-center justify-center rounded-2xl bg-brand-purple-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">{{ __('ลองใหม่') }}</button>
    <a href="{{ url('/') }}" class="flex h-11 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('กลับหน้าแรก') }}</a>
@endsection
