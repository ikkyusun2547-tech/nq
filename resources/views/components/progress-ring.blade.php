@props([
    'value',
    'target',
    'label',
    'color' => '#7C3AED',
    'track' => 'rgb(124 58 237 / 0.12)',
])

@php
    $size = 76;
    $stroke = 7;
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $progress = $target > 0 ? min(1, $value / $target) : 0;
    $offset = $circumference * (1 - $progress);
@endphp

<div class="flex flex-col items-center">
    <div class="relative" style="width: {{ $size }}px; height: {{ $size }}px;">
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="-rotate-90">
            <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke="{{ $track }}" stroke-width="{{ $stroke }}"/>
            <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke="{{ $color }}" stroke-width="{{ $stroke }}"
                stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"/>
        </svg>
        <span class="absolute inset-0 flex items-center justify-center text-lg font-bold" style="color: {{ $color }};">{{ $value }}</span>
    </div>
    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $label }}</p>
    <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ __('เป้าหมาย :target', ['target' => $target]) }}</p>
</div>
