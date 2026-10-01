{{-- Likert interpretation chip. Expects $level (Thai label from ActivitySurvey::interpret). --}}
@php
    $levelClass = [
        'มากที่สุด' => 'bg-brand-green-50 text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400',
        'มาก' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
        'ปานกลาง' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        'น้อย' => 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
        'น้อยที่สุด' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    ][$level] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
@endphp
<span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {{ $levelClass }}">{{ __($level) }}</span>
