<div class="flex items-center gap-0.5 rounded-xl bg-slate-100 p-1 text-xs font-semibold dark:bg-slate-800">
    <a href="{{ route('locale.switch', 'th') }}"
        @class([
            'rounded-lg px-2 py-1 transition-colors',
            'bg-white text-brand-purple-700 shadow-soft dark:bg-slate-700 dark:text-brand-purple-300' => app()->getLocale() === 'th',
            'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => app()->getLocale() !== 'th',
        ])>
        TH
    </a>
    <a href="{{ route('locale.switch', 'en') }}"
        @class([
            'rounded-lg px-2 py-1 transition-colors',
            'bg-white text-brand-purple-700 shadow-soft dark:bg-slate-700 dark:text-brand-purple-300' => app()->getLocale() === 'en',
            'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => app()->getLocale() !== 'en',
        ])>
        EN
    </a>
</div>
