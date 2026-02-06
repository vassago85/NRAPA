<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        
        <title>{{ $title ?? 'NRAPA Digital Card' }}</title>
        
        <link rel="icon" href="/nrapa-logo.png" type="image/png">
        <link rel="apple-touch-icon" href="/nrapa-logo.png">
        
        {{-- PWA / Add to Home Screen Meta Tags --}}
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="NRAPA Card">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="theme-color" content="#18181b">
        
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        
        {{-- Alpine.js cloak style --}}
        <style>[x-cloak] { display: none !important; }</style>
        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
        @stack('head')
    </head>
    <body class="min-h-screen antialiased">
        {{ $slot }}
        
        @livewireScripts
        @fluxScripts
    </body>
</html>
