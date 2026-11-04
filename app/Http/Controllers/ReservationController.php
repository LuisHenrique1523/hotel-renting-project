<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Http\Requests\StoreRoomReservationRequest;
use App\Http\Requests\StoreServiceReservationRequest;
use App\Http\Requests\StoreSpaceReservationRequest;
use App\Mail\ReservationStatusMail;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Space;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Fluxo do hóspede: reservar → pagar → acompanhar → cancelar.
 * Toda regra de negócio está no ReservationService; aqui só orquestramos HTTP.
 */
class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $engine) {}

    public function index(Request $request): View
    {
        $all = $request->user()->reservations()
            ->with(['reservable' => fn ($morph) => $morph->morphWith([\App\Models\Room::class => ['type'], \App\Models\Professional::class => ['user']]), 'service'])
            ->orderBy('starts_at')
            ->get();

        return view('site.reservations.index', [
            'upcoming' => $all->filter(fn ($r) => $r->status->isActive() && $r->ends_at->isFuture()),
            'past' => $all->filter(fn ($r) => $r->status === ReservationStatus::Completed || ($r->status->isActive() && $r->ends_at->isPast()))->reverse(),
            'cancelled' => $all->filter(fn ($r) => in_array($r->status, [ReservationStatus::Cancelled, ReservationStatus::NoShow], true))->reverse(),
        ]);
    }

    public function storeRoom(StoreRoomReservationRequest $request, RoomType $roomType): RedirectResponse
    {
        $reservation = $this->engine->bookRoom(
            $request->user(), $roomType,
            CarbonImmutable::parse($request->validated('check_in')),
            CarbonImmutable::parse($request->validated('check_out')),
            (int) $request->validated('guests'),
            $request->validated('notes'),
        );

        return to_route('reservations.checkout', $reservation);
    }

    public function storeSpace(StoreSpaceReservationRequest $request, Space $space): RedirectResponse
    {
        $day = CarbonImmutable::parse($request->validated('date'))->startOfDay();

        $reservation = $this->engine->bookSpace(
            $request->user(), $space,
            $day->addHours((int) $request->validated('start_hour')),
            $day->addHours((int) $request->validated('end_hour')),
            (int) $request->validated('guests'),
            $request->validated('notes'),
        );

        return to_route('reservations.checkout', $reservation);
    }

    public function storeService(StoreServiceReservationRequest $request, Service $service): RedirectResponse
    {
        $professional = $service->professionals()->findOrFail($request->validated('professional_id'));

        $reservation = $this->engine->bookService(
            $request->user(), $professional, $service,
            CarbonImmutable::parse($request->validated('starts_at'))->setTimezone(config('app.timezone')),
            $request->validated('notes'),
        );

        return to_route('reservations.checkout', $reservation);
    }

    public function checkout(Reservation $reservation): View|RedirectResponse
    {
        Gate::authorize('pay', $reservation);

        if ($reservation->status !== ReservationStatus::Pending) {
            return to_route('reservations.show', $reservation);
        }

        $expiresAt = $reservation->created_at->addMinutes((int) config('hotel.pending_expiration_minutes'));

        return view('site.reservations.checkout', compact('reservation', 'expiresAt'));
    }

    public function pay(Request $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('pay', $reservation);

        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)->only([PaymentMethod::Pix, PaymentMethod::Card])],
        ]);

        $this->engine->pay($reservation, PaymentMethod::from($data['method']));

        return to_route('reservations.show', $reservation)
            ->with('success', 'Pagamento aprovado! Sua reserva está confirmada.')
            ->with('celebrate', true);
    }

    public function show(Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);

        $reservation->load('reservable', 'service', 'payment', 'user');

        return view('site.reservations.show', compact('reservation'));
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('cancel', $reservation);

        $this->engine->cancel($reservation, $request->user());

        return to_route('reservations.show', $reservation)->with('success', 'Reserva cancelada. O horário foi liberado.');
    }

    /** Pré-visualização do e-mail enviado ao hóspede (útil para a demonstração). */
    public function emailPreview(Reservation $reservation): ReservationStatusMail
    {
        Gate::authorize('view', $reservation);

        return new ReservationStatusMail($reservation->load('user', 'reservable', 'service'),
            $reservation->status === ReservationStatus::Cancelled ? 'cancelled' : 'confirmed');
    }
}
