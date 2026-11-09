@php
    $category = $space->category;
    $unit = $space->unit;
    $billing = $space->billing_unit;
    $words = match ($billing) {
        \App\Enums\BillingUnit::Hour => ['hora', 'horas'],
        \App\Enums\BillingUnit::Day => ['dia', 'dias'],
        \App\Enums\BillingUnit::Month => ['mês', 'meses'],
    };
    $config = [
        'billing' => $billing->value,
        'price' => (float) $space->price,
        'shared' => $space->is_shared,
        'min' => $category->min_quantity,
        'max' => $category->max_quantity,
        'capacity' => $space->capacity,
        'units' => $words,
        'hoursUrl' => route('availability.hours', $space),
        'daysUrl' => route('availability.days', $space),
        'quoteUrl' => route('availability.quote', $space),
    ];
@endphp

<x-layouts.site :title="$space->name">
    <div class="container-site pt-8 text-sm text-night-500">
        <a href="{{ route('spaces.index') }}" class="hover:text-night-900">Espaços</a>
        <span class="mx-1">/</span>
        <a href="{{ route('units.show', $unit) }}" class="hover:text-night-900">{{ $unit->name }}</a>
        <span class="mx-1">/</span>
        <span class="text-night-900">{{ $space->name }}</span>
    </div>

    <section class="container-site mt-6">
        <div class="relative h-72 overflow-hidden rounded-[2rem] shadow-soft sm:h-96">
            <img src="{{ $space->image_url }}" alt="{{ $space->name }}" class="h-full w-full animate-slow-zoom object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-night-950/80 to-transparent"></div>
            <div class="absolute bottom-0 p-8 text-white">
                <p class="eyebrow !text-gold-300">{{ $category->name }} · {{ $unit->name }}</p>
                <h1 class="mt-2 font-display text-4xl font-semibold sm:text-5xl">{{ $space->name }}</h1>
            </div>
        </div>
    </section>

    <section class="container-site mt-10 grid gap-10 lg:grid-cols-[1fr_26rem]" x-data="spaceBooking(@js($config))">
        <div class="min-w-0">
            <dl class="grid grid-cols-2 gap-4 rounded-3xl bg-night-900 p-6 text-sand-100 sm:grid-cols-4">
                <div><dt class="text-xs uppercase tracking-wider text-night-300">{{ $space->is_shared ? 'Estações' : 'Capacidade' }}</dt><dd class="mt-1 font-display text-xl">{{ $space->capacity }} {{ $space->is_shared ? '' : 'pessoas' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wider text-night-300">Área</dt><dd class="mt-1 font-display text-xl">{{ $space->size_m2 ? $space->size_m2.' m²' : '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wider text-night-300">Cobrança</dt><dd class="mt-1 font-display text-xl">{{ $billing->getLabel() }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wider text-night-300">Funcionamento</dt><dd class="mt-1 font-display text-xl">{{ $unit->hours_label }}</dd></div>
            </dl>

            <p class="mt-8 text-lg leading-relaxed text-night-600">{{ $space->description }}</p>
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($space->amenities ?? [] as $amenity)
                    <span class="chip !px-4 !py-2 !text-sm">✓ {{ $amenity }}</span>
                @endforeach
                @foreach ($unit->amenities ?? [] as $amenity)
                    <span class="chip !bg-white !px-4 !py-2 !text-sm ring-1 ring-night-100">{{ $amenity }}</span>
                @endforeach
            </div>

            {{-- ── Agenda: por hora ─────────────────────────── --}}
            <template x-if="billing === 'hour'">
                <div class="card mt-10 p-6 sm:p-8">
                    <h2 class="font-display text-2xl">Agenda do espaço</h2>
                    <p class="mt-1 text-sm text-night-500">
                        Clique no horário de início e depois no horário final.
                        @if ($category->min_quantity > 1) Mínimo de {{ $billing->quantityLabel($category->min_quantity) }}. @endif
                    </p>

                    <div class="-mx-2 mt-6 flex gap-2 overflow-x-auto px-2 pb-2">
                        <template x-for="d in strip" :key="d.iso">
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
                        <div x-show="!loading" class="grid grid-cols-3 gap-2 sm:grid-cols-5">
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
                        <p x-show="!loading && slots.length === 0" class="text-sm text-night-500">Sem horários neste dia.</p>
                    </div>
                </div>
            </template>

            {{-- ── Agenda: por dia ou por mês ───────────────── --}}
            <template x-if="billing !== 'hour'">
                <div class="card mt-10 p-6 sm:p-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="font-display text-2xl">{{ $billing === \App\Enums\BillingUnit::Month ? 'Data de início do contrato' : 'Escolha o primeiro dia' }}</h2>
                            <p class="mt-1 text-sm text-night-500">
                                {{ $space->is_shared ? 'Os números mostram as vagas livres em cada dia.' : 'Dias riscados já estão reservados.' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="shiftMonth(-1)" :disabled="!canPrev()" class="grid h-9 w-9 place-items-center rounded-full border border-night-100 disabled:opacity-30">‹</button>
                            <span class="min-w-[9rem] text-center text-sm font-semibold" x-text="monthLabel"></span>
                            <button type="button" @click="shiftMonth(1)" :disabled="!canNext()" class="grid h-9 w-9 place-items-center rounded-full border border-night-100 disabled:opacity-30">›</button>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-7 gap-1.5 text-center text-[0.65rem] font-semibold uppercase tracking-wider text-night-400">
                        @foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $wd)<span>{{ $wd }}</span>@endforeach
                    </div>
                    <div x-show="loading" class="mt-2 h-64 animate-pulse rounded-2xl bg-night-50"></div>
                    <div x-show="!loading" class="mt-2 grid grid-cols-7 gap-1.5">
                        <template x-for="(cell, i) in grid" :key="i">
                            <div>
                                <template x-if="cell">
                                    <button type="button" @click="pickStart(cell.iso)" :disabled="!cell.info || !cell.info.available"
                                            :class="inRange(cell.iso) ? 'border-gold-500 bg-gold-500 text-night-950'
                                                : (cell.info && cell.info.available ? 'border-night-100 bg-white hover:border-gold-400'
                                                    : (cell.info && cell.info.reason === 'busy' ? 'cursor-not-allowed border-transparent bg-night-50 text-night-300 line-through' : 'cursor-not-allowed border-transparent text-night-200'))"
                                            class="flex aspect-square w-full flex-col items-center justify-center rounded-xl border text-sm font-semibold transition">
                                        <span x-text="cell.day"></span>
                                        <span x-show="{{ $space->is_shared ? 'true' : 'false' }} && cell.info && cell.info.available" class="text-[0.6rem] font-medium opacity-70" x-text="cell.info ? cell.info.left + ' vagas' : ''"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- ── Card de reserva ─────────────────────────────── --}}
        <aside>
            <div class="card sticky top-28 p-6">
                <p>
                    <span class="font-display text-3xl font-semibold">@money($space->price)</span>
                    <span class="text-sm text-night-400">/ {{ $words[0] }}{{ $space->is_shared ? ' por pessoa' : '' }}</span>
                </p>
                <p class="mt-1 text-sm text-night-500">{{ $unit->full_address }}</p>

                @auth
                    <form method="POST" action="{{ route('reservations.store', $space) }}" class="mt-6 space-y-4" @submit="busy = true">
                        @csrf
                        <input type="hidden" name="date" :value="date">
                        <input type="hidden" name="start_hour" :value="billing === 'hour' ? startHour : ''">
                        <input type="hidden" name="quantity" :value="submitQuantity">

                        <div class="grid grid-cols-2 gap-3">
                            <label x-show="billing !== 'hour'" class="block">
                                <span class="field-label">Duração ({{ $words[1] }})</span>
                                <input type="number" x-model.number="quantity" min="{{ $category->min_quantity }}" max="{{ $category->max_quantity }}" class="field">
                            </label>
                            <label class="block" :class="billing === 'hour' && 'col-span-2'">
                                <span class="field-label">{{ $space->is_shared ? 'Vagas (pessoas)' : 'Pessoas' }}</span>
                                <input type="number" name="guests" x-model.number="guests" min="1" max="{{ $space->capacity }}" class="field">
                            </label>
                        </div>

                        <div class="rounded-2xl bg-sand-50 p-5">
                            {{-- resumo por hora --}}
                            <template x-if="billing === 'hour'">
                                <div>
                                    <p x-show="hourProblem()" class="text-sm text-night-500" x-text="hourProblem()"></p>
                                    <div x-show="!hourProblem()">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-night-400">Sua reserva</p>
                                        <p class="mt-1 font-semibold" x-text="hourSummary()"></p>
                                    </div>
                                    <div x-show="start !== null" class="mt-4 flex items-baseline justify-between border-t border-night-100 pt-4">
                                        <span class="text-sm" x-text="unitLabel(hours) + ' × @money($space->price)' + ({{ $space->is_shared ? 'true' : 'false' }} ? ' × ' + guests : '')"></span>
                                        <span class="font-display text-2xl font-semibold" x-text="total"></span>
                                    </div>
                                </div>
                            </template>
                            {{-- resumo por dia / mês --}}
                            <template x-if="billing !== 'hour'">
                                <div>
                                    <p x-show="!date" class="text-sm text-night-500">Escolha a data de início no calendário.</p>
                                    <div x-show="date">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-night-400">Sua reserva</p>
                                        <p class="mt-1 font-semibold" x-text="quote ? quote.period : '…'"></p>
                                        <p x-show="quote && !quote.available" class="mt-2 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">
                                            Esse período já está ocupado{{ $space->is_shared ? ' ou não tem vagas suficientes' : '' }}. Escolha outra data ou outra duração.
                                        </p>
                                        <p x-show="quote && quote.available && quote.seats_left !== null" class="mt-1 text-xs text-night-500" x-text="quote ? quote.seats_left + ' vagas livres no período' : ''"></p>
                                        <div class="mt-4 flex items-baseline justify-between border-t border-night-100 pt-4">
                                            <span class="text-sm" x-text="unitLabel(quantity) + ' × @money($space->price)' + ({{ $space->is_shared ? 'true' : 'false' }} ? ' × ' + guests : '')"></span>
                                            <span class="font-display text-2xl font-semibold" :class="quoting && 'opacity-40'" x-text="total"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <textarea name="notes" rows="2" placeholder="Algum pedido? Ex.: coffee break, layout da sala (opcional)" class="field resize-none"></textarea>
                        <button class="btn-gold w-full" :disabled="!ready">
                            <span x-show="!busy">{{ $billing === \App\Enums\BillingUnit::Month ? 'Contratar escritório' : 'Reservar agora' }}</span>
                            <span x-show="busy" x-cloak>Reservando…</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-gold mt-6 w-full">Entrar para reservar</a>
                    <p class="mt-2 text-center text-xs text-night-400">Novo por aqui? <a href="{{ route('register') }}" class="font-semibold text-gold-700">Crie sua conta</a></p>
                @endauth

                <p class="mt-4 text-center text-xs text-night-400">Cancelamento grátis até {{ config('aurora.guest_cancellation_hours') }}h antes do início.</p>
            </div>
        </aside>
    </section>

    @if ($others->isNotEmpty())
        <section class="container-site mt-24">
            <h2 class="heading">Mais espaços na {{ $unit->name }}</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($others as $other)
                    <x-space-card :space="$other" :show-unit="false" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
