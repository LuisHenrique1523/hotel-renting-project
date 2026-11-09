<x-layouts.site overlay>

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <section class="relative flex min-h-[100svh] items-end overflow-hidden bg-night-950 pb-16 pt-32 sm:items-center sm:pb-0">
        <img src="{{ asset('storage/img/hero-office.jpg') }}" alt="" class="absolute inset-0 h-full w-full animate-slow-zoom object-cover opacity-70">
        <div class="absolute inset-0 bg-gradient-to-t from-night-950 via-night-950/40 to-night-950/30"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-night-950/80 to-transparent"></div>

        <div class="container-site relative">
            <div class="max-w-3xl">
                <p class="eyebrow animate-fade-up !text-gold-300">Coworking · Salas de reunião · Escritórios</p>
                <h1 class="mt-5 animate-fade-up font-display text-5xl font-semibold leading-[1.05] text-white [animation-delay:120ms] sm:text-7xl">
                    Seu escritório, <em class="font-medium text-gold-300">onde você precisar.</em>
                </h1>
                <p class="mt-6 max-w-xl animate-fade-up text-lg leading-relaxed text-sand-200/90 [animation-delay:240ms]">
                    {{ $stats['units'] }} unidades em {{ $stats['cities'] }} cidades. Reserve uma sala por hora, um dia de coworking
                    ou um escritório por mês, com disponibilidade em tempo real.
                </p>
            </div>

            <div class="mt-10 max-w-4xl animate-fade-up [animation-delay:360ms]">
                <x-search-bar glass :cities="$cities" :categories="$categories" />
            </div>

            <div class="mt-10 hidden animate-fade-up items-center gap-8 text-sm text-sand-200/80 [animation-delay:480ms] sm:flex">
                <span class="flex items-center gap-2"><span class="text-gold-400">★★★★★</span> 4,8 de 2.340 avaliações</span>
                <span class="h-4 w-px bg-white/20"></span>
                <span>Cancelamento grátis até 24h antes</span>
                <span class="h-4 w-px bg-white/20"></span>
                <span>Pagamento via Pix ou cartão</span>
            </div>
        </div>
    </section>

    {{-- ── Categorias (cadastradas no painel) ─────────────── --}}
    <section class="container-site relative z-10 pt-24">
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">Escolha o formato</p>
            <h2 class="heading mt-3">Do café de negócios ao escritório da equipe</h2>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($categories as $category)
                <a href="{{ route('spaces.index', ['categoria' => $category->slug]) }}" class="group relative block h-[24rem] overflow-hidden rounded-[2rem] bg-night-900 shadow-soft">
                    <img src="{{ $category->image_url }}" alt="" class="img-zoom absolute inset-0 h-full w-full object-cover opacity-90">
                    <div class="absolute inset-0 bg-gradient-to-t from-night-950 via-night-950/40 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                        <span class="chip !bg-white/15 !text-sand-100 backdrop-blur">{{ $category->spaces_count }} {{ $category->spaces_count === 1 ? 'espaço' : 'espaços' }}</span>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-gold-300">{{ $category->billing_unit->getLabel() }}</p>
                        <h3 class="mt-1 font-display text-2xl">{{ $category->name }}</h3>
                        <p class="mt-2 max-h-0 overflow-hidden text-sm text-sand-200/90 opacity-0 transition-all duration-500 group-hover:max-h-24 group-hover:opacity-100">{{ $category->tagline }}</p>
                        @if ($category->spaces_min_price)
                            <p class="mt-3 text-sm text-sand-200/90">a partir de <span class="font-semibold text-gold-300">@money($category->spaces_min_price){{ $category->billing_unit->suffix() }}</span></p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── Unidades ───────────────────────────────────────── --}}
    <section class="mt-28 bg-night-950 py-24 text-sand-100">
        <div class="container-site">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="eyebrow !text-gold-400">Nossas unidades</p>
                    <h2 class="mt-3 font-display text-4xl leading-tight text-white sm:text-5xl">Endereços para trabalhar bem</h2>
                </div>
                <a href="{{ route('units.index') }}" class="btn-light">Ver todas as unidades</a>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($units->take(4) as $unit)
                    <a href="{{ route('units.show', $unit) }}" class="group block">
                        <div class="relative aspect-[4/5] overflow-hidden rounded-[2rem]">
                            <img src="{{ $unit->image_url }}" alt="{{ $unit->name }}" class="img-zoom h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-night-950/90 via-transparent to-transparent"></div>
                            <span class="absolute left-4 top-4 chip !bg-white/90 backdrop-blur">{{ $unit->spaces_count }} espaços</span>
                            <div class="absolute inset-x-0 bottom-0 p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-300">{{ $unit->city }} · {{ $unit->state }}</p>
                                <h3 class="mt-1 font-display text-2xl text-white">{{ $unit->name }}</h3>
                                <p class="mt-1 text-sm text-sand-200/80">{{ $unit->neighborhood }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Espaços em destaque ───────────────────────────── --}}
    @if ($featured->isNotEmpty())
        <section class="container-site pt-28">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="eyebrow">Mais reservados</p>
                    <h2 class="heading mt-3">Espaços em destaque</h2>
                </div>
                <a href="{{ route('spaces.index') }}" class="btn-ghost">Ver todos os espaços</a>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featured as $space)
                    <x-space-card :space="$space" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Como funciona ──────────────────────────────────── --}}
    <section class="container-site pt-28">
        <div class="grid gap-6 rounded-[2.5rem] bg-sand-100 p-8 sm:p-14 lg:grid-cols-3">
            @foreach ([
                ['1', 'Escolha o espaço', 'Filtre por cidade, tipo de espaço e número de pessoas. Cada espaço mostra fotos, recursos e preço.'],
                ['2', 'Veja a agenda real', 'Horários e dias livres aparecem em tempo real. O sistema nunca deixa duas reservas ocuparem o mesmo espaço.'],
                ['3', 'Pague e pronto', 'Pix ou cartão, confirmação na hora e voucher com QR code para apresentar na recepção.'],
            ] as [$n, $title, $text])
                <div class="rounded-3xl bg-white p-7 shadow-soft">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-night-900 font-display text-lg text-gold-300">{{ $n }}</span>
                    <h3 class="mt-5 font-display text-2xl">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-night-500">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── CTA ─────────────────────────────────────────────── --}}
    <section class="container-site pt-28">
        <div class="relative overflow-hidden rounded-[2.5rem] bg-night-900 px-8 py-16 text-center sm:px-16">
            <img src="{{ asset('storage/img/hero-lounge.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-25">
            <div class="relative">
                <h2 class="font-display text-4xl text-white sm:text-5xl">Sua próxima reunião começa aqui.</h2>
                <p class="mx-auto mt-4 max-w-xl text-sand-200/90">Crie sua conta em segundos e acompanhe todas as reservas em um só lugar.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('spaces.index') }}" class="btn-gold">Reservar um espaço</a>
                    <a href="{{ route('units.index') }}" class="btn-light">Conhecer as unidades</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
