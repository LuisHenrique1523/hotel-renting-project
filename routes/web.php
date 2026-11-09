<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

// ── Vitrine pública ────────────────────────────────────────────
Route::get('/', HomeController::class)->name('home');

Route::get('/unidades', [UnitController::class, 'index'])->name('units.index');
Route::get('/unidades/{unit}', [UnitController::class, 'show'])->name('units.show');

Route::get('/espacos', [SpaceController::class, 'index'])->name('spaces.index');
Route::get('/espacos/{space}', [SpaceController::class, 'show'])->name('spaces.show');

// ── Disponibilidade em tempo real (JSON para o Alpine.js) ─────
Route::middleware('throttle:120,1')->prefix('disponibilidade/{space}')->name('availability.')->group(function () {
    Route::get('/horarios', [AvailabilityController::class, 'hours'])->name('hours');
    Route::get('/dias', [AvailabilityController::class, 'days'])->name('days');
    Route::get('/orcamento', [AvailabilityController::class, 'quote'])->name('quote');
});

// ── Área autenticada ──────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Pós-login: equipe vai ao painel, cliente às suas reservas
    Route::get('/dashboard', fn () => auth()->user()->canAccessPanel(filament()->getDefaultPanel())
        ? redirect('/admin')
        : to_route('reservations.index'))->name('dashboard');

    Route::post('/espacos/{space}/reservar', [ReservationController::class, 'store'])
        ->middleware('throttle:20,1')->name('reservations.store');

    Route::get('/minhas-reservas', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservas/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('/reservas/{reservation}/pagamento', [ReservationController::class, 'checkout'])->name('reservations.checkout');
    Route::post('/reservas/{reservation}/pagamento', [ReservationController::class, 'pay'])->name('reservations.pay');
    Route::post('/reservas/{reservation}/cancelar', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::get('/reservas/{reservation}/email', [ReservationController::class, 'emailPreview'])->name('reservations.email');

    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
