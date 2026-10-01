{{--
    Student dashboard hero for a single activity (up next / missed / latest).
    Expects: $heroActivity, $heroLabel (chip text), $heroNote (short status
    text), $noteTone (classes for the note on a plain card), $heroAction
    (null or ['url', 'label', 'primary' => bool]), $categoryMeta, $thaiYear.
    The title link's ::after makes the whole card open the activity; the
    action button sits above it (z-10).
--}}
@php
    $hasBanner = (bool) $heroActivity->banner_url;
    $soft = $hasBanner ? 'text-white/85' : 'text-slate-500 dark:text-slate-400';
@endphp
<section aria-label="{{ $heroLabel }}" @class([
    'group relative isolate overflow-hidden rounded-3xl',
    'flex min-h-[15rem] flex-col justify-end bg-slate-900 text-white shadow-soft-lg sm:min-h-[17rem]' => $hasBanner,
    'border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' => ! $hasBanner,
])>
    @if ($hasBanner)
        {{-- Cover photo fills the whole card; the scrim keeps the text readable. --}}
        <img src="{{ asset('storage/'.$heroActivity->banner_url) }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/85 via-black/45 to-black/5"></div>
    @endif
    <div class="flex items-center gap-4 p-5 sm:gap-5 sm:p-6">
        {{-- Date block --}}
        <div @class([
            'flex w-24 shrink-0 flex-col items-center justify-center rounded-2xl px-2 py-3 text-center sm:w-28',
            'bg-white/15 text-white ring-1 ring-white/20' => $hasBanner,
            'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300' => ! $hasBanner,
        ])>
            <span class="text-xs opacity-80">{{ $heroActivity->start_at->translatedFormat('D') }}</span>
            <span class="font-display text-3xl leading-none">{{ $heroActivity->start_at->day }}</span>
            <span class="mt-0.5 text-xs opacity-80">{{ $heroActivity->start_at->translatedFormat('M') }} {{ $thaiYear($heroActivity->start_at) }}</span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span @class([
                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-white/15 text-white' => $hasBanner,
                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => ! $hasBanner,
                ])>
                    <span class="h-1.5 w-1.5 rounded-full {{ $categoryMeta[$heroActivity->activity_category]['dot'] ?? 'bg-slate-400' }}"></span>
                    {{ $heroLabel }}
                </span>
                <span @class(['text-sm font-semibold', 'text-white' => $hasBanner, $noteTone => ! $hasBanner])>{{ $heroNote }}</span>
            </div>
            {{-- The title link's ::after covers the whole card. --}}
            <a href="{{ route('activities.show', $heroActivity) }}" @class([
                'mt-2 block truncate font-display text-xl leading-snug group-hover:underline after:absolute after:inset-0 after:rounded-3xl sm:text-2xl',
                'text-slate-900 dark:text-white' => ! $hasBanner,
            ])>{{ $heroActivity->title }}</a>
            <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-sm {{ $soft }}">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $heroActivity->start_at->format('H:i') }}–{{ $heroActivity->end_at->format('H:i') }}
                </span>
                @if ($heroActivity->location_name)
                    <span class="inline-flex min-w-0 items-center gap-1.5">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <span class="truncate">{{ $heroActivity->location_name }}</span>
                    </span>
                @endif
                <span>{{ __(':hours ชม.', ['hours' => $heroActivity->credit_hours]) }}</span>
                @if ($heroActivity->status === 'draft')
                    <span class="{{ $hasBanner ? 'text-amber-300' : 'text-amber-700 dark:text-amber-300' }}">{{ __('ยังไม่เปิด') }}</span>
                @endif
            </p>
            @if ($heroAction)
                <a href="{{ $heroAction['url'] }}" @class([
                    'relative z-10 mt-4 inline-flex h-10 items-center justify-center rounded-xl px-4 text-sm font-semibold transition-colors',
                    'bg-white text-brand-purple-800 hover:bg-brand-purple-50' => ($heroAction['primary'] ?? true) && $hasBanner,
                    'bg-brand-purple-700 text-white hover:bg-brand-purple-800' => ($heroAction['primary'] ?? true) && ! $hasBanner,
                    'bg-white/15 text-white ring-1 ring-white/25' => ! ($heroAction['primary'] ?? true) && $hasBanner,
                    'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300' => ! ($heroAction['primary'] ?? true) && ! $hasBanner,
                ])>{{ $heroAction['label'] }}</a>
            @endif
        </div>
        <svg class="hidden h-5 w-5 shrink-0 sm:block {{ $hasBanner ? 'text-white/70' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
    </div>
</section>
