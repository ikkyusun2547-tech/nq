@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-brand-header :title="__('แก้ไขกิจกรรม').': '.$activity->title" :back="route('admin.activities.index')">
        <x-slot:eyebrow>
            {{ __('กองพัฒนานักศึกษา') }}
            @if ($activity->activity_code)
                · <span class="font-mono">{{ $activity->activity_code }}</span>
            @endif
        </x-slot:eyebrow>
    </x-brand-header>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.activities.update', $activity) }}" enctype="multipart/form-data"
        class="rounded-3xl glass-card p-6 shadow-soft-lg sm:p-8">
        @csrf
        @method('PUT')
        @include('admin.activities._form')

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-xl bg-brand-green-500 px-6 py-3 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                {{ __('บันทึกการแก้ไข') }}
            </button>
        </div>
    </form>
</div>
@endsection
