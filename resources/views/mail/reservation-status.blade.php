<x-mail::message>
@if ($kind === 'confirmed')
# Sua reserva está confirmada ✨

Olá, {{ $reservation->user->name }}! Está tudo pronto para receber você na **{{ $reservation->space?->unit?->name ?? config('aurora.name') }}**.
@else
# Reserva cancelada

Olá, {{ $reservation->user->name }}. Sua reserva foi cancelada. Se houve pagamento, o estorno já foi solicitado.
@endif

<x-mail::panel>
**{{ $reservation->kind_label }}:** {{ $reservation->title }}<br>
{{ $reservation->space?->unit?->full_address }}<br>
**Quando:** {{ $reservation->period_label }}<br>
**Pessoas:** {{ $reservation->guests }}<br>
**Código:** {{ $reservation->code }}<br>
**Total:** R$ {{ number_format((float) $reservation->total, 2, ',', '.') }}
</x-mail::panel>

<x-mail::button :url="route('reservations.show', $reservation)" color="success">
Ver minha reserva
</x-mail::button>

@if ($kind === 'confirmed')
Apresente o código {{ $reservation->code }} na recepção. A unidade funciona das {{ $reservation->space?->unit?->hours_label }}.
@endif

Até breve,<br>
Equipe {{ config('aurora.name') }}
</x-mail::message>
