<x-layouts.site title="Minhas reservas">
    <section class="container-site max-w-5xl pt-12" x-data="{ tab: 'upcoming' }">
        <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
            <div>
                <p class="eyebrow">Olá, {{ strtok(auth()->user()->name, ' ') }}</p>
                <h1 class="heading mt-2">Minhas reservas</h1>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('spaces.index') }}" class="btn-dark !px-4 !py-2.5">+ Nova reserva</a>
                <a href="{{ route('units.index') }}" class="btn-ghost !px-4 !py-2.5">Unidades</a>
            </div>
        </div>

        @php
            $tabs = ['upcoming' => ['Próximas', $upcoming], 'past' => ['Anteriores', $past], 'cancelled' => ['Canceladas', $cancelled]];
        @endphp

        <div class="mt-10 flex gap-1 rounded-full bg-white p-1 shadow-sm sm:inline-flex">
            @foreach ($tabs as $key => [$label, $items])
                <button @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-night-900 text-white' : 'text-night-600 hover:bg-sand-50'"
                        class="flex-1 whitespace-nowrap rounded-full px-5 py-2 text-sm font-semibold transition">
                    {{ $label }} <span class="ml-1 opacity-60">{{ $items->count() }}</span>
                </button>
            @endforeach
        </div>

        @foreach ($tabs as $key => [$label, $items])
            <div x-show="tab === '{{ $key }}'" @if ($key !== 'upcoming') x-cloak @endif class="mt-6 space-y-4">
                @forelse ($items as $r)
                    <a href="{{ $r->status === \App\Enums\ReservationStatus::Pending ? route('reservations.checkout', $r) : route('reservations.show', $r) }}"
                       class="group card flex flex-col gap-4 p-4 transition hover:-translate-y-0.5 hover:shadow-lg sm:flex-row sm:items-center sm:p-5">
                        <div class="flex-1"><x-reservation-summary :reservation="$r" /></div>
                        <div class="flex items-center justify-between gap-6 sm:flex-col sm:items-end sm:justify-center">
                            <span class="badge {{ $r->status->badgeClasses() }}">{{ $r->status->getLabel() }}</span>
                            <span class="font-display text-xl font-semibold">@money($r->total)</span>
                            <span class="font-mono text-xs text-night-400">{{ $r->code }}</span>
                        </div>
                    </a>
                @empty
                    <div class="card flex flex-col items-center p-14 text-center">
                        <span class="text-5xl">🗓️</span>
                        <p class="mt-4 font-display text-2xl">Nada por aqui ainda</p>
                        <p class="mt-1 text-night-500">Reserve uma sala, um dia de coworking ou um escritório.</p>
                        <a href="{{ route('spaces.index') }}" class="btn-gold mt-6">Explorar espaços</a>
                    </div>
                @endforelse
            </div>
        @endforeach
    </section>
</x-layouts.site>
