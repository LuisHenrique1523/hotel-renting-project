<?php

/*
| Regras de negócio parametrizáveis do Aurora Hotel & Spa.
*/

return [
    'name' => 'Aurora Hotel & Spa',
    'phone' => '(31) 3333-2026',
    'email' => 'reservas@aurora.test',
    'address' => 'Av. das Palmeiras, 1200 · Beira-mar',

    // Mostra as contas de demonstração na tela de login (site público de apresentação)
    'demo_mode' => (bool) env('HOTEL_DEMO', env('APP_ENV') === 'local'),

    // Hospedagem
    'check_in_hour' => 14,
    'check_out_hour' => 12,
    'max_nights' => 30,
    'weekend_surcharge' => 0.20, // +20% nas diárias de sexta e sábado

    // Agenda de salas e profissionais
    'slot_step_minutes' => 30,
    'booking_horizon_days' => 90,

    // Cancelamento e expiração
    'guest_cancellation_hours' => 24,
    'pending_expiration_minutes' => 30,
    'no_show_tolerance_hours' => 6,
];
