@extends('errors.layout')

@section('code', '429')
@section('title', __('ส่งคำขอถี่เกินไป'))
@section('tone', 'amber')
@section('icon', 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z')
@section('message')
    {{ __('รอสักครู่ประมาณ 1 นาที แล้วลองใหม่อีกครั้ง') }}
@endsection
@section('actions')
    @include('errors._buttons', ['primary' => [url('/'), __('กลับหน้าแรก')], 'back' => true])
@endsection
