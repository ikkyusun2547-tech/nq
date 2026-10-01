{{-- Shared buttons for the error pages: $primary = [url, label], $back = show "go back". --}}
@isset($primary)
    <a href="{{ $primary[0] }}" class="flex h-11 items-center justify-center rounded-2xl bg-brand-purple-700 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">{{ $primary[1] }}</a>
@endisset
@if ($back ?? false)
    <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')"
        class="flex h-11 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('ย้อนกลับ') }}</button>
@endif
