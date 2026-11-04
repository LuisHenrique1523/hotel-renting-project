@props(['checkIn' => null, 'checkOut' => null, 'guests' => 2, 'glass' => false])

@php
    $checkIn = $checkIn ?? today()->addDay();
    $checkOut = $checkOut ?? today()->addDays(3);
@endphp

<form action="{{ route('rooms.index') }}" method="GET"
      x-data="{ ci: '{{ $checkIn->toDateString() }}', co: '{{ $checkOut->toDateString() }}' }"
      x-effect="if (co <= ci) { const d = new Date(ci + 'T12:00:00'); d.setDate(d.getDate() + 1); co = d.toISOString().slice(0, 10) }"
      @class([
          'grid gap-3 rounded-3xl p-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_0.8fr_auto] lg:items-end',
          'border border-white/20 bg-white/10 shadow-2xl backdrop-blur-xl' => $glass,
          'card' => ! $glass,
      ])>
    <label class="block rounded-2xl {{ $glass ? 'bg-white/95' : 'bg-sand-50' }} px-4 py-2.5">
        <span class="field-label !text-night-400">Check-in</span>
        <input type="date" name="check_in" x-model="ci" min="{{ today()->toDateString() }}"
               class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-night-900 focus:ring-0">
    </label>
    <label class="block rounded-2xl {{ $glass ? 'bg-white/95' : 'bg-sand-50' }} px-4 py-2.5">
        <span class="field-label !text-night-400">Check-out</span>
        <input type="date" name="check_out" x-model="co" :min="ci"
               class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-night-900 focus:ring-0">
    </label>
    <label class="block rounded-2xl {{ $glass ? 'bg-white/95' : 'bg-sand-50' }} px-4 py-2.5">
        <span class="field-label !text-night-400">Hóspedes</span>
        <select name="guests" class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-night-900 focus:ring-0">
            @foreach (range(1, 4) as $n)
                <option value="{{ $n }}" @selected($guests == $n)>{{ $n }} {{ $n === 1 ? 'adulto' : 'adultos' }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn-gold h-full min-h-[3.5rem] !rounded-2xl sm:col-span-2 lg:col-span-1">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
        Ver disponibilidade
    </button>
</form>
