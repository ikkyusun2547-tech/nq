<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ระบบเช็กชื่อกิจกรรมนักศึกษา SRRU') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @include('partials.pwa-head')
    <script>
        if (localStorage.theme === 'dark' || (! ('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen">
        @yield('content')
    </div>
    @include('partials.pwa-install-banner')
    @stack('scripts')
</body>
</html>
