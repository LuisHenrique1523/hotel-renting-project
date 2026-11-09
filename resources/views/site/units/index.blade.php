<x-layouts.site title="Unidades" overlay>
    <x-page-hero image="hero-bright" eyebrow="Nossas unidades"
                 :title="$total.' endereços para trabalhar bem'"
                 subtitle="Escolha a unidade mais perto de você ou do seu cliente e veja todos os espaços disponíveis nela." />

    <section class="container-site mt-14 space-y-16">
        @foreach ($cities as $city => $units)
            <div>
                <div class="flex items-baseline justify-between border-b border-night-100 pb-3">
                    <h2 class="font-display text-3xl">{{ $city }}</h2>
                    <span class="text-sm text-night-400">{{ $units->count() }} {{ $units->count() === 1 ? 'unidade' : 'unidades' }}</span>
                </div>

                <div class="mt-8 grid gap-8 lg:grid-cols-2">
                    @foreach ($units as $unit)
                        <a href="{{ route('units.show', $unit) }}" class="group card grid overflow-hidden transition hover:-translate-y-1 hover:shadow-xl sm:grid-cols-[14rem_1fr]">
                            <div class="relative aspect-[16/10] overflow-hidden sm:aspect-auto">
                                <img src="{{ $unit->image_url }}" alt="{{ $unit->name }}" class="img-zoom h-full w-full object-cover">
                            </div>
                            <div class="flex flex-col p-6">
                                <p class="eyebrow">{{ $unit->neighborhood }} · {{ $unit->state }}</p>
                                <h3 class="mt-1 font-display text-2xl">{{ $unit->name }}</h3>
                                <p class="mt-1 text-sm text-night-500">{{ $unit->address }}</p>
                                <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-night-600">{{ $unit->description }}</p>
                                <div class="mt-auto flex items-center justify-between pt-5 text-sm">
                                    <span class="chip">🕗 {{ $unit->hours_label }}</span>
                                    <span class="font-semibold text-night-900">{{ $unit->spaces_count }} espaços <span class="transition group-hover:translate-x-1">→</span></span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
</x-layouts.site>
