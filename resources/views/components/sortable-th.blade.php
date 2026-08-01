@props(['field', 'label'])
@php
    // Generic across every table page: builds from the CURRENT URL rather
    // than a passed-in route name, so this component works unmodified
    // whether the page is a plain index (?sort=&dir=) or a
    // route-model-bound one (/admin/activities/{activity}/attendance) —
    // no per-page route/param wiring needed. 'page' is dropped so sorting
    // always jumps back to page 1 instead of a now-mismatched page number.
    $active = request('sort') === $field;
    $dir = request('dir');
    $baseQuery = collect(request()->query())->except('page');
    $ascUrl = request()->url().'?'.http_build_query($baseQuery->merge(['sort' => $field, 'dir' => 'asc'])->all());
    $descUrl = request()->url().'?'.http_build_query($baseQuery->merge(['sort' => $field, 'dir' => 'desc'])->all());
@endphp
<th {{ $attributes->class(['whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500']) }}>
    <div class="flex items-center gap-1.5">
        {{ $label }}
        <span class="flex flex-col leading-none">
            <a href="{{ $ascUrl }}" title="{{ __('เรียงน้อยไปมาก') }}"
                class="{{ $active && $dir === 'asc' ? 'text-brand-purple-600 dark:text-brand-purple-400' : 'text-slate-300 hover:text-slate-500 dark:text-slate-600 dark:hover:text-slate-400' }}">
                <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 4l6 8H4l6-8z"/></svg>
            </a>
            <a href="{{ $descUrl }}" title="{{ __('เรียงมากไปน้อย') }}"
                class="{{ $active && $dir === 'desc' ? 'text-brand-purple-600 dark:text-brand-purple-400' : 'text-slate-300 hover:text-slate-500 dark:text-slate-600 dark:hover:text-slate-400' }}">
                <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 16l-6-8h12l-6 8z"/></svg>
            </a>
        </span>
    </div>
</th>
