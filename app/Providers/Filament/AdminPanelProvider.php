<?php

namespace App\Providers\Filament;

use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Aurora Hotel & Spa')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.6rem')
            ->favicon("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='19' fill='%230F1E33'/%3E%3Cpath d='M8 25 Q20 6 32 25' fill='none' stroke='%23C8A15A' stroke-width='3'/%3E%3Ccircle cx='20' cy='18' r='3.5' fill='%23C8A15A'/%3E%3C/svg%3E")
            ->colors([
                'primary' => Color::hex('#C8A15A'),
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->font('Inter', provider: LocalFontProvider::class)
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationGroups([
                NavigationGroup::make('Operação'),
                NavigationGroup::make('Hospedagem'),
                NavigationGroup::make('Spa & Bem-estar'),
                NavigationGroup::make('Salas & Eventos'),
                NavigationGroup::make('Administração')->collapsed(),
            ])
            ->userMenuItems([
                MenuItem::make()->label('Ver site')->url('/', shouldOpenInNewTab: true)->icon('heroicon-o-globe-alt'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // Registrado para o Livewire resolver o componente nas requisições AJAX
            // (o dashboard lista seus widgets explicitamente, então ele não aparece lá)
            ->widgets([\App\Filament\Calendar\ReservationCalendarWidget::class])
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->locale('pt-br')
                    ->timezone(config('app.timezone'))
                    ->config([
                        'firstDay' => 1,
                        'nowIndicator' => true,
                        'dayMaxEvents' => true,
                        'slotMinTime' => '06:00:00',
                        'slotMaxTime' => '23:00:00',
                        'allDaySlot' => false,
                        'height' => 'auto',
                        'eventTimeFormat' => ['hour' => '2-digit', 'minute' => '2-digit', 'hour12' => false],
                        'slotLabelFormat' => ['hour' => '2-digit', 'minute' => '2-digit', 'hour12' => false],
                    ]),
            ])
            ->databaseNotifications(false)
            ->spa()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
