<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#E91E8C">
        <meta name="description" content="{{ config('salon.name') }} — {{ config('salon.tagline') }}. Book hair, skin, laser, and beauty services.">

        <title inertia>{{ config('salon.name', config('app.name', 'Laravel')) }}</title>

        <!-- Fonts: Fraunces (expressive display serif) + Manrope (body) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700,900&family=manrope:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <a href="#main-content" class="skip-link">
            Skip to main content
        </a>
        @inertia
    </body>
</html>
