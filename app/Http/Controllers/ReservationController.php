<?php

namespace App\Http\Controllers;

use App\Enums\BillingUnit;
use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Http\Requests\StoreSpaceReservationRequest;
use App\Mail\ReservationStatusMail;
use App\Models\Reservation;
use App\Models\Space;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Fluxo do cliente: reservar → pagar → acompanhar → cancelar.
 * Toda regra de negócio está no ReservationService; aqui só orquestramos HTTP.
 */
class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $engine) {}

    public function index(Request $request): View
    {
        $all = $request->user()->reservations()
            ->with('space.unit', 'space.category')
            ->orderBy('starts_at')
            ->get();

        return view('site.reservations.index', [
            'upcoming' => $all->filter(fn ($r) => $r->status->isActive() && $r->ends_at->isFuture()),
            'past' => $all->filter(fn ($r) => $r->status === ReservationStatus::Completed || ($r->status->isActive() && $r->ends_at->isPast()))->reverse(),
            'cancelled' => $all->filter(fn ($r) => in_array($r->status, [ReservationStatus::Cancelled, ReservationStatus::NoShow], true))->reverse(),
        ]);
    }

    public function store(StoreSpaceReservationRequest $request, Space $space): RedirectResponse
    {
        $start = CarbonImmutable::parse($request->validated('date'))->startOfDay();

        if ($space->billing_unit === BillingUnit::Hour) {
            $start = $start->setTime((int) $request->validated('start_hour'), 0);
        }

        $reservation = $this->engine->book(
            $request->user(), $space, $start,
            (int) $request->validated('quantity'),
            (int) $request->validated('guests'),
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

        $reservation->load('space.unit', 'space.category');
        $expiresAt = $reservation->created_at->addMinutes((int) config('aurora.pending_expiration_minutes'));

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

        $reservation->load('space.unit', 'space.category', 'payment', 'user');

        return view('site.reservations.show', compact('reservation'));
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('cancel', $reservation);

        $this->engine->cancel($reservation, $request->user());

        return to_route('reservations.show', $reservation)->with('success', 'Reserva cancelada. O período foi liberado.');
    }

    /** Pré-visualização do e-mail enviado ao cliente (útil para a demonstração). */
    public function emailPreview(Reservation $reservation): ReservationStatusMail
    {
        Gate::authorize('view', $reservation);

        return new ReservationStatusMail($reservation->load('user', 'space.unit', 'space.category'),
            $reservation->status === ReservationStatus::Cancelled ? 'cancelled' : 'confirmed');
    }
}
