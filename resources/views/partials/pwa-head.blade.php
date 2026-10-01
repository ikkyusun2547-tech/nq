<link rel="manifest" href="{{ asset('manifest.json') }}">
{{-- Status bar / title bar colour = the top bar's colour (white, or slate-950 in
     dark mode). The script below keeps it in step with the theme toggle. --}}
<meta name="theme-color" content="#ffffff">

{{-- iOS ignores the Web App Manifest for most of this and needs its own tags. --}}
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SRRU Check">
<link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">

<script>
    // Chromium fires beforeinstallprompt once, often before Alpine has started,
    // so catch it here and keep it for the install banner and the guide's
    // "ติดตั้งเลย" button (both read window.srruInstallPrompt).
    window.srruSyncThemeColor = () => {
        const dark = document.documentElement.classList.contains('dark');
        document.querySelector('meta[name=theme-color]')?.setAttribute('content', dark ? '#0f0d16' : '#ffffff');
    };
    document.addEventListener('DOMContentLoaded', window.srruSyncThemeColor);

    window.srruInstallPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.srruInstallPrompt = e;
        window.dispatchEvent(new CustomEvent('srru-install-available'));
    });
    window.addEventListener('appinstalled', () => { window.srruInstallPrompt = null; });

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
