@props(['title' => null, 'overlay' => false])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('hotel.name') }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='19' fill='%230F1E33'/%3E%3Cpath d='M8 25 Q20 6 32 25' fill='none' stroke='%23C8A15A' stroke-width='3'/%3E%3Ccircle cx='20' cy='18' r='3.5' fill='%23C8A15A'/%3E%3C/svg%3E">


    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen font-sans antialiased">

    {{-- ── Navegação ─────────────────────────────────────────── --}}
    <header x-data="{ scrolled: {{ $overlay ? 'false' : 'true' }}, open: false }"
            @if ($overlay) @scroll.window="scrolled = window.scrollY > 40" @endif
            :class="scrolled || open ? 'bg-white/90 shadow-soft backdrop-blur-lg' : 'bg-transparent'"
            class="no-print {{ $overlay ? 'fixed' : 'sticky' }} inset-x-0 top-0 z-50 transition-all duration-300">
        <nav class="container-site flex h-20 items-center justify-between">
            <a href="{{ route('home') }}" aria-label="Página inicial">
                <x-logo x-show="scrolled || open" />
                @if ($overlay)
                    <x-logo light x-show="!(scrolled || open)" />
                @endif
            </a>

            @php
                $links = [
                    ['rooms.*', 'rooms.index', 'Hospedagem'],
                    ['spa.*', 'spa.index', 'Spa & Bem-estar'],
                    ['spaces.*', 'spaces.index', 'Salas & Eventos'],
                ];
            @endphp

            <div class="hidden items-center gap-1 lg:flex">
                @foreach ($links as [$pattern, $route, $label])
                    <a href="{{ route($route) }}"
                       :class="scrolled ? '{{ request()->routeIs($pattern) ? 'text-gold-600' : 'text-night-700 hover:text-night-900' }}' : 'text-white/90 hover:text-white'"
                       class="relative rounded-full px-4 py-2 text-sm font-medium transition">
                        {{ $label }}
                        @if (request()->routeIs($pattern))
                            <span class="absolute inset-x-4 -bottom-0.5 h-0.5 rounded-full bg-gold-500"></span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="hidden items-center gap-3 lg:flex">
                @auth
                    <a href="{{ route('reservations.index') }}"
                       :class="scrolled ? 'text-night-700 hover:text-night-900' : 'text-white/90 hover:text-white'"
                       class="text-sm font-medium transition">Minhas reservas</a>

                    <div class="relative" x-data="{ menu: false }" @click.outside="menu = false">
                        <button @click="menu = !menu" class="flex items-center gap-2 rounded-full border border-night-900/10 bg-white py-1 pl-1 pr-3 shadow-sm">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-night-900 text-xs font-semibold text-gold-300">{{ auth()->user()->initials }}</span>
                            <svg class="h-4 w-4 text-night-500" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/></svg>
                        </button>
                        <div x-show="menu" x-cloak x-transition.origin.top.right
                             class="absolute right-0 mt-3 w-60 overflow-hidden rounded-2xl border border-night-900/5 bg-white py-2 shadow-soft">
                            <div class="border-b border-night-50 px-4 pb-3 pt-1">
                                <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-night-400">{{ auth()->user()->email }}</p>
                            </div>
                            @if (auth()->user()->canAccessPanel(filament()->getDefaultPanel()))
                                <a href="{{ url('/admin') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gold-700 hover:bg-sand-50">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h12M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/></svg>
                                    Painel administrativo
                                </a>
                            @endif
                            <a href="{{ route('reservations.index') }}" class="block px-4 py-2.5 text-sm hover:bg-sand-50">Minhas reservas</a>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm hover:bg-sand-50">Meu perfil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="block w-full px-4 py-2.5 text-left text-sm text-rose-600 hover:bg-rose-50">Sair</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}"
                       :class="scrolled ? 'text-night-700 hover:text-night-900' : 'text-white/90 hover:text-white'"
                       class="text-sm font-medium transition">Entrar</a>
                    <a href="{{ route('rooms.index') }}" class="btn-gold !px-5 !py-2.5">Reservar agora</a>
                @endauth
            </div>

            <button @click="open = !open" class="lg:hidden" :class="scrolled || open ? 'text-night-900' : 'text-white'" aria-label="Menu">
                <svg x-show="!open" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/></svg>
                <svg x-show="open" x-cloak class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </nav>

        <div x-show="open" x-cloak x-transition class="border-t border-night-50 bg-white px-4 pb-6 pt-2 lg:hidden">
            @foreach ($links as [$pattern, $route, $label])
                <a href="{{ route($route) }}" class="block rounded-xl px-3 py-3 font-medium hover:bg-sand-50">{{ $label }}</a>
            @endforeach
            <div class="mt-2 border-t border-night-50 pt-2">
                @auth
                    <a href="{{ route('reservations.index') }}" class="block rounded-xl px-3 py-3 hover:bg-sand-50">Minhas reservas</a>
                    <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-3 hover:bg-sand-50">Meu perfil</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="block w-full rounded-xl px-3 py-3 text-left text-rose-600">Sair</button></form>
                @else
                    <a href="{{ route('login') }}" class="block rounded-xl px-3 py-3 hover:bg-sand-50">Entrar</a>
                    <a href="{{ route('register') }}" class="btn-gold mt-2 w-full">Criar conta</a>
                @endauth
            </div>
        </div>
    </header>

    <x-flash />

    <main>
        {{ $slot }}
    </main>

    {{-- ── Rodapé ────────────────────────────────────────────── --}}
    <footer class="no-print mt-24 bg-night-950 text-sand-200">
        <div class="container-site grid gap-12 py-16 md:grid-cols-4">
            <div class="md:col-span-2">
                <x-logo light />
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-night-300">
                    Hospedagem, bem-estar e eventos em um só lugar. Reserve quartos, agende terapias
                    e alugue salas com confirmação instantânea.
                </p>
            </div>
            <div>
                <p class="eyebrow !text-gold-400">Reservas</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a class="hover:text-white" href="{{ route('rooms.index') }}">Quartos e suítes</a></li>
                    <li><a class="hover:text-white" href="{{ route('spa.index') }}">Spa &amp; bem-estar</a></li>
                    <li><a class="hover:text-white" href="{{ route('spaces.index') }}">Salas &amp; eventos</a></li>
                </ul>
            </div>
            <div>
                <p class="eyebrow !text-gold-400">Contato</p>
                <ul class="mt-4 space-y-2.5 text-sm text-night-300">
                    <li>{{ config('hotel.address') }}</li>
                    <li>{{ config('hotel.phone') }}</li>
                    <li>{{ config('hotel.email') }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/5">
            <div class="container-site flex flex-col justify-between gap-2 py-6 text-xs text-night-400 sm:flex-row">
                <p>© {{ now()->year }} Aurora Hotel &amp; Spa · Projeto acadêmico, Equipe 5, Programação de Sistemas Web (Afya)</p>
                <p>Laravel {{ app()->version() }} · MySQL · Filament</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
