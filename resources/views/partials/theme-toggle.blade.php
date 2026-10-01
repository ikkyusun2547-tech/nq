<button
    type="button"
    x-data="{ dark: document.documentElement.classList.contains('dark') }"
    x-init="$watch('dark', value => {
        document.documentElement.classList.toggle('dark', value);
        localStorage.theme = value ? 'dark' : 'light';
        window.srruSyncThemeColor?.();
    })"
    @click="dark = ! dark"
    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
    :aria-label="dark ? '{{ __('เปลี่ยนเป็นโหมดสว่าง') }}' : '{{ __('เปลี่ยนเป็นโหมดมืด') }}'"
>
    <svg x-show="! dark" class="h-[1.15rem] w-[1.15rem]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
    <svg x-show="dark" x-cloak class="h-[1.15rem] w-[1.15rem]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
</button>
