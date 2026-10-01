@extends('errors.layout')

@section('code', '403')
@section('title', __('ไม่มีสิทธิ์เข้าถึงหน้านี้'))
@section('tone', 'red')
@section('icon', 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z')
@section('message')
    @auth
        {{ __('คุณกำลังเข้าสู่ระบบด้วยบัญชี :name (:email) ซึ่งไม่มีสิทธิ์เข้าถึงหน้านี้', ['name' => auth()->user()->name_thai ?? auth()->user()->name, 'email' => auth()->user()->email]) }}
    @else
        {{ __('กรุณาเข้าสู่ระบบก่อนใช้งาน') }}
    @endauth
@endsection
@section('actions')
    @auth
        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
            class="flex h-11 items-center justify-center rounded-2xl bg-brand-purple-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">{{ __('กลับไปหน้าของฉัน') }}</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex h-11 w-full items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('ออกจากระบบเพื่อสลับบัญชี') }}</button>
        </form>
    @else
        @include('errors._buttons', ['primary' => [route('login'), __('ไปหน้าเข้าสู่ระบบ')]])
    @endauth
@endsection
