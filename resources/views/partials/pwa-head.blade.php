<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#2e1065">

{{-- iOS ignores the Web App Manifest for most of this and needs its own tags. --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="SRRU Check">
<link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {
                // Installability is a nice-to-have, not a hard requirement —
                // a failed registration (e.g. unsupported browser) shouldn't
                // be treated as an app error anywhere.
            });
        });
    }
</script>
