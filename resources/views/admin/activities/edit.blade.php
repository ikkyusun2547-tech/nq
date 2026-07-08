@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">แก้ไขกิจกรรม: {{ $activity->title }}</h1>
        <a href="{{ route('admin.activities.index') }}" class="text-sm text-gray-400 hover:text-gray-600">&larr; กลับรายการกิจกรรม</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.activities.update', $activity) }}" enctype="multipart/form-data"
        class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        @csrf
        @method('PUT')
        @include('admin.activities._form')

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-xl bg-brand-green-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-green-700">
                บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>
@endsection
