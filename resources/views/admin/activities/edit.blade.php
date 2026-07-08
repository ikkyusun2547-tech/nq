@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">กองพัฒนานักศึกษา</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">แก้ไขกิจกรรม: {{ $activity->title }}</h1>
        </div>
        <a href="{{ route('admin.activities.index') }}"
            class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
            &larr; กลับรายการกิจกรรม
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100">
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
                บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>
@endsection
