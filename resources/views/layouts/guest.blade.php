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
    <body class="font-sans antialiased text-slate-900">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(34,197,94,0.25),_transparent_42%),radial-gradient(circle_at_bottom_left,_rgba(14,165,233,0.2),_transparent_35%),linear-gradient(to_bottom,_#f8fafc,_#eff6ff)]">
            <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-6 text-center">
                    <a href="{{ route('home') }}" class="text-2xl font-black tracking-tight text-emerald-700">MatchConnect</a>
                    <p class="mt-2 text-sm text-slate-600">Trusted matchmaking, now simpler and faster.</p>
                </div>

                <div class="mx-auto w-full max-w-md rounded-2xl border border-white/70 bg-white/90 p-6 shadow-xl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
