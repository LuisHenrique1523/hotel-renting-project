<x-layouts.site :title="$unit->name">
    <div class="container-site pt-8">
        <a href="{{ route('units.index') }}" class="text-sm text-night-500 hover:text-night-900">← Todas as unidades</a>
    </div>

    <section class="container-site mt-6">
        <div class="relative h-72 overflow-hidden rounded-[2rem] shadow-soft sm:h-96">
            <img src="{{ $unit->image_url }}" alt="{{ $unit->name }}" class="h-full w-full animate-slow-zoom object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-night-950/85 to-transparent"></div>
            <div class="absolute bottom-0 p-8 text-white">
                <p class="eyebrow !text-gold-300">{{ $unit->neighborhood }} · {{ $unit->city }}/{{ $unit->state }}</p>
                <h1 class="mt-2 font-display text-4xl font-semibold sm:text-5xl">{{ $unit->name }}</h1>
            </div>
        </div>
    </section>

    <section class="container-site mt-10 grid gap-10 lg:grid-cols-[1fr_22rem]">
        <div>
            <p class="text-lg leading-relaxed text-night-600">{{ $unit->description }}</p>
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($unit->amenities ?? [] as $amenity)
                    <span class="chip !px-4 !py-2 !text-sm">✓ {{ $amenity }}</span>
                @endforeach
            </div>
        </div>
        <aside class="card h-fit space-y-4 p-6 text-sm">
            <div><p class="field-label">Endereço</p><p class="font-medium">{{ $unit->address }}</p><p class="text-night-500">{{ $unit->neighborhood }} · {{ $unit->city }}/{{ $unit->state }} {{ $unit->zip_code }}</p></div>
            <div><p class="field-label">Funcionamento</p><p class="font-medium">Todos os dias, das {{ $unit->hours_label }}</p></div>
            @if ($unit->phone || $unit->email)
                <div><p class="field-label">Contato</p><p class="font-medium">{{ $unit->phone }}</p><p class="text-night-500">{{ $unit->email }}</p></div>
            @endif
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($unit->address.', '.$unit->city.' '.$unit->state) }}" target="_blank" rel="noopener" class="btn-ghost w-full">Ver no mapa</a>
        </aside>
    </section>

    <section class="container-site mt-16 space-y-14">
        @forelse ($groups as $spaces)
            @php $category = $spaces->first()->category; @endphp
            <div>
                <div class="flex flex-col justify-between gap-2 border-b border-night-100 pb-3 sm:flex-row sm:items-end">
                    <div>
                        <p class="eyebrow">{{ $category->billing_unit->getLabel() }}{{ $category->is_shared ? ' · preço por pessoa' : '' }}</p>
                        <h2 class="mt-1 font-display text-3xl">{{ $category->name }}</h2>
                    </div>
                    <p class="max-w-md text-sm text-night-500">{{ $category->tagline }}</p>
                </div>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($spaces as $space)
                        <x-space-card :space="$space->setRelation('unit', $unit)" :show-unit="false" />
                    @endforeach
                </div>
            </div>
        @empty
            <div class="card p-14 text-center text-night-500">Esta unidade ainda não tem espaços disponíveis para reserva.</div>
        @endforelse
    </section>

    @if ($others->isNotEmpty())
        <section class="container-site mt-24">
            <h2 class="heading">Outras unidades</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @foreach ($others as $other)
                    <a href="{{ route('units.show', $other) }}" class="group card overflow-hidden">
                        <div class="aspect-[16/10] overflow-hidden"><img src="{{ $other->image_url }}" alt="" class="img-zoom h-full w-full object-cover"></div>
                        <div class="p-5">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gold-600">{{ $other->city }} · {{ $other->state }}</p>
                            <p class="mt-1 font-display text-xl">{{ $other->name }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
