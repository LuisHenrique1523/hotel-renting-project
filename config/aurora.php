<?php

/*
| Regras de negócio parametrizáveis da rede Aurora Workspaces.
| Os dados de cada unidade (endereço, horário) ficam no banco e são cadastrados no painel.
*/

return [
    'name' => 'Aurora Workspaces',
    'tagline' => 'Escritórios, salas e coworking sob demanda',
    'phone' => '(11) 4000-2026',
    'email' => 'reservas@aurora.test',

    // Mostra as contas de demonstração na tela de login (site público de apresentação)
    'demo_mode' => (bool) env('AURORA_DEMO', env('HOTEL_DEMO', env('APP_ENV') === 'local')),

    // Até quantos dias à frente o cliente pode iniciar uma reserva
    'booking_horizon_days' => 90,

    // Cancelamento e expiração
    'guest_cancellation_hours' => 24,
    'pending_expiration_minutes' => 30,
    'no_show_tolerance_hours' => 6,
];
