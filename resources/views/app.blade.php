<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7C5CFC">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">

    <title inertia>TimeFlow</title>

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">

    <!-- PRD §6 — Inject user timezone for frontend composable -->
    <script>
        window.__APP_TIMEZONE = @json(auth()->user()?->timezone ?? 'UTC');
        window.__APP_USER_ID = @json(auth()->user()?->id);
        window.__VAPID_PUBLIC_KEY = @json(config('webpush.vapid.public_key'));
    </script>

    <!-- Dark mode init: prevents flash of wrong theme (DESIGN.md §10) -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>


    @if (! app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
