<x-layouts.site title="Salas & Eventos" overlay>
    <x-page-hero image="space-event" eyebrow="Salas & Eventos"
                 title="Reserve por hora, do café de negócios ao grande evento"
                 subtitle="Veja a grade de horários de cada espaço e reserve exatamente o tempo de que precisa." />

    <section class="container-site mt-14 space-y-10">
        @foreach ($spaces as $i => $space)
            <article class="group card grid overflow-hidden lg:grid-cols-2">
                <a href="{{ route('spaces.show', $space) }}" @class(['relative block aspect-[16/10] overflow-hidden lg:aspect-auto', 'lg:order-2' => $i % 2])>
                    <img src="{{ $space->image_url }}" alt="{{ $space->name }}" class="img-zoom h-full w-full object-cover">
                </a>
                <div class="flex flex-col justify-center p-8 sm:p-12">
                    <p class="eyebrow">{{ $space->type->getLabel() }}</p>
                    <h2 class="mt-2 font-display text-3xl sm:text-4xl">{{ $space->name }}</h2>
                    <p class="mt-4 leading-relaxed text-night-600">{{ $space->description }}</p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($space->amenities ?? [] as $amenity)
                            <span class="chip">{{ $amenity }}</span>
                        @endforeach
                    </div>
                    <dl class="mt-8 grid grid-cols-3 gap-4 border-y border-night-50 py-5 text-center">
                        <div><dt class="text-xs text-night-400">Capacidade</dt><dd class="mt-1 font-display text-xl">{{ $space->capacity }} pessoas</dd></div>
                        <div><dt class="text-xs text-night-400">Funcionamento</dt><dd class="mt-1 font-display text-xl">{{ $space->opens_hour }}h–{{ $space->closes_hour }}h</dd></div>
                        <div><dt class="text-xs text-night-400">Valor</dt><dd class="mt-1 font-display text-xl">@money($space->hourly_price)<span class="text-sm text-night-400">/h</span></dd></div>
                    </dl>
                    <a href="{{ route('spaces.show', $space) }}" class="btn-dark mt-8 self-start">Ver horários livres →</a>
                </div>
            </article>
        @endforeach
    </section>
</x-layouts.site>
