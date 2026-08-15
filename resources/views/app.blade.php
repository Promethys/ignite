<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title data-inertia>{{ config('app.name', 'Laravel') }}</title>

        @php
            $socialTitle = config('app.name').' - '.__('landing.meta.tagline');
            $socialDescription = __('landing.meta.description');
            $socialImage = url('/android-chrome-512x512.png');

            // Built here rather than inline below, because Blade would other-
            // wise compile the schema.org "@context" key as a directive.
            $structuredData = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => config('app.name'),
                'description' => $socialDescription,
                'url' => config('app.url'),
                'applicationCategory' => 'ProductivityApplication',
                'operatingSystem' => 'Web',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        @endphp

        {{-- Rendered here rather than through Inertia's head manager: social
             scrapers do not run JavaScript, so these have to be in the
             response body itself. --}}
        <meta name="description" content="{{ $socialDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $socialTitle }}">
        <meta property="og:description" content="{{ $socialDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $socialImage }}">
        <meta property="og:locale" content="{{ config('locales.open_graph.'.app()->getLocale(), 'en_US') }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $socialTitle }}">
        <meta name="twitter:description" content="{{ $socialDescription }}">
        <meta name="twitter:image" content="{{ $socialImage }}">

        @if (request()->routeIs('home'))
            <script type="application/ld+json">{!! $structuredData !!}</script>
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|inter:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
