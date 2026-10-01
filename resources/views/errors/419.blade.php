@extends('errors.layout')

@section('code', '419')
@section('title', __('หน้านี้หมดเวลาแล้ว'))
@section('tone', 'amber')
@section('icon', 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z')
@section('message')
    {{ __('เปิดหน้าทิ้งไว้นานเกินไป ระบบจึงยังไม่ได้บันทึกสิ่งที่ส่งมา กดโหลดหน้าใหม่แล้วลองอีกครั้ง') }}
@endsection
@section('actions')
    <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')"
        class="flex h-11 items-center justify-center rounded-2xl bg-brand-purple-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">{{ __('กลับไปลองใหม่') }}</button>
    <a href="{{ url('/') }}" class="flex h-11 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('กลับหน้าแรก') }}</a>
@endsection
