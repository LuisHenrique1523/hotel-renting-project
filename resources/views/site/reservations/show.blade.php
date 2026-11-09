<x-layouts.site :title="'Reserva '.$reservation->code">
    @if (session('celebrate'))
        @push('scripts')
            <script>
                window.addEventListener('load', () => {
                    const colors = ['#C8A15A', '#E9DCC9', '#0F1E33', '#DCBA74'];
                    confetti({ particleCount: 140, spread: 80, origin: { y: 0.35 }, colors });
                    setTimeout(() => confetti({ particleCount: 80, angle: 60, spread: 60, origin: { x: 0 }, colors }), 300);
                    setTimeout(() => confetti({ particleCount: 80, angle: 120, spread: 60, origin: { x: 1 }, colors }), 500);
                });
            </script>
        @endpush
    @endif

    @php
        $status = $reservation->status;
        $isOwner = $reservation->user_id === auth()->id();
    @endphp

    <section class="container-site max-w-4xl pt-10">
        <a href="{{ route('reservations.index') }}" class="no-print text-sm text-night-500 hover:text-night-900">← Minhas reservas</a>

        @if (session('celebrate'))
            <div class="mt-6 text-center">
                <p class="text-5xl">🎉</p>
                <h1 class="mt-3 font-display text-4xl font-semibold">Tudo certo, {{ strtok(auth()->user()->name, ' ') }}!</h1>
                <p class="mt-2 text-night-500">Enviamos a confirmação para <span class="font-medium text-night-800">{{ $reservation->user->email }}</span>.</p>
            </div>
        @endif

        {{-- ── Voucher ─────────────────────────────────────── --}}
        <article class="mt-8 overflow-hidden rounded-[2rem] bg-white shadow-soft">
            <div class="relative h-48 sm:h-56">
                <img src="{{ $reservation->image_url }}" alt="" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-night-950/90 via-night-950/40 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-4 p-6 sm:p-8">
                    <div>
                        <p class="eyebrow !text-gold-300">{{ $reservation->kind_label }}</p>
                        <h2 class="mt-1 font-display text-3xl text-white sm:text-4xl">{{ $reservation->title }}</h2>
                        <p class="text-sand-200">{{ $reservation->subtitle }}</p>
                    </div>
                    <span class="badge shrink-0 ring-1 {{ $status->badgeClasses() }}">{{ $status->getLabel() }}</span>
                </div>
            </div>

            <div class="grid gap-8 p-6 sm:grid-cols-[1fr_auto] sm:p-8">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-5 text-sm">
                    @if ($reservation->billing_unit === \App\Enums\BillingUnit::Hour)
                        <div><dt class="field-label">Data</dt><dd class="font-display text-xl capitalize">{{ $reservation->starts_at->translatedFormat('D, d M Y') }}</dd></div>
                        <div><dt class="field-label">Horário</dt><dd class="font-display text-xl">{{ $reservation->starts_at->format('H:i') }} – {{ $reservation->ends_at->format('H:i') }}</dd></div>
                    @else
                        <div><dt class="field-label">Início</dt><dd class="font-display text-xl capitalize">{{ $reservation->starts_at->translatedFormat('D, d M Y') }}</dd><dd class="text-night-500">a partir das {{ $reservation->starts_at->format('H\hi') }}</dd></div>
                        <div><dt class="field-label">Fim</dt><dd class="font-display text-xl capitalize">{{ $reservation->ends_at->translatedFormat('D, d M Y') }}</dd><dd class="text-night-500">até {{ $reservation->ends_at->format('H\hi') }} · {{ $reservation->quantity_label }}</dd></div>
                    @endif
                    <div class="col-span-2"><dt class="field-label">Endereço</dt><dd class="font-medium">{{ $reservation->space?->unit?->full_address }}</dd></div>
                    <div><dt class="field-label">Titular</dt><dd class="font-medium">{{ $reservation->user->name }}</dd></div>
                    <div><dt class="field-label">Pessoas</dt><dd class="font-medium">{{ $reservation->guests }}</dd></div>
                    <div><dt class="field-label">Pagamento</dt>
                        <dd class="font-medium">{{ $reservation->payment?->method?->getLabel() ?? '—' }}
                            @if ($reservation->payment)<span class="text-night-400">· {{ $reservation->payment->status->getLabel() }}</span>@endif
                        </dd></div>
                    <div><dt class="field-label">Total</dt><dd class="font-display text-2xl font-semibold">@money($reservation->total)</dd></div>
                    @if ($reservation->notes)
                        <div class="col-span-2"><dt class="field-label">Observações</dt><dd class="italic text-night-600">“{{ $reservation->notes }}”</dd></div>
                    @endif
                </dl>

                <div class="flex flex-col items-center justify-center border-dashed border-night-100 sm:border-l sm:pl-8">
                    <x-fake-qr :seed="$reservation->code" class="w-32" />
                    <p class="mt-3 font-mono text-lg font-semibold tracking-widest">{{ $reservation->code }}</p>
                    <p class="text-xs text-night-400">Apresente na recepção</p>
                </div>
            </div>

            {{-- Linha do tempo do status --}}
            <div class="border-t border-night-50 bg-sand-50/60 px-6 py-5 sm:px-8">
                <ol class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-night-500">
                    <li>📝 Criada {{ $reservation->created_at->translatedFormat('d/m H:i') }}</li>
                    @if ($reservation->confirmed_at)<li>✅ Confirmada {{ $reservation->confirmed_at->translatedFormat('d/m H:i') }}</li>@endif
                    @if ($reservation->checked_in_at)<li>🔑 Check-in {{ $reservation->checked_in_at->translatedFormat('d/m H:i') }}</li>@endif
                    @if ($reservation->checked_out_at)<li>🏁 Concluída {{ $reservation->checked_out_at->translatedFormat('d/m H:i') }}</li>@endif
                    @if ($reservation->cancelled_at)<li>✖ {{ $status->getLabel() }} {{ $reservation->cancelled_at->translatedFormat('d/m H:i') }}</li>@endif
                </ol>
            </div>
        </article>

        <div class="no-print mt-6 flex flex-wrap items-center gap-3">
            @if ($status === \App\Enums\ReservationStatus::Pending && $isOwner)
                <a href="{{ route('reservations.checkout', $reservation) }}" class="btn-gold">Concluir pagamento</a>
            @endif
            <button onclick="window.print()" class="btn-ghost">🖨 Imprimir voucher</button>
            <a href="{{ route('reservations.email', $reservation) }}" target="_blank" class="btn-ghost">✉ Ver e-mail enviado</a>

            @if ($isOwner && $status->isActive())
                <div class="ml-auto">
                    @if ($reservation->canBeCancelledByGuest())
                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}"
                              onsubmit="return confirm('Tem certeza que deseja cancelar esta reserva? O período será liberado.')">
                            @csrf
                            <button class="btn !px-4 text-rose-600 hover:bg-rose-50">Cancelar reserva</button>
                        </form>
                    @else
                        <p class="text-xs text-night-400">Cancelamento online indisponível (menos de {{ config('aurora.guest_cancellation_hours') }}h). Fale com a recepção da unidade.</p>
                    @endif
                </div>
            @endif
        </div>
    </section>
</x-layouts.site>
