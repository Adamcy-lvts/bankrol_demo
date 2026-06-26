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

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.svg" sizes="any">
        <link rel="apple-touch-icon" href="/favicon.svg">

        {{-- Link / social share preview --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Visual Demo">
        <meta property="og:title" content="Visual Demo">
        <meta property="og:description" content="Bankrol — executive command centre for construction, investment and energy.">
        <meta property="og:image" content="{{ url('/img/bankrol_logo_2.png') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Visual Demo">
        <meta name="twitter:description" content="Bankrol — executive command centre for construction, investment and energy.">
        <meta name="twitter:image" content="{{ url('/img/bankrol_logo_2.png') }}">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Visual Demo') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
