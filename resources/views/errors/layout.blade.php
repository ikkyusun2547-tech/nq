{{--
    Shared shell for the error pages (404, 419, 429, 500, 503 …).
    Deliberately lighter than layouts.app: no install/push banners and no
    session or database lookups of its own, so it still renders when the
    error *is* the database or the session (500 / 503). Pages that need the
    signed-in user (403) check it themselves.

    Sections: code, title, message, icon (SVG path), tone (red|amber|purple|slate), actions.
--}}
@php
    $tone = trim($__env->yieldContent('tone')) ?: 'purple';
    $toneClass = [
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        'purple' => 'bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300',
        'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    ][$tone] ?? '';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title') · SRRU Check</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @include('partials.fonts')
    <script>
        if (localStorage.theme === 'dark' || (! ('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <main class="flex min-h-dvh flex-col items-center justify-center px-4 py-12">
        <a href="{{ url('/') }}" class="mb-8 flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 object-contain">
            <span class="leading-tight">
                <span class="block font-display text-[1.05rem] font-semibold text-slate-900 dark:text-white">SRRU Check</span>
                <span class="block text-[0.7rem] text-slate-500 dark:text-slate-400">{{ __('มหาวิทยาลัยราชภัฏสุรินทร์') }}</span>
            </span>
        </a>

        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 text-center dark:border-slate-800 dark:bg-slate-900">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl {{ $toneClass }}">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="@yield('icon')"/></svg>
            </span>
            @hasSection('code')
                <p class="mt-5 font-display text-sm tracking-widest text-slate-400 dark:text-slate-500">@yield('code')</p>
            @endif
            <h1 class="mt-1 font-display text-2xl text-slate-900 dark:text-white">@yield('title')</h1>
            <div class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">@yield('message')</div>
            <div class="mt-6 flex flex-col gap-2.5">
                @yield('actions')
            </div>
        </div>
    </main>
</body>
</html>
