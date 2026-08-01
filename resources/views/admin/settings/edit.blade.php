@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-brand-header :title="__('แก้ไขเกณฑ์ รหัสนักศึกษาปี :year', ['year' => $year])" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('มีผลกับนักศึกษาที่เข้าศึกษาปีนี้เท่านั้น')" :back="route('admin.settings.index')" />

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update', $year) }}" class="space-y-5">
        @csrf
        @method('PUT')

        @include('admin.settings._criteria-fields')

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                {{ __('บันทึกเกณฑ์') }}
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.settings.destroy', $year) }}" class="mt-3">
        @csrf
        @method('DELETE')
        <x-confirm-submit tone="red"
            :message="__('ลบเกณฑ์ปี :year? นักศึกษารหัสปีนี้จะกลับไปใช้เกณฑ์ปีก่อนหน้าหรือค่าเริ่มต้นแทน', ['year' => $year])"
            :label="__('ลบเกณฑ์ปี :year', ['year' => $year])"
            class="w-full rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 shadow-soft transition-colors hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20">
            {{ __('ลบเกณฑ์ปี :year', ['year' => $year]) }}
        </x-confirm-submit>
    </form>
</div>
@endsection
