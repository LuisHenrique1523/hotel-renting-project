<x-layouts.site :title="$service->name">
    <div class="container-site pt-8">
        <a href="{{ route('spa.index') }}" class="text-sm text-night-500 hover:text-night-900">← Todos os serviços</a>
    </div>

    <section class="container-site mt-6 grid gap-12 lg:grid-cols-[0.9fr_1.1fr]">
        {{-- ── Sobre o serviço ─────────────────────────────── --}}
        <div>
            <div class="aspect-[4/3] overflow-hidden rounded-[2rem] shadow-soft">
                <img src="{{ $service->image_url }}" alt="{{ $service->name }}" class="h-full w-full animate-slow-zoom object-cover">
            </div>
            <p class="eyebrow mt-8">{{ $service->category->getLabel() }}</p>
            <h1 class="mt-2 font-display text-4xl font-semibold sm:text-5xl">{{ $service->name }}</h1>
            <p class="mt-5 text-lg leading-relaxed text-night-600">{{ $service->description }}</p>
            <div class="mt-6 flex gap-3">
                <span class="chip !px-4 !py-2 !text-sm">⏱ {{ $service->duration_label }}</span>
                <span class="chip !px-4 !py-2 !text-sm">💳 @money($service->price)</span>
            </div>
        </div>

        {{-- ── Agendamento em 3 passos ─────────────────────── --}}
        <div class="min-w-0" x-data="serviceBooking({
                endpoint: '{{ route('availability.service', $service) }}',
                single: @js($professionals->count() === 1 ? ['id' => $professionals->first()->id, 'name' => $professionals->first()->name] : null),
             })">
            <div class="card p-6 sm:p-8">
                {{-- Passo 1: profissional --}}
                <div class="flex items-center gap-3">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-night-900 text-sm font-semibold text-gold-300">1</span>
                    <h2 class="font-display text-xl">Escolha o profissional</h2>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($professionals as $pro)
                        <button type="button" @click="pickPro({{ $pro->id }}, @js($pro->name))"
                                :class="pro === {{ $pro->id }} ? 'border-gold-500 bg-gold-50 ring-2 ring-gold-400' : 'border-night-100 hover:border-night-300'"
                                class="flex items-center gap-4 rounded-2xl border bg-white p-3 text-left transition">
                            <img src="{{ $pro->photo_url }}" alt="" class="h-14 w-14 rounded-full object-cover">
                            <span>
                                <span class="block font-semibold">{{ $pro->name }}</span>
                                <span class="block text-xs text-night-500">{{ $pro->specialty }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                {{-- Passo 2: dia --}}
                <div class="mt-8 flex items-center gap-3" :class="!pro && 'opacity-40'">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-night-900 text-sm font-semibold text-gold-300">2</span>
                    <h2 class="font-display text-xl">Escolha o dia</h2>
                </div>
                <div class="-mx-2 mt-4 flex gap-2 overflow-x-auto px-2 pb-2" :class="!pro && 'pointer-events-none opacity-40'">
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

                {{-- Passo 3: horário --}}
                <div class="mt-8 flex items-center gap-3" :class="!date && 'opacity-40'">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-night-900 text-sm font-semibold text-gold-300">3</span>
                    <h2 class="font-display text-xl">Escolha o horário</h2>
                </div>
                <div class="mt-4 min-h-[6rem]">
                    <div x-show="loading" class="grid grid-cols-4 gap-2 sm:grid-cols-5">
                        <template x-for="i in 10"><div class="h-11 animate-pulse rounded-xl bg-night-50"></div></template>
                    </div>
                    <p x-show="!loading && pro && date && slots.length === 0" x-cloak class="rounded-2xl bg-sand-100 p-5 text-sm text-night-600">
                        Sem expediente neste dia. Escolha outra data 🙂
                    </p>
                    <p x-show="!pro || !date" class="text-sm text-night-400">Selecione o profissional e o dia para ver os horários.</p>
                    <div x-show="!loading && slots.length" x-cloak class="grid grid-cols-4 gap-2 sm:grid-cols-5">
                        <template x-for="s in slots" :key="s.start">
                            <button type="button" :disabled="!s.available" @click="slot = s"
                                    :title="s.reason === 'busy' ? 'Horário ocupado' : (s.reason === 'past' ? 'Horário já passou' : 'Disponível')"
                                    :class="slot && slot.start === s.start ? 'border-gold-500 bg-gold-500 text-night-950 shadow-glow'
                                        : (s.available ? 'border-night-100 bg-white hover:border-gold-400 hover:text-gold-700' : 'cursor-not-allowed border-transparent bg-night-50 text-night-300 line-through')"
                                    class="h-11 rounded-xl border text-sm font-semibold transition" x-text="s.time"></button>
                        </template>
                    </div>
                    <div x-show="!loading && slots.length" x-cloak class="mt-4 flex gap-5 text-xs text-night-400">
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-night-200 bg-white"></span> Livre</span>
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-night-50"></span> Ocupado</span>
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-gold-500"></span> Selecionado</span>
                    </div>
                </div>

                {{-- Resumo --}}
                <div x-show="slot" x-cloak x-transition class="mt-8 rounded-2xl bg-night-900 p-5 text-sand-100">
                    <p class="text-xs uppercase tracking-wider text-night-300">Resumo</p>
                    <p class="mt-1 font-display text-xl text-white">{{ $service->name }} <span class="text-gold-300">com <span x-text="proName"></span></span></p>
                    <p class="mt-1 text-sm" x-text="slot ? summary() : ''"></p>
                    <p class="mt-3 font-display text-2xl text-gold-300">@money($service->price)</p>
                </div>

                <div class="mt-6">
                    @auth
                        <form method="POST" action="{{ route('reservations.service', $service) }}" @submit="busy = true">
                            @csrf
                            <input type="hidden" name="professional_id" :value="pro">
                            <input type="hidden" name="starts_at" :value="slot ? slot.start : ''">
                            <textarea name="notes" rows="2" x-show="slot" x-cloak placeholder="Alguma observação para o profissional? (opcional)" class="field mb-3 resize-none"></textarea>
                            <button class="btn-gold w-full" :disabled="!slot || busy">
                                <span x-show="!busy">Confirmar agendamento</span>
                                <span x-show="busy" x-cloak>Reservando horário…</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-gold w-full">Entrar para agendar</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="container-site mt-24">
            <h2 class="heading">Você também pode gostar</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @foreach ($related as $item)
                    <a href="{{ route('spa.show', $item) }}" class="group card overflow-hidden">
                        <div class="aspect-[16/10] overflow-hidden"><img src="{{ $item->image_url }}" alt="" class="img-zoom h-full w-full object-cover"></div>
                        <div class="flex items-center justify-between p-5">
                            <span class="font-display text-xl">{{ $item->name }}</span>
                            <span class="text-sm font-semibold">@money($item->price)</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
