<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'MatchConnect') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-950 text-white antialiased">
        <div class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(16,185,129,0.3),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(14,165,233,0.2),transparent_35%),radial-gradient(circle_at_50%_100%,rgba(168,85,247,0.18),transparent_40%)]"></div>

            <header class="relative mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
                <h1 class="text-xl font-black tracking-tight text-emerald-300">MatchConnect</h1>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg border border-emerald-300/40 bg-emerald-400/20 px-4 py-2 text-sm font-semibold">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold">Login</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-950">Get Started</a>
                    @endauth
                </div>
            </header>

            <main class="relative mx-auto max-w-7xl px-4 pb-16 pt-12 sm:px-6 lg:px-8 lg:pt-20">
                <div class="grid items-center gap-10 lg:grid-cols-2">
                    <div>
                        <p class="inline-flex items-center rounded-full border border-emerald-300/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">
                            Matchmaking MVP
                        </p>
                        <h2 class="mt-5 text-4xl font-black leading-tight sm:text-5xl">
                            From WhatsApp chaos to a clean matchmaking workflow.
                        </h2>
                        <p class="mt-6 max-w-xl text-slate-200">
                            Members create rich profiles, browse approved matches, and request connections. Admins manage approvals, payments, and safety reports from one panel.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('register') }}" class="rounded-xl bg-emerald-400 px-6 py-3 text-sm font-bold text-emerald-950">Create Account</a>
                            <a href="{{ route('login') }}" class="rounded-xl border border-white/30 px-6 py-3 text-sm font-bold">Login</a>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                        <h3 class="text-lg font-bold text-emerald-200">Core Flow</h3>
                        <ol class="mt-4 space-y-3 text-sm text-slate-100">
                            <li class="rounded-lg border border-white/10 bg-white/5 p-3">1. User registers and fills profile + preferences.</li>
                            <li class="rounded-lg border border-white/10 bg-white/5 p-3">2. Admin reviews and approves profile visibility.</li>
                            <li class="rounded-lg border border-white/10 bg-white/5 p-3">3. Users browse and send connection requests.</li>
                            <li class="rounded-lg border border-white/10 bg-white/5 p-3">4. Receiver accepts, payment is submitted, admin confirms.</li>
                            <li class="rounded-lg border border-white/10 bg-white/5 p-3">5. Request becomes connected and contact can be shared.</li>
                        </ol>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
