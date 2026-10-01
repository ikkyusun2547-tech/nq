@extends('layouts.dashboard')

@section('content')
@php
    $scaleLabels = [1 => __('น้อยที่สุด'), 2 => __('น้อย'), 3 => __('ปานกลาง'), 4 => __('มาก'), 5 => __('มากที่สุด')];
@endphp

<div class="mx-auto max-w-2xl">
    <x-brand-header :title="__('ประเมินความพึงพอใจกิจกรรม')" :subtitle="$activity->title" />

    <div class="rounded-3xl glass-card p-5 shadow-soft-lg sm:p-7">
        @if ($submitted)
            <div class="py-6 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-green-50 text-brand-green-600 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="mt-3 font-medium text-slate-800 dark:text-slate-100">{{ __('คุณประเมินกิจกรรมนี้แล้ว ขอบคุณที่ร่วมประเมิน') }}</p>
            </div>
        @elseif (! $ended)
            <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('เปิดให้ประเมินหลังกิจกรรมสิ้นสุด') }}</p>
        @else
            <p class="mb-5 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:bg-sky-500/10 dark:text-sky-300">
                {{ __('การประเมินนี้ไม่ระบุตัวตน ผู้ดูแลระบบจะเห็นเฉพาะผลรวมและข้อเสนอแนะ โดยไม่ทราบว่าใครเป็นผู้ตอบ') }}
            </p>

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('activity-survey.store', $activity) }}" class="space-y-6">
                @csrf

                @foreach ($questions as $question)
                    <fieldset>
                        <legend class="mb-2 text-sm font-medium text-slate-800 dark:text-slate-100">{{ $loop->iteration }}. {{ $question->text }}</legend>
                        <div class="grid grid-cols-5 gap-1.5 sm:gap-2">
                            @foreach ($scaleLabels as $score => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="scores[{{ $question->id }}]" value="{{ $score }}" required class="peer sr-only"
                                        @checked((int) old("scores.{$question->id}") === $score)>
                                    <span class="flex flex-col items-center gap-0.5 rounded-xl border border-slate-200 bg-white px-1 py-2 text-center transition peer-checked:border-brand-purple-500 peer-checked:bg-brand-purple-50 peer-checked:text-brand-purple-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-purple-400 dark:border-slate-700 dark:bg-slate-900 dark:peer-checked:bg-brand-purple-500/15 dark:peer-checked:text-brand-purple-300">
                                        <span class="text-base font-semibold">{{ $score }}</span>
                                        <span class="text-[10px] leading-tight text-slate-500 dark:text-slate-400 sm:text-xs">{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <div>
                    <label for="comment" class="mb-2 block text-sm font-medium text-slate-800 dark:text-slate-100">{{ __('ข้อเสนอแนะเพิ่มเติม (ไม่บังคับ)') }}</label>
                    <textarea id="comment" name="comment" rows="3" maxlength="2000"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-brand-purple-500 focus:outline-none focus:ring-2 focus:ring-brand-purple-500/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">{{ old('comment') }}</textarea>
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand-green-500 px-4 py-3 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                    {{ __('ส่งแบบประเมิน') }}
                </button>
            </form>
        @endif

        <a href="{{ route('activities.show', $activity) }}" class="mt-5 block text-center text-sm text-slate-500 hover:underline dark:text-slate-400">&larr; {{ __('กลับไปหน้ากิจกรรม') }}</a>
    </div>
</div>
@endsection
