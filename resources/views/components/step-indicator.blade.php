@props([
    'steps',
    'current' => 'stepIndex',
])

<div class="flex items-start">
    @foreach ($steps as $index => $label)
        <div class="flex flex-1 flex-col items-center text-center">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors duration-200"
                :class="({{ $current }} > {{ $index }}) || ({{ $current }} === {{ $index }})
                    ? 'bg-brand-purple-700 text-white'
                    : 'border border-slate-200 bg-white text-slate-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-500'">
                <template x-if="{{ $current }} > {{ $index }}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </template>
                <template x-if="{{ $current }} <= {{ $index }}">
                    <span>{{ $index + 1 }}</span>
                </template>
            </span>
            <span class="mt-1.5 text-[11px] font-medium"
                :class="{{ $current }} >= {{ $index }} ? 'text-brand-purple-700 dark:text-brand-purple-400' : 'text-slate-400 dark:text-slate-500'">
                {{ $label }}
            </span>
        </div>

        @if (! $loop->last)
            <div class="mt-3.5 h-0.5 flex-1 rounded-full transition-colors duration-200"
                :class="{{ $current }} > {{ $index }} ? 'bg-brand-purple-700' : 'bg-slate-200 dark:bg-slate-700'">
            </div>
        @endif
    @endforeach
</div>
