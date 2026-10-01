@extends('errors.layout')

@section('code', '404')
@section('title', __('ไม่พบหน้าที่ต้องการ'))
@section('tone', 'purple')
@section('icon', 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM13.5 10.5h-6')
@section('message')
    {{ __('หน้านี้อาจถูกลบ ย้าย หรือลิงก์พิมพ์ผิด ลองกลับไปหน้าแรกแล้วเข้าใหม่จากเมนู') }}
@endsection
@section('actions')
    @include('errors._buttons', ['primary' => [url('/'), __('กลับหน้าแรก')], 'back' => true])
@endsection
