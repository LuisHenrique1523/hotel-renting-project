<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SpaController;
use App\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;

// ── Vitrine pública ────────────────────────────────────────────
Route::get('/', HomeController::class)->name('home');

Route::get('/quartos', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/quartos/{roomType}', [RoomController::class, 'show'])->name('rooms.show');

Route::get('/spa', [SpaController::class, 'index'])->name('spa.index');
Route::get('/spa/{service}', [SpaController::class, 'show'])->name('spa.show');

Route::get('/salas', [SpaceController::class, 'index'])->name('spaces.index');
Route::get('/salas/{space}', [SpaceController::class, 'show'])->name('spaces.show');

// ── Disponibilidade em tempo real (JSON para o Alpine.js) ─────
Route::middleware('throttle:120,1')->prefix('disponibilidade')->name('availability.')->group(function () {
    Route::get('/spa/{service}', [AvailabilityController::class, 'service'])->name('service');
    Route::get('/salas/{space}', [AvailabilityController::class, 'space'])->name('space');
});

// ── Área autenticada ──────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Pós-login: equipe vai ao painel, hóspede às suas reservas
    Route::get('/dashboard', fn () => auth()->user()->canAccessPanel(filament()->getDefaultPanel())
        ? redirect('/admin')
        : to_route('reservations.index'))->name('dashboard');

    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/quartos/{roomType}/reservar', [ReservationController::class, 'storeRoom'])->name('reservations.room');
        Route::post('/salas/{space}/reservar', [ReservationController::class, 'storeSpace'])->name('reservations.space');
        Route::post('/spa/{service}/reservar', [ReservationController::class, 'storeService'])->name('reservations.service');
    });

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
