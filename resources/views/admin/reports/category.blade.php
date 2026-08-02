@extends('layouts.dashboard')

@section('content')
@php
    $categoryMeta = [
        'culture' => ['label' => __('ทำนุบำรุงศิลปวัฒนธรรม'), 'bar' => 'bg-sky-400 dark:bg-sky-600', 'well' => 'bg-sky-50 dark:bg-sky-500/10', 'text' => 'text-sky-600 dark:text-sky-400'],
        'academic' => ['label' => __('วิชาการ'), 'bar' => 'bg-brand-green-500 dark:bg-brand-green-600', 'well' => 'bg-brand-green-50 dark:bg-brand-green-500/10', 'text' => 'text-brand-green-600 dark:text-brand-green-400'],
        'sports' => ['label' => __('กีฬาและส่งเสริมสุขภาพ'), 'bar' => 'bg-amber-500 dark:bg-amber-600', 'well' => 'bg-amber-50 dark:bg-amber-500/10', 'text' => 'text-amber-600 dark:text-amber-400'],
        'volunteer' => ['label' => __('จิตอาสา/บำเพ็ญประโยชน์'), 'bar' => 'bg-brand-purple-500', 'well' => 'bg-brand-purple-50 dark:bg-brand-purple-500/10', 'text' => 'text-brand-purple-600 dark:text-brand-purple-400'],
        'ethics' => ['label' => __('คุณธรรมจริยธรรม'), 'bar' => 'bg-fuchsia-400 dark:bg-fuchsia-600', 'well' => 'bg-fuchsia-50 dark:bg-fuchsia-500/10', 'text' => 'text-fuchsia-600 dark:text-fuchsia-400'],
    ];
    $totalHours = max(0, array_sum($categoryHours));
    $maxHours = max(1, max($categoryHours));
    // Descending so the most and least represented categories are
    // immediately obvious at a glance, top to bottom.
    arsort($categoryHours);
@endphp

<div class="mx-auto max-w-4xl">
    <x-brand-header :title="__('ชั่วโมงสะสมแยกตามหมวดหมู่ (5 ด้าน)')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('ภาพรวมทั้งมหาวิทยาลัย ไม่ใช่รายบุคคล — ใช้ดูว่าหมวดไหนมีกิจกรรมครอบคลุมน้อยเพื่อวางแผนปีถัดไป')" />

    <div class="mb-4 rounded-xl border border-brand-purple-200 bg-brand-purple-50 p-4 dark:border-brand-purple-500/20 dark:bg-brand-purple-500/10">
        <p class="text-xs font-medium text-brand-purple-700 dark:text-brand-purple-400">{{ __('ชั่วโมงสะสมรวมทุกหมวดหมู่') }}</p>
        <p class="mt-0.5 text-2xl font-bold text-brand-purple-800 dark:text-brand-purple-300">{{ number_format($totalHours) }} <span class="text-sm font-normal text-brand-purple-600 dark:text-brand-purple-400">{{ __('ชั่วโมง') }}</span></p>
    </div>

    <div class="rounded-2xl glass-card p-5 shadow-soft">
        <div class="space-y-5">
            @foreach ($categoryHours as $key => $hours)
                @php
                    $meta = $categoryMeta[$key] ?? ['label' => $key, 'bar' => 'bg-slate-400', 'well' => 'bg-slate-50', 'text' => 'text-slate-600'];
                    $pctOfTotal = $totalHours > 0 ? round($hours / $totalHours * 100, 1) : 0;
                    $pctOfMax = round($hours / $maxHours * 100);
                @endphp
                <div>
                    <div class="mb-1.5 flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 font-medium text-slate-700 dark:text-slate-200">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $meta['well'] }}">
                                <span class="h-2.5 w-2.5 rounded-full {{ $meta['bar'] }}"></span>
                            </span>
                            {{ $meta['label'] }}
                        </span>
                        <span class="tabular-nums {{ $meta['text'] }} font-semibold">{{ number_format($hours) }} {{ __('ชม.') }}</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full {{ $meta['bar'] }}" style="width: {{ $pctOfMax }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('คิดเป็น :pct% ของชั่วโมงสะสมทั้งหมด', ['pct' => $pctOfTotal]) }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
