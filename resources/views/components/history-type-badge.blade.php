@props(['type'])

{{-- Which kind of record a history row is: a checked-in activity, an external-activity request, or a position credit transfer. --}}
@php
    [$label, $classes] = match ($type) {
        'external' => [__('กิจกรรมภายนอก'), 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300'],
        'credit_transfer' => [__('เทียบโอนตำแหน่ง'), 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300'],
        default => [__('กิจกรรม'), 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-300'],
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center rounded-md px-1.5 py-0.5 text-[0.7rem] font-semibold leading-none {$classes}"]) }}>{{ $label }}</span>
