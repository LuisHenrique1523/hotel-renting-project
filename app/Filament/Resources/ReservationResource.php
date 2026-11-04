<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\ReservationException;
use App\Filament\Resources\ReservationResource\Pages;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Space;
use App\Models\User;
use App\Services\ReservationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Operação';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'reserva';
    protected static ?string $pluralModelLabel = 'reservas';
    protected static ?string $recordTitleAttribute = 'code';

    /** Profissional enxerga apenas a própria agenda. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'user', 'service', 'payment',
            'reservable' => fn (MorphTo $morph) => $morph->morphWith([Room::class => ['type'], Professional::class => ['user']]),
        ]);

        $user = auth()->user();
        if ($user?->isProfessional()) {
            $query->where('reservable_type', Reservation::KIND_PROFESSIONAL)
                ->where('reservable_id', $user->professional?->id ?? 0);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->whereDate('starts_at', today())->blocking()->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Reservas que começam hoje';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'user.name', 'user.email'];
    }

    // ── Criação no balcão (telefone, walk-in) ──────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Hóspede')->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Cliente')
                    ->options(fn () => User::where('role', 'guest')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->label('Nome')->required(),
                        Forms\Components\TextInput::make('email')->label('E-mail')->email()->required()->unique('users', 'email'),
                        Forms\Components\TextInput::make('phone')->label('Telefone')->tel(),
                    ])
                    ->createOptionUsing(fn (array $data) => User::create($data + ['password' => str()->random(16), 'role' => 'guest'])->id),
            ]),

            Forms\Components\Section::make('O que reservar')->schema([
                Forms\Components\ToggleButtons::make('kind')
                    ->label('Tipo')
                    ->options(Reservation::KINDS)
                    ->icons(['room' => 'heroicon-o-home-modern', 'space' => 'heroicon-o-presentation-chart-bar', 'professional' => 'heroicon-o-sparkles'])
                    ->inline()->required()->live()->default('room'),

                // Hospedagem
                Forms\Components\Grid::make(4)->visible(fn (Get $get) => $get('kind') === 'room')->schema([
                    Forms\Components\Select::make('room_type_id')->label('Categoria')->options(RoomType::orderBy('base_price')->pluck('name', 'id'))->required()->columnSpan(2),
                    Forms\Components\DatePicker::make('check_in')->label('Check-in')->native(false)->displayFormat('d/m/Y')->minDate(today())->required(),
                    Forms\Components\DatePicker::make('check_out')->label('Check-out')->native(false)->displayFormat('d/m/Y')->after('check_in')->required(),
                ]),

                // Sala
                Forms\Components\Grid::make(4)->visible(fn (Get $get) => $get('kind') === 'space')->schema([
                    Forms\Components\Select::make('space_id')->label('Espaço')->options(Space::pluck('name', 'id'))->required()->columnSpan(2),
                    Forms\Components\DateTimePicker::make('space_start')->label('Início')->native(false)->seconds(false)->minutesStep(60)->displayFormat('d/m/Y H:i')->required(),
                    Forms\Components\DateTimePicker::make('space_end')->label('Fim')->native(false)->seconds(false)->minutesStep(60)->displayFormat('d/m/Y H:i')->after('space_start')->required(),
                ]),

                // Spa / profissional
                Forms\Components\Grid::make(4)->visible(fn (Get $get) => $get('kind') === 'professional')->schema([
                    Forms\Components\Select::make('service_id')->label('Serviço')->options(Service::pluck('name', 'id'))->required()->live(),
                    Forms\Components\Select::make('professional_id')->label('Profissional')
                        ->options(fn (Get $get) => Professional::with('user')
                            ->when($get('service_id'), fn ($q, $id) => $q->whereHas('services', fn ($s) => $s->whereKey($id)))
                            ->get()->pluck('name', 'id'))
                        ->required(),
                    Forms\Components\DateTimePicker::make('service_start')->label('Horário')->native(false)->seconds(false)->minutesStep(30)->displayFormat('d/m/Y H:i')->required()->columnSpan(2),
                ]),

                Forms\Components\Grid::make(4)->schema([
                    Forms\Components\TextInput::make('guests')->label('Pessoas')->numeric()->minValue(1)->default(1)->required()
                        ->hidden(fn (Get $get) => $get('kind') === 'professional'),
                    Forms\Components\Textarea::make('notes')->label('Observações')->rows(2)->columnSpan(3),
                ]),
                Forms\Components\Toggle::make('confirm_now')->label('Pagamento recebido no balcão (confirmar agora)')->default(true),
            ]),
        ]);
    }

    // ── Listagem ────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable()->copyable()
                    ->fontFamily(FontFamily::Mono)->weight(FontWeight::SemiBold),
                Tables\Columns\TextColumn::make('reservable_type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (string $state) => Reservation::KINDS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) { 'room' => 'gray', 'space' => 'info', default => 'primary' })
                    ->icon(fn (string $state) => match ($state) { 'room' => 'heroicon-m-home-modern', 'space' => 'heroicon-m-presentation-chart-bar', default => 'heroicon-m-sparkles' }),
                Tables\Columns\TextColumn::make('title')->label('Recurso')
                    ->description(fn (Reservation $record) => $record->subtitle),
                Tables\Columns\TextColumn::make('user.name')->label('Hóspede')->searchable()
                    ->description(fn (Reservation $record) => $record->user?->email),
                Tables\Columns\TextColumn::make('starts_at')->label('Início')->dateTime('d/m/Y H:i')->sortable()
                    ->description(fn (Reservation $record) => $record->starts_at->diffForHumans()),
                Tables\Columns\TextColumn::make('ends_at')->label('Fim')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('BRL', locale: 'pt_BR')->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Soma')->money('BRL', locale: 'pt_BR')),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('payment.status')->label('Pagamento')->badge()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(ReservationStatus::class)->multiple(),
                Tables\Filters\SelectFilter::make('reservable_type')->label('Tipo')->options(Reservation::KINDS),
                Tables\Filters\Filter::make('today')->label('Começam hoje')->toggle()
                    ->query(fn (Builder $q) => $q->whereDate('starts_at', today())),
                Tables\Filters\Filter::make('period')->label('Período')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('De'),
                        Forms\Components\DatePicker::make('until')->label('Até'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'], fn ($q, $d) => $q->whereDate('starts_at', '>=', $d))
                        ->when($data['until'], fn ($q, $d) => $q->whereDate('starts_at', '<=', $d))),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    ...static::workflowActions(),
                ])->button()->label('Ações')->color('gray')->size('sm'),
            ])
            ->recordUrl(fn (Reservation $record) => static::getUrl('view', ['record' => $record]))
            ->striped()
            ->poll('30s');
    }

    /**
     * Botões da máquina de estados. Cada um chama o ReservationService,
     * que valida a transição; aqui só decidimos quando o botão aparece.
     *
     * @return array<Tables\Actions\Action>
     */
    public static function workflowActions(string $actionClass = Tables\Actions\Action::class): array
    {
        $run = function (Reservation $record, string $method, string $message) {
            try {
                app(ReservationService::class)->{$method}($record);
                Notification::make()->title($message)->success()->send();
            } catch (ReservationException $e) {
                Notification::make()->title('Não foi possível concluir')->body($e->getMessage())->danger()->send();
            }
        };

        $can = fn (Reservation $record, ReservationStatus $to) => $record->status->canTransitionTo($to) && auth()->user()->can('operate', $record);

        return [
            $actionClass::make('confirm')->label('Confirmar pagamento')->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn (Reservation $record) => $can($record, ReservationStatus::Confirmed))
                ->requiresConfirmation()->modalDescription('Registra o pagamento no balcão e confirma a reserva.')
                ->action(fn (Reservation $record) => $run($record, 'confirm', 'Reserva confirmada')),

            $actionClass::make('checkIn')->label('Fazer check-in')->icon('heroicon-o-key')->color('info')
                ->visible(fn (Reservation $record) => $can($record, ReservationStatus::CheckedIn) && $record->starts_at->startOfDay()->lte(now()))
                ->action(fn (Reservation $record) => $run($record, 'checkIn', 'Check-in realizado')),

            $actionClass::make('checkOut')->label('Concluir / check-out')->icon('heroicon-o-flag')->color('gray')
                ->visible(fn (Reservation $record) => $can($record, ReservationStatus::Completed))
                ->action(fn (Reservation $record) => $run($record, 'checkOut', 'Reserva concluída')),

            $actionClass::make('noShow')->label('Não compareceu')->icon('heroicon-o-user-minus')->color('warning')
                ->visible(fn (Reservation $record) => $can($record, ReservationStatus::NoShow) && $record->starts_at->isPast())
                ->requiresConfirmation()
                ->action(fn (Reservation $record) => $run($record, 'markNoShow', 'Marcada como não comparecimento')),

            $actionClass::make('cancel')->label('Cancelar')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (Reservation $record) => $can($record, ReservationStatus::Cancelled))
                ->requiresConfirmation()->modalDescription('O horário será liberado e, se pago, o valor será estornado.')
                ->action(function (Reservation $record) {
                    try {
                        app(ReservationService::class)->cancel($record, auth()->user());
                        Notification::make()->title('Reserva cancelada')->success()->send();
                    } catch (ReservationException $e) {
                        Notification::make()->title('Não foi possível cancelar')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    // ── Detalhes ────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Grid::make(3)->schema([
                Infolists\Components\Section::make(fn (Reservation $record) => $record->title)
                    ->description(fn (Reservation $record) => $record->kind_label.' · '.$record->subtitle)
                    ->icon('heroicon-o-ticket')
                    ->columnSpan(2)->columns(3)
                    ->schema([
                        Infolists\Components\ImageEntry::make('image_url')->hiddenLabel()->height(150)->extraImgAttributes(['class' => 'rounded-xl object-cover']),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('code')->label('Código')->fontFamily(FontFamily::Mono)->copyable()->size('lg')->weight(FontWeight::Bold),
                            Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
                            Infolists\Components\TextEntry::make('guests')->label('Pessoas'),
                        ]),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('starts_at')->label('Início')->dateTime('d/m/Y \à\s H:i'),
                            Infolists\Components\TextEntry::make('ends_at')->label('Fim')->dateTime('d/m/Y \à\s H:i'),
                            Infolists\Components\TextEntry::make('total')->label('Total')->money('BRL', locale: 'pt_BR')->size('lg')->weight(FontWeight::Bold),
                        ]),
                        Infolists\Components\TextEntry::make('notes')->label('Observações do hóspede')->placeholder('Nenhuma')->columnSpanFull(),
                    ]),

                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Hóspede')->icon('heroicon-o-user')->schema([
                        Infolists\Components\TextEntry::make('user.name')->label('Nome')->weight(FontWeight::SemiBold),
                        Infolists\Components\TextEntry::make('user.email')->label('E-mail')->copyable(),
                        Infolists\Components\TextEntry::make('user.phone')->label('Telefone')->placeholder('—'),
                    ]),
                    Infolists\Components\Section::make('Pagamento')->icon('heroicon-o-credit-card')->schema([
                        Infolists\Components\TextEntry::make('payment.method')->label('Forma')->placeholder('—'),
                        Infolists\Components\TextEntry::make('payment.status')->label('Situação')->badge(),
                        Infolists\Components\TextEntry::make('payment.paid_at')->label('Pago em')->dateTime('d/m/Y H:i')->placeholder('—'),
                        Infolists\Components\TextEntry::make('payment.transaction_id')->label('Transação')->fontFamily(FontFamily::Mono)->placeholder('—'),
                    ]),
                ]),
            ]),

            Infolists\Components\Section::make('Linha do tempo')->icon('heroicon-o-clock')->columns(5)->schema([
                Infolists\Components\TextEntry::make('created_at')->label('Criada')->dateTime('d/m H:i'),
                Infolists\Components\TextEntry::make('confirmed_at')->label('Confirmada')->dateTime('d/m H:i')->placeholder('—'),
                Infolists\Components\TextEntry::make('checked_in_at')->label('Check-in')->dateTime('d/m H:i')->placeholder('—'),
                Infolists\Components\TextEntry::make('checked_out_at')->label('Concluída')->dateTime('d/m H:i')->placeholder('—'),
                Infolists\Components\TextEntry::make('cancelled_at')->label('Cancelada')->dateTime('d/m H:i')->placeholder('—'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'view' => Pages\ViewReservation::route('/{record}'),
        ];
    }
}
