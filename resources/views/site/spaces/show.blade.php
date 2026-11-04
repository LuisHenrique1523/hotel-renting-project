<x-layouts.site :title="$space->name">
    <div class="container-site pt-8">
        <a href="{{ route('spaces.index') }}" class="text-sm text-night-500 hover:text-night-900">← Todos os espaços</a>
    </div>

    <section class="container-site mt-6">
        <div class="relative h-72 overflow-hidden rounded-[2rem] shadow-soft sm:h-96">
            <img src="{{ $space->image_url }}" alt="{{ $space->name }}" class="h-full w-full animate-slow-zoom object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-night-950/80 to-transparent"></div>
            <div class="absolute bottom-0 p-8 text-white">
                <p class="eyebrow !text-gold-300">{{ $space->type->getLabel() }} · até {{ $space->capacity }} pessoas</p>
                <h1 class="mt-2 font-display text-4xl font-semibold sm:text-5xl">{{ $space->name }}</h1>
            </div>
        </div>
    </section>

    <section class="container-site mt-10 grid gap-10 lg:grid-cols-[1fr_26rem]"
             x-data="spaceBooking({ endpoint: '{{ route('availability.space', $space) }}', price: {{ (float) $space->hourly_price }} })">
        <div class="min-w-0">
            <p class="text-lg leading-relaxed text-night-600">{{ $space->description }}</p>
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($space->amenities ?? [] as $amenity)
                    <span class="chip !px-4 !py-2 !text-sm">✓ {{ $amenity }}</span>
                @endforeach
            </div>

            <div class="card mt-10 p-6 sm:p-8">
                <h2 class="font-display text-2xl">Agenda do espaço</h2>
                <p class="mt-1 text-sm text-night-500">Clique no horário de início e depois no horário final para selecionar o intervalo.</p>

                <div class="-mx-2 mt-6 flex gap-2 overflow-x-auto px-2 pb-2">
                    <template x-for="d in days" :key="d.iso">
                        <button type="button" @click="pickDate(d.iso)"
                                :class="date === d.iso ? 'border-night-900 bg-night-900 text-white' : 'border-night-100 bg-white hover:border-night-300'"
                                class="flex w-16 shrink-0 flex-col items-center rounded-2xl border py-3 transition">
                            <span class="text-[0.65rem] font-semibold uppercase tracking-wider opacity-70" x-text="d.weekday"></span>
                            <span class="font-display text-xl font-semibold" x-text="d.day"></span>
                            <span class="text-[0.65rem] opacity-70" x-text="d.month"></span>
                        </button>
                    </template>
                </div>

                <div class="mt-6">
                    <div x-show="loading" class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                        <template x-for="i in 10"><div class="h-14 animate-pulse rounded-xl bg-night-50"></div></template>
                    </div>
                    <div x-show="!loading" x-cloak class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                        <template x-for="(s, i) in slots" :key="s.start">
                            <button type="button" @click="pick(i)" :disabled="!s.available"
                                    :class="selected(i) ? 'border-gold-500 bg-gold-500 text-night-950 shadow-glow'
                                        : (s.available ? 'border-night-100 bg-white hover:border-gold-400' : 'cursor-not-allowed border-transparent bg-night-50 text-night-300')"
                                    class="flex h-14 flex-col items-center justify-center rounded-xl border text-sm font-semibold transition">
                                <span x-text="s.time + ' – ' + s.end"></span>
                                <span class="text-[0.6rem] font-medium uppercase tracking-wider"
                                      x-text="s.reason === 'busy' ? 'reservado' : (s.reason === 'past' ? 'encerrado' : (selected(i) ? 'selecionado' : 'livre'))"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <aside>
            <div class="card sticky top-28 p-6">
                <p><span class="font-display text-3xl font-semibold">@money($space->hourly_price)</span> <span class="text-sm text-night-400">/ hora</span></p>
                <p class="mt-1 text-sm text-night-500">Funcionamento das {{ $space->opens_hour }}h às {{ $space->closes_hour }}h</p>

                <div class="mt-6 rounded-2xl bg-sand-50 p-5">
                    <template x-if="start === null">
                        <p class="text-sm text-night-500">Nenhum horário selecionado ainda.</p>
                    </template>
                    <template x-if="start !== null">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-night-400">Sua reserva</p>
                            <p class="mt-1 font-semibold" x-text="summary()"></p>
                            <div class="mt-4 flex items-baseline justify-between border-t border-night-100 pt-4">
                                <span class="text-sm"><span x-text="hours"></span>h × @money($space->hourly_price)</span>
                                <span class="font-display text-2xl font-semibold" x-text="total"></span>
                            </div>
                        </div>
                    </template>
                </div>

                @auth
                    <form method="POST" action="{{ route('reservations.space', $space) }}" class="mt-5 space-y-3" @submit="busy = true">
                        @csrf
                        <input type="hidden" name="date" :value="date">
                        <input type="hidden" name="start_hour" :value="startHour">
                        <input type="hidden" name="end_hour" :value="endHour">
                        <label class="block">
                            <span class="field-label">Participantes</span>
                            <input type="number" name="guests" min="1" max="{{ $space->capacity }}" value="{{ min(10, $space->capacity) }}" class="field">
                        </label>
                        <textarea name="notes" rows="2" placeholder="Tipo de evento, montagem, coffee break… (opcional)" class="field resize-none"></textarea>
                        <button class="btn-gold w-full" :disabled="start === null || busy">
                            <span x-show="!busy">Reservar espaço</span>
                            <span x-show="busy" x-cloak>Reservando…</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-gold mt-5 w-full">Entrar para reservar</a>
                @endauth
            </div>
        </aside>
    </section>

    <section class="container-site mt-24">
        <h2 class="heading">Outros espaços</h2>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @foreach ($others as $other)
                <a href="{{ route('spaces.show', $other) }}" class="group card overflow-hidden">
                    <div class="aspect-[16/10] overflow-hidden"><img src="{{ $other->image_url }}" alt="" class="img-zoom h-full w-full object-cover"></div>
                    <div class="flex items-center justify-between p-5">
                        <span class="font-display text-xl">{{ $other->name }}</span>
                        <span class="text-sm font-semibold">@money($other->hourly_price)/h</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.site>
