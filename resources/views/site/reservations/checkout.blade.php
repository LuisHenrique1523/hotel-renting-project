<x-layouts.site title="Pagamento">
    <section class="container-site max-w-5xl pt-10">
        {{-- Etapas --}}
        <ol class="flex items-center gap-3 text-sm">
            <li class="flex items-center gap-2 text-night-400"><span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-500 text-xs text-white">✓</span> Escolha</li>
            <li class="h-px w-8 bg-night-200"></li>
            <li class="flex items-center gap-2 font-semibold"><span class="grid h-6 w-6 place-items-center rounded-full bg-night-900 text-xs text-gold-300">2</span> Pagamento</li>
            <li class="h-px w-8 bg-night-200"></li>
            <li class="flex items-center gap-2 text-night-400"><span class="grid h-6 w-6 place-items-center rounded-full border border-night-200 text-xs">3</span> Confirmação</li>
        </ol>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]"
             x-data="{
                method: 'pix', busy: false,
                left: {{ (int) max(0, now()->diffInSeconds($expiresAt, false)) }},
                get clock() { const m = Math.floor(this.left / 60), s = this.left % 60; return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0') },
             }"
             x-init="setInterval(() => left > 0 && left--, 1000)">

            <div class="card p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h1 class="font-display text-3xl">Finalize seu pagamento</h1>
                    <span class="badge bg-amber-50 text-amber-800 ring-amber-200" :class="left < 120 && 'animate-pulse !bg-rose-50 !text-rose-700'">
                        ⏳ Horário reservado por <span class="font-mono" x-text="clock"></span>
                    </span>
                </div>
                <p class="mt-2 text-sm text-night-500">Seguramos este horário para você. Após o prazo, ele volta a ficar disponível para outros clientes.</p>

                <div class="mt-8 grid grid-cols-2 gap-3">
                    <button type="button" @click="method = 'pix'"
                            :class="method === 'pix' ? 'border-gold-500 bg-gold-50 ring-2 ring-gold-400' : 'border-night-100'"
                            class="flex items-center gap-3 rounded-2xl border bg-white p-4 text-left transition">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-100 text-lg">⚡</span>
                        <span><span class="block font-semibold">Pix</span><span class="text-xs text-night-500">Aprovação imediata</span></span>
                    </button>
                    <button type="button" @click="method = 'card'"
                            :class="method === 'card' ? 'border-gold-500 bg-gold-50 ring-2 ring-gold-400' : 'border-night-100'"
                            class="flex items-center gap-3 rounded-2xl border bg-white p-4 text-left transition">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-night-100 text-lg">💳</span>
                        <span><span class="block font-semibold">Cartão de crédito</span><span class="text-xs text-night-500">Em até 3x sem juros</span></span>
                    </button>
                </div>

                {{-- Pix --}}
                <div x-show="method === 'pix'" x-transition.opacity class="mt-8 grid items-center gap-8 sm:grid-cols-[12rem_1fr]">
                    <x-fake-qr :seed="$reservation->code" class="w-48 shadow-soft" />
                    <div>
                        <p class="font-semibold">Escaneie o QR Code com o app do seu banco</p>
                        <p class="mt-1 text-sm text-night-500">ou use o Pix copia e cola:</p>
                        <div class="mt-3 flex items-center gap-2 rounded-xl bg-sand-50 p-3" x-data="{ copied: false }">
                            <code class="truncate text-xs text-night-600">00020126580014BR.GOV.BCB.PIX0136aurora-{{ strtolower($reservation->code) }}5204000053039865406{{ number_format((float) $reservation->total, 2, '.', '') }}</code>
                            <button type="button" class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold shadow-sm"
                                    @click="navigator.clipboard?.writeText($el.previousElementSibling.innerText); copied = true; setTimeout(() => copied = false, 1500)"
                                    x-text="copied ? 'Copiado!' : 'Copiar'"></button>
                        </div>
                        <p class="mt-3 text-xs text-night-400">Ambiente de demonstração: nenhum valor real é cobrado.</p>
                    </div>
                </div>

                {{-- Cartão --}}
                <div x-show="method === 'card'" x-cloak x-transition.opacity class="mt-8 grid gap-4 sm:grid-cols-2">
                    <label class="sm:col-span-2"><span class="field-label">Número do cartão</span>
                        <input class="field font-mono" value="4242 4242 4242 4242" autocomplete="off"></label>
                    <label class="sm:col-span-2"><span class="field-label">Nome impresso</span>
                        <input class="field uppercase" value="{{ auth()->user()->name }}" autocomplete="off"></label>
                    <label><span class="field-label">Validade</span><input class="field font-mono" value="12/29" autocomplete="off"></label>
                    <label><span class="field-label">CVV</span><input class="field font-mono" value="123" autocomplete="off"></label>
                    <p class="text-xs text-night-400 sm:col-span-2">🔒 Ambiente de demonstração: os dados não são armazenados nem enviados.</p>
                </div>

                <form method="POST" action="{{ route('reservations.pay', $reservation) }}" class="mt-8" @submit="busy = true">
                    @csrf
                    <input type="hidden" name="method" :value="method">
                    <button class="btn-gold w-full !py-4 text-base" :disabled="busy || left === 0">
                        <span x-show="!busy && left > 0">
                            <span x-text="method === 'pix' ? 'Já paguei com Pix' : 'Pagar'"></span> · @money($reservation->total)
                        </span>
                        <span x-show="busy" x-cloak class="flex items-center gap-2">
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3"/></svg>
                            Processando pagamento…
                        </span>
                        <span x-show="left === 0" x-cloak>Prazo expirado: faça uma nova reserva</span>
                    </button>
                </form>
            </div>

            <aside class="space-y-4">
                <div class="card p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-night-400">Resumo · {{ $reservation->code }}</p>
                    <div class="mt-4"><x-reservation-summary :reservation="$reservation" /></div>
                    <dl class="mt-6 space-y-2 border-t border-night-50 pt-4 text-sm">
                        <div class="flex justify-between"><dt class="text-night-500">Pessoas</dt><dd>{{ $reservation->guests }}</dd></div>
                        <div class="flex justify-between"><dt class="text-night-500">{{ $reservation->quantity_label }} × @money($reservation->unit_price){{ $reservation->space?->is_shared && $reservation->guests > 1 ? ' × '.$reservation->guests.' pessoas' : '' }}</dt><dd>@money($reservation->total)</dd></div>
                        @if ($reservation->notes)
                            <div><dt class="text-night-500">Observações</dt><dd class="mt-1 italic">“{{ $reservation->notes }}”</dd></div>
                        @endif
                        <div class="flex items-baseline justify-between border-t border-night-50 pt-3">
                            <dt class="font-semibold">Total</dt><dd class="font-display text-2xl font-semibold">@money($reservation->total)</dd>
                        </div>
                    </dl>
                </div>
                <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" onsubmit="return confirm('Desistir desta reserva?')">
                    @csrf
                    <button class="w-full text-center text-sm text-night-400 hover:text-rose-600">Desistir da reserva</button>
                </form>
            </aside>
        </div>
    </section>
</x-layouts.site>
