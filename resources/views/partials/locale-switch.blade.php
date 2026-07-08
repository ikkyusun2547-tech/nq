<div class="flex items-center gap-0.5 rounded-lg bg-white/5 p-1 text-xs font-semibold">
    <a href="{{ route('locale.switch', 'th') }}"
        @class([
            'rounded-md px-2 py-1 transition-colors',
            'bg-brand-green-500/20 text-brand-green-400' => app()->getLocale() === 'th',
            'text-violet-200/60 hover:text-white' => app()->getLocale() !== 'th',
        ])>
        TH
    </a>
    <a href="{{ route('locale.switch', 'en') }}"
        @class([
            'rounded-md px-2 py-1 transition-colors',
            'bg-brand-green-500/20 text-brand-green-400' => app()->getLocale() === 'en',
            'text-violet-200/60 hover:text-white' => app()->getLocale() !== 'en',
        ])>
        EN
    </a>
</div>
