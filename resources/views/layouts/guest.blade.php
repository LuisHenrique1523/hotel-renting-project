<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('aurora.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-night-950 lg:block">
            <img src="{{ asset('storage/img/hero-lounge.jpg') }}" alt="" class="absolute inset-0 h-full w-full animate-slow-zoom object-cover opacity-70">
            <div class="absolute inset-0 bg-gradient-to-t from-night-950 via-night-950/30 to-night-950/40"></div>
            <div class="relative flex h-full flex-col justify-between p-12">
                <a href="{{ route('home') }}"><x-logo light /></a>
                <div>
                    <p class="font-display text-4xl leading-tight text-white">“Seu escritório,<br><em class="text-gold-300">onde você precisar</em>.”</p>
                    <p class="mt-4 text-sm text-sand-200/80">Coworking · Salas de reunião · Escritórios privativos, em várias cidades.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-center justify-center bg-sand-50 px-6 py-12">
            <a href="{{ route('home') }}" class="mb-10 lg:hidden"><x-logo /></a>
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
