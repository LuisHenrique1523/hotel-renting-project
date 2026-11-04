<x-layouts.site title="Hospedagem" overlay>
    <x-page-hero image="hero-pool" eyebrow="Hospedagem"
                 title="Quartos e suítes para cada tipo de viagem"
                 subtitle="Escolha as datas e veja, em tempo real, quantas unidades de cada categoria ainda estão livres." />

    <div class="container-site relative z-10 -mt-14">
        <x-search-bar :check-in="$checkIn" :check-out="$checkOut" :guests="$guests" />
    </div>

    @php $nights = $checkIn->diffInDays($checkOut); @endphp

    <section class="container-site mt-14">
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2 class="font-display text-2xl">
                @php $open = $types->where('available_rooms_count', '>', 0)->count(); @endphp
                {{ $open }} {{ $open === 1 ? 'categoria disponível' : 'categorias disponíveis' }}
            </h2>
            <p class="text-sm text-night-500">
                {{ $checkIn->translatedFormat('d \d\e F') }} → {{ $checkOut->translatedFormat('d \d\e F') }}
                · {{ $nights }} {{ $nights === 1 ? 'diária' : 'diárias' }} · {{ $guests }} {{ $guests === 1 ? 'hóspede' : 'hóspedes' }}
            </p>
        </div>

        <div class="mt-8 space-y-6">
            @forelse ($types as $type)
                @php
                    $free = $type->available_rooms_count;
                    $link = route('rooms.show', [$type, 'check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString(), 'guests' => $guests]);
                @endphp
                <article @class(['group card grid overflow-hidden md:grid-cols-[22rem_1fr_15rem]', 'opacity-60' => ! $free])>
                    <a href="{{ $link }}" class="relative block aspect-[4/3] overflow-hidden md:aspect-auto">
                        <img src="{{ $type->cover_url }}" alt="{{ $type->name }}" class="img-zoom h-full w-full object-cover">
                        @if ($type->is_featured)
                            <span class="absolute left-4 top-4 chip !bg-gold-500 !text-night-950">★ Mais procurado</span>
                        @endif
                    </a>

                    <div class="p-6 md:p-8">
                        <h3 class="font-display text-2xl"><a href="{{ $link }}" class="hover:text-gold-700">{{ $type->name }}</a></h3>
                        <p class="mt-1 text-sm text-night-500">{{ $type->tagline }}</p>
                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-night-600">
                            <span>📐 {{ $type->size_m2 }} m²</span>
                            <span>🛏 {{ $type->bed }}</span>
                            <span>👤 até {{ $type->capacity }} hóspedes</span>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach (array_slice($type->amenities ?? [], 0, 5) as $amenity)
                                <span class="chip">{{ $amenity }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col justify-between gap-4 border-t border-night-50 bg-sand-50/60 p-6 md:border-l md:border-t-0">
                        <div>
                            @if ($free === 0)
                                <span class="badge bg-rose-100 text-rose-700 ring-rose-200">Esgotado nestas datas</span>
                            @elseif ($free <= 2)
                                <span class="badge bg-amber-100 text-amber-800 ring-amber-200">🔥 Só {{ $free }} {{ $free === 1 ? 'disponível' : 'disponíveis' }}</span>
                            @else
                                <span class="badge bg-emerald-100 text-emerald-800 ring-emerald-200">{{ $free }} disponíveis</span>
                            @endif

                            <p class="mt-4 text-xs text-night-400">Total para {{ $nights }} {{ $nights === 1 ? 'diária' : 'diárias' }}</p>
                            <p class="font-display text-3xl font-semibold">@money($type->quote['total'])</p>
                            <p class="text-xs text-night-400">@money($type->base_price) por diária</p>
                        </div>
                        @if ($free)
                            <a href="{{ $link }}" class="btn-dark w-full">Reservar</a>
                        @else
                            <span class="btn-ghost pointer-events-none w-full">Indisponível</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="card p-12 text-center">
                    <p class="font-display text-2xl">Nenhuma categoria acomoda {{ $guests }} hóspedes.</p>
                    <p class="mt-2 text-night-500">Tente reduzir o número de hóspedes ou fale com a recepção.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-layouts.site>
