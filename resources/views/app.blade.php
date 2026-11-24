<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/images/icon.png">
        <link rel="apple-touch-icon" href="/images/icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Theme Anti-FOUC -->
        <script>
            (function () {
                var userTheme = @json(auth()->user()?->theme);
                var storedTheme = null;
                try {
                    storedTheme = localStorage.getItem('gestagil-theme');
                } catch (e) {}

                var theme = userTheme || storedTheme || 'system';
                if (userTheme) {
                    try {
                        localStorage.setItem('gestagil-theme', userTheme);
                    } catch (e) {}
                }

                var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', isDark);
            })();
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        @inertia
    </body>
</html>
