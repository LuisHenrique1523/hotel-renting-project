<x-layouts.site overlay>

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <section class="relative flex min-h-[100svh] items-end overflow-hidden bg-night-950 pb-16 pt-32 sm:items-center sm:pb-0">
        <img src="{{ asset('storage/img/hero-resort.jpg') }}" alt="" class="absolute inset-0 h-full w-full animate-slow-zoom object-cover opacity-80">
        <div class="absolute inset-0 bg-gradient-to-t from-night-950 via-night-950/40 to-night-950/30"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-night-950/70 to-transparent"></div>

        <div class="container-site relative">
            <div class="max-w-3xl">
                <p class="eyebrow animate-fade-up !text-gold-300">Hotel · Spa · Eventos</p>
                <h1 class="mt-5 animate-fade-up font-display text-5xl font-semibold leading-[1.05] text-white [animation-delay:120ms] sm:text-7xl">
                    Onde cada estadia <em class="font-medium text-gold-300">vira lembrança.</em>
                </h1>
                <p class="mt-6 max-w-xl animate-fade-up text-lg leading-relaxed text-sand-200/90 [animation-delay:240ms]">
                    Reserve sua suíte, agende uma massagem e organize seu evento.
                    Disponibilidade em tempo real e confirmação instantânea.
                </p>
            </div>

            <div class="mt-10 max-w-4xl animate-fade-up [animation-delay:360ms]">
                <x-search-bar glass />
            </div>

            <div class="mt-10 hidden animate-fade-up items-center gap-8 text-sm text-sand-200/80 [animation-delay:480ms] sm:flex">
                <span class="flex items-center gap-2"><span class="text-gold-400">★★★★★</span> 4,9 de 1.280 avaliações</span>
                <span class="h-4 w-px bg-white/20"></span>
                <span>Cancelamento grátis até 24h antes</span>
                <span class="h-4 w-px bg-white/20"></span>
                <span>Pagamento seguro via Pix ou cartão</span>
            </div>
        </div>
    </section>

    {{-- ── O que dá para reservar ─────────────────────────── --}}
    <section class="container-site relative z-10 -mt-px pt-24">
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">Uma plataforma, três experiências</p>
            <h2 class="heading mt-3">Tudo que você precisa, reservado em segundos</h2>
        </div>

        @php
            $pillars = [
                ['rooms.index', 'room-2', 'Hospedagem', 'Quartos e suítes por diária', 'Seis categorias, do aconchegante Standard à cobertura Presidencial.', $stats['rooms'].' acomodações'],
                ['spa.index', 'spa-massage', 'Spa & Bem-estar', 'Profissionais por horário', 'Massagens, estética e fitness com a agenda real de cada terapeuta.', $stats['professionals'].' especialistas'],
                ['spaces.index', 'space-meeting', 'Salas & Eventos', 'Espaços por hora', 'Da sala executiva ao grande salão, reserve exatamente as horas de que precisa.', $stats['spaces'].' espaços'],
            ];
        @endphp

        <div class="mt-14 grid gap-6 md:grid-cols-3">
            @foreach ($pillars as $i => [$route, $img, $title, $kicker, $text, $count])
                <a href="{{ route($route) }}" class="group relative block h-[26rem] overflow-hidden rounded-[2rem] bg-night-900 shadow-soft">
                    <img src="{{ asset("storage/img/{$img}.jpg") }}" alt="" class="img-zoom absolute inset-0 h-full w-full object-cover opacity-90">
                    <div class="absolute inset-0 bg-gradient-to-t from-night-950 via-night-950/40 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-7 text-white">
                        <span class="chip !bg-white/15 !text-sand-100 backdrop-blur">{{ $count }}</span>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-gold-300">{{ $kicker }}</p>
                        <h3 class="mt-1 font-display text-3xl">{{ $title }}</h3>
                        <p class="mt-2 max-h-0 overflow-hidden text-sm text-sand-200/90 opacity-0 transition-all duration-500 group-hover:max-h-24 group-hover:opacity-100">{{ $text }}</p>
                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-gold-300">
                            Reservar <span class="transition group-hover:translate-x-1">→</span>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── Quartos em destaque ─────────────────────────────── --}}
    <section class="container-site pt-28">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="eyebrow">Acomodações</p>
                <h2 class="heading mt-3">Suítes que hóspedes amam</h2>
            </div>
            <a href="{{ route('rooms.index') }}" class="btn-ghost">Ver todas as categorias</a>
        </div>

        <div class="mt-12 grid gap-8 md:grid-cols-3">
            @foreach ($featuredRooms as $type)
                <a href="{{ route('rooms.show', $type) }}" class="group block">
                    <div class="relative aspect-[4/5] overflow-hidden rounded-[2rem]">
                        <img src="{{ $type->cover_url }}" alt="{{ $type->name }}" class="img-zoom h-full w-full object-cover">
                        <span class="absolute left-4 top-4 chip !bg-white/90 backdrop-blur">{{ $type->size_m2 }} m² · até {{ $type->capacity }} hóspedes</span>
                    </div>
                    <div class="mt-5 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-display text-2xl">{{ $type->name }}</h3>
                            <p class="mt-1 text-sm text-night-500">{{ $type->tagline }}</p>
                        </div>
                        <p class="shrink-0 text-right">
                            <span class="block text-xs text-night-400">a partir de</span>
                            <span class="font-display text-xl font-semibold">@money($type->base_price)</span>
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── Spa ────────────────────────────────────────────── --}}
    <section class="mt-28 bg-night-950 py-24 text-sand-100">
        <div class="container-site grid items-center gap-14 lg:grid-cols-2">
            <div class="relative">
                <img src="{{ asset('storage/img/spa-stones.jpg') }}" alt="" class="aspect-[4/5] w-full rounded-[2rem] object-cover">
                <img src="{{ asset('storage/img/spa-facial.jpg') }}" alt="" class="absolute -bottom-8 -right-4 hidden w-1/2 rounded-3xl border-8 border-night-950 object-cover sm:block">
            </div>
            <div>
                <p class="eyebrow !text-gold-400">Aurora Spa</p>
                <h2 class="mt-3 font-display text-4xl leading-tight text-white sm:text-5xl">Escolha o terapeuta, o dia e o horário.</h2>
                <p class="mt-5 max-w-lg text-night-300">
                    A agenda de cada profissional é calculada em tempo real, cruzando expediente,
                    duração do serviço e horários já ocupados.
                </p>

                <ul class="mt-10 divide-y divide-white/10 border-y border-white/10">
                    @foreach ($services as $service)
                        <li>
                            <a href="{{ route('spa.show', $service) }}" class="group flex items-center justify-between gap-6 py-4">
                                <span>
                                    <span class="block font-display text-xl text-white transition group-hover:text-gold-300">{{ $service->name }}</span>
                                    <span class="text-sm text-night-400">{{ $service->duration_label }} · {{ $service->category->getLabel() }}</span>
                                </span>
                                <span class="flex items-center gap-4">
                                    <span class="font-semibold text-gold-300">@money($service->price)</span>
                                    <span class="grid h-9 w-9 place-items-center rounded-full border border-white/15 transition group-hover:border-gold-400 group-hover:bg-gold-500 group-hover:text-night-950">→</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('spa.index') }}" class="btn-gold mt-10">Conhecer o spa</a>
            </div>
        </div>
    </section>

    {{-- ── Salas ──────────────────────────────────────────── --}}
    <section class="container-site pt-28">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="eyebrow">Salas &amp; Eventos</p>
                <h2 class="heading mt-3">Espaços para trabalhar e celebrar</h2>
            </div>
            <a href="{{ route('spaces.index') }}" class="btn-ghost">Ver todos os espaços</a>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($spaces as $space)
                <a href="{{ route('spaces.show', $space) }}" class="group card overflow-hidden">
                    <div class="aspect-[4/3] overflow-hidden">
                        <img src="{{ $space->image_url }}" alt="{{ $space->name }}" class="img-zoom h-full w-full object-cover">
                    </div>
                    <div class="p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gold-600">{{ $space->type->getLabel() }}</p>
                        <h3 class="mt-1 font-display text-xl">{{ $space->name }}</h3>
                        <div class="mt-3 flex items-center justify-between text-sm text-night-500">
                            <span>até {{ $space->capacity }} pessoas</span>
                            <span class="font-semibold text-night-900">@money($space->hourly_price)<span class="font-normal text-night-400">/h</span></span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── Depoimentos + números ─────────────────────────── --}}
    <section class="container-site pt-28">
        <div class="grid gap-6 rounded-[2.5rem] bg-sand-100 p-8 sm:p-14 lg:grid-cols-3">
            @foreach ([
                ['Reservei a suíte e a massagem do casal no mesmo lugar, em 2 minutos. O lembrete por e-mail foi um charme.', 'Fernanda L.', 'Lua de mel · Suíte Bangalô'],
                ['Organizamos o lançamento no Auditório Horizonte. Ver os horários livres na hora economizou dias de troca de e-mails.', 'Ricardo M.', 'Evento corporativo'],
                ['A Sala Executiva tem vista para o mar e reservo por hora quando venho a trabalho. Simples e sem surpresas.', 'Patrícia S.', 'Hóspede frequente'],
            ] as [$quote, $name, $context])
                <figure class="rounded-3xl bg-white p-7 shadow-soft">
                    <p class="text-gold-500">★★★★★</p>
                    <blockquote class="mt-4 font-display text-lg leading-relaxed">“{{ $quote }}”</blockquote>
                    <figcaption class="mt-5 text-sm"><span class="font-semibold">{{ $name }}</span> <span class="text-night-400">· {{ $context }}</span></figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- ── CTA ─────────────────────────────────────────────── --}}
    <section class="container-site pt-28">
        <div class="relative overflow-hidden rounded-[2.5rem] bg-night-900 px-8 py-16 text-center sm:px-16">
            <img src="{{ asset('storage/img/hero-pool.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-25">
            <div class="relative">
                <h2 class="font-display text-4xl text-white sm:text-5xl">Sua próxima pausa começa aqui.</h2>
                <p class="mx-auto mt-4 max-w-xl text-sand-200/90">Crie sua conta em segundos e acompanhe todas as reservas em um só lugar.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('rooms.index') }}" class="btn-gold">Reservar hospedagem</a>
                    <a href="{{ route('spa.index') }}" class="btn-light">Agendar no spa</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
