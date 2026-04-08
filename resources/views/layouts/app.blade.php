<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MatchConnect') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.09),_transparent_40%),linear-gradient(to_bottom,_#f8fafc,_#eef2ff)]">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-slate-200/70 bg-white/80 backdrop-blur">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="pb-10">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
