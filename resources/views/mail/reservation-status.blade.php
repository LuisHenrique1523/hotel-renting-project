<x-mail::message>
@if ($kind === 'confirmed')
# Sua reserva está confirmada ✨

Olá, {{ $reservation->user->name }}! Tudo pronto para receber você no **{{ config('hotel.name') }}**.
@else
# Reserva cancelada

Olá, {{ $reservation->user->name }}. Sua reserva foi cancelada. Se houve pagamento, o estorno já foi solicitado.
@endif

<x-mail::panel>
**{{ $reservation->kind_label }}:** {{ $reservation->title }}<br>
{{ $reservation->subtitle }}<br>
**Quando:** {{ $reservation->period_label }}<br>
**Código:** {{ $reservation->code }}<br>
**Total:** R$ {{ number_format((float) $reservation->total, 2, ',', '.') }}
</x-mail::panel>

<x-mail::button :url="route('reservations.show', $reservation)" color="success">
Ver minha reserva
</x-mail::button>

@if ($kind === 'confirmed' && $reservation->kind === 'room')
Check-in a partir das {{ config('hotel.check_in_hour') }}h · Check-out até {{ config('hotel.check_out_hour') }}h.
@endif

Com carinho,<br>
Equipe {{ config('hotel.name') }}
</x-mail::message>
