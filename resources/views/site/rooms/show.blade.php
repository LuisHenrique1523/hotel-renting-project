<x-layouts.site :title="$type->name">
    <div class="container-site pt-8">
        <a href="{{ route('rooms.index', ['check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString(), 'guests' => $guests]) }}"
           class="text-sm text-night-500 hover:text-night-900">← Voltar para a busca</a>
    </div>

    <section class="container-site mt-6 grid gap-12 lg:grid-cols-[1fr_24rem]">
        <div>
            <div class="relative aspect-[16/10] overflow-hidden rounded-[2rem] shadow-soft">
                <img src="{{ $type->cover_url }}" alt="{{ $type->name }}" class="h-full w-full animate-slow-zoom object-cover">
            </div>

            <p class="eyebrow mt-10">{{ $type->size_m2 }} m² · {{ $type->bed }} · até {{ $type->capacity }} hóspedes</p>
            <h1 class="mt-3 font-display text-4xl font-semibold sm:text-5xl">{{ $type->name }}</h1>
            <p class="mt-2 text-lg text-gold-700">{{ $type->tagline }}</p>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-night-600">{{ $type->description }}</p>

            <h2 class="mt-12 font-display text-2xl">O que está incluso</h2>
            <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($type->amenities ?? [] as $amenity)
                    <li class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-sm">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-gold-100 text-gold-700">✓</span>
                        <span class="text-sm font-medium">{{ $amenity }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-12 grid gap-4 rounded-3xl bg-night-900 p-8 text-sand-100 sm:grid-cols-3">
                <div><p class="text-xs uppercase tracking-wider text-night-300">Check-in</p><p class="mt-1 font-display text-2xl">a partir das {{ config('hotel.check_in_hour') }}h</p></div>
                <div><p class="text-xs uppercase tracking-wider text-night-300">Check-out</p><p class="mt-1 font-display text-2xl">até {{ config('hotel.check_out_hour') }}h</p></div>
                <div><p class="text-xs uppercase tracking-wider text-night-300">Cancelamento</p><p class="mt-1 font-display text-2xl">grátis até 24h</p></div>
            </div>
        </div>

        {{-- ── Card de reserva ─────────────────────────────── --}}
        <aside>
            <div class="card sticky top-28 p-6">
                <div class="flex items-baseline justify-between">
                    <p><span class="font-display text-3xl font-semibold">@money($type->base_price)</span> <span class="text-sm text-night-400">/ diária</span></p>
                </div>

                <form method="GET" action="{{ route('rooms.show', $type) }}" class="mt-5 grid grid-cols-2 gap-2"
                      x-data="{ ci: '{{ $checkIn->toDateString() }}', co: '{{ $checkOut->toDateString() }}' }">
                    <label class="rounded-xl border border-night-100 px-3 py-2">
                        <span class="block text-[0.65rem] font-semibold uppercase tracking-wider text-night-400">Check-in</span>
                        <input type="date" name="check_in" x-model="ci" min="{{ today()->toDateString() }}" @change="$el.form.submit()"
                               class="w-full border-0 p-0 text-sm font-semibold focus:ring-0">
                    </label>
                    <label class="rounded-xl border border-night-100 px-3 py-2">
                        <span class="block text-[0.65rem] font-semibold uppercase tracking-wider text-night-400">Check-out</span>
                        <input type="date" name="check_out" x-model="co" :min="ci" @change="$el.form.submit()"
                               class="w-full border-0 p-0 text-sm font-semibold focus:ring-0">
                    </label>
                    <label class="col-span-2 rounded-xl border border-night-100 px-3 py-2">
                        <span class="block text-[0.65rem] font-semibold uppercase tracking-wider text-night-400">Hóspedes</span>
                        <select name="guests" onchange="this.form.submit()" class="w-full border-0 p-0 text-sm font-semibold focus:ring-0">
                            @foreach (range(1, $type->capacity) as $n)
                                <option value="{{ $n }}" @selected($guests == $n)>{{ $n }} {{ $n === 1 ? 'hóspede' : 'hóspedes' }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>

                <div class="mt-5 space-y-2 text-sm">
                    @foreach (collect($quote['items'])->take(6) as $night)
                        <div class="flex justify-between text-night-600">
                            <span>{{ $night['date']->translatedFormat('D, d/m') }}
                                @if ($night['weekend'])<span class="ml-1 rounded bg-gold-100 px-1.5 py-0.5 text-[0.65rem] font-semibold text-gold-800">fim de semana</span>@endif
                            </span>
                            <span>@money($night['price'])</span>
                        </div>
                    @endforeach
                    @if ($quote['nights'] > 6)
                        <p class="text-xs text-night-400">+ {{ $quote['nights'] - 6 }} diárias…</p>
                    @endif
                </div>

                <div class="mt-4 flex items-baseline justify-between border-t border-night-100 pt-4">
                    <span class="font-semibold">Total · {{ $quote['nights'] }} {{ $quote['nights'] === 1 ? 'diária' : 'diárias' }}</span>
                    <span class="font-display text-2xl font-semibold">@money($quote['total'])</span>
                </div>

                <div class="mt-5">
                    @if ($free === 0)
                        <div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-800">
                            Esgotado nestas datas. Altere as datas acima ou
                            <a class="font-semibold underline" href="{{ route('rooms.index', ['check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString()]) }}">veja outras categorias</a>.
                        </div>
                    @else
                        @auth
                            <form method="POST" action="{{ route('reservations.room', $type) }}" x-data="{ busy: false }" @submit="busy = true">
                                @csrf
                                <input type="hidden" name="check_in" value="{{ $checkIn->toDateString() }}">
                                <input type="hidden" name="check_out" value="{{ $checkOut->toDateString() }}">
                                <input type="hidden" name="guests" value="{{ $guests }}">
                                <textarea name="notes" rows="2" placeholder="Algum pedido especial? (opcional)" class="field mb-3 resize-none">{{ old('notes') }}</textarea>
                                <button class="btn-gold w-full" :disabled="busy">
                                    <span x-show="!busy">Reservar agora</span>
                                    <span x-show="busy" x-cloak>Garantindo seu quarto…</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="btn-gold w-full">Entrar para reservar</a>
                            <p class="mt-2 text-center text-xs text-night-400">Novo por aqui? <a href="{{ route('register') }}" class="font-semibold text-gold-700">Crie sua conta</a></p>
                        @endauth
                        <p class="mt-3 text-center text-xs {{ $free <= 2 ? 'font-semibold text-amber-700' : 'text-night-400' }}">
                            {{ $free <= 2 ? "Restam apenas {$free} nessas datas" : "{$free} unidades livres nessas datas" }}
                        </p>
                    @endif
                </div>
            </div>
        </aside>
    </section>

    <section class="container-site mt-24">
        <h2 class="heading">Outras acomodações</h2>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @foreach ($others as $other)
                <a href="{{ route('rooms.show', [$other, 'check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString()]) }}" class="group card overflow-hidden">
                    <div class="aspect-[16/10] overflow-hidden"><img src="{{ $other->cover_url }}" alt="" class="img-zoom h-full w-full object-cover"></div>
                    <div class="flex items-center justify-between p-5">
                        <span class="font-display text-xl">{{ $other->name }}</span>
                        <span class="text-sm font-semibold">@money($other->base_price)</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.site>
