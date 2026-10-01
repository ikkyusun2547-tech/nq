@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-brand-header :title="__('แก้ไขกิจกรรม').': '.$activity->title">
        <x-slot:eyebrow>
            {{ __('กองพัฒนานักศึกษา') }}
            @if ($activity->activity_code)
                · <span class="font-mono">{{ $activity->activity_code }}</span>
            @endif
        </x-slot:eyebrow>
    </x-brand-header>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.activities.update', $activity) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.activities._form')

        {{-- Stays reachable while scrolling a long form; sits above the
             admin bottom bar on phones. --}}
        <div class="sticky bottom-20 z-30 mt-6 flex items-center justify-end gap-2 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-soft-lg dark:border-slate-800 dark:bg-slate-900/95 lg:bottom-4">
            <a href="{{ route('admin.activities.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('ยกเลิก') }}</a>
            <button type="submit" class="rounded-xl bg-brand-purple-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-purple-800">
                {{ __('บันทึกการแก้ไข') }}
            </button>
        </div>
    </form>
</div>
@endsection
