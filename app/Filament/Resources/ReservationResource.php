<?php

namespace App\Filament\Resources;

use App\Enums\BillingUnit;
use App\Enums\ReservationStatus;
use App\Exceptions\ReservationException;
use App\Filament\Resources\ReservationResource\Pages;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\Unit;
use App\Models\User;
use App\Services\PricingService;
use App\Services\ReservationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Operação';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'reserva';
    protected static ?string $pluralModelLabel = 'reservas';
    protected static ?string $recordTitleAttribute = 'code';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'payment', 'space.unit', 'space.category']);
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
        return ['code', 'user.name', 'user.email', 'space.name'];
    }

    // ── Criação no balcão (telefone, walk-in) ──────────────────

    public static function form(Form $form): Form
    {
        $space = fn (Get $get): ?Space => $get('space_id') ? Space::with('unit', 'category')->find($get('space_id')) : null;

        return $form->schema([
            Forms\Components\Section::make('Cliente')->schema([
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

            Forms\Components\Section::make('O que reservar')->columns(4)->schema([
                Forms\Components\Select::make('unit_id')->label('Unidade')
                    ->options(fn () => Unit::active()->orderBy('name')->get()->mapWithKeys(fn (Unit $u) => [$u->id => "{$u->name} · {$u->city}"]))
                    ->required()->live()->columnSpan(2)
                    ->afterStateUpdated(fn (Set $set) => $set('space_id', null)),
                Forms\Components\Select::make('space_id')->label('Espaço')
                    ->options(fn (Get $get) => Space::bookable()->with('category')->where('unit_id', $get('unit_id'))->orderBy('name')->get()
                        ->mapWithKeys(fn (Space $s) => [$s->id => "{$s->name} · {$s->category->name} ({$s->price_label})"]))
                    ->disabled(fn (Get $get) => ! $get('unit_id'))
                    ->required()->live()->searchable()->columnSpan(2)
                    ->afterStateUpdated(fn (Set $set, Get $get) => $set('quantity', $space($get)?->category->min_quantity ?? 1)),

                Forms\Components\DatePicker::make('date')
                    ->label(fn (Get $get) => $space($get)?->billing_unit === BillingUnit::Hour ? 'Data' : 'Data de início')
                    ->native(false)->displayFormat('d/m/Y')->minDate(today())->required()->live(),
                Forms\Components\Select::make('start_hour')->label('Horário de início')
                    ->options(function (Get $get) use ($space) {
                        $unit = $space($get)?->unit;

                        return $unit ? collect(range($unit->opens_hour, $unit->closes_hour - 1))->mapWithKeys(fn ($h) => [$h => sprintf('%02d:00', $h)]) : [];
                    })
                    ->visible(fn (Get $get) => $space($get)?->billing_unit === BillingUnit::Hour)
                    ->required()->live(),
                Forms\Components\TextInput::make('quantity')->label('Duração')->numeric()->required()->default(1)->live(onBlur: true)
                    ->minValue(fn (Get $get) => $space($get)?->category->min_quantity ?? 1)
                    ->maxValue(fn (Get $get) => $space($get)?->category->max_quantity ?? 365)
                    ->suffix(fn (Get $get) => $space($get)?->billing_unit->unitName()),
                Forms\Components\TextInput::make('guests')->numeric()->minValue(1)->default(1)->required()->live(onBlur: true)
                    ->label(fn (Get $get) => $space($get)?->is_shared ? 'Vagas (pessoas)' : 'Pessoas')
                    ->maxValue(fn (Get $get) => $space($get)?->capacity ?? 1000),

                Forms\Components\Placeholder::make('estimate')->label('Valor estimado')->columnSpanFull()
                    ->content(function (Get $get) use ($space) {
                        $s = $space($get);
                        if (! $s || ! $get('quantity')) {
                            return 'Escolha o espaço e a duração.';
                        }
                        $q = app(PricingService::class)->quote($s, (int) $get('quantity'), (int) ($get('guests') ?: 1));

                        return 'R$ '.number_format($q['total'], 2, ',', '.').' · '.$q['label'].($s->is_shared ? " · {$q['guests']} ".($q['guests'] === 1 ? 'pessoa' : 'pessoas') : '');
                    }),

                Forms\Components\Textarea::make('notes')->label('Observações')->rows(2)->columnSpanFull(),
                Forms\Components\Toggle::make('confirm_now')->label('Pagamento recebido no balcão (confirmar agora)')->default(true)->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('space.name')->label('Espaço')->searchable()
                    ->description(fn (Reservation $record) => $record->subtitle),
                Tables\Columns\TextColumn::make('kind_label')->label('Categoria')->badge()
                    ->color(fn (Reservation $record) => $record->billing_unit->getColor())
                    ->icon(fn (Reservation $record) => $record->billing_unit->getIcon()),
                Tables\Columns\TextColumn::make('user.name')->label('Cliente')->searchable()
                    ->description(fn (Reservation $record) => $record->user?->email),
                Tables\Columns\TextColumn::make('starts_at')->label('Início')->dateTime('d/m/Y H:i')->sortable()
                    ->description(fn (Reservation $record) => $record->quantity_label),
                Tables\Columns\TextColumn::make('ends_at')->label('Fim')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('BRL', locale: 'pt_BR')->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Soma')->money('BRL', locale: 'pt_BR')),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('payment.status')->label('Pagamento')->badge()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(ReservationStatus::class)->multiple(),
                Tables\Filters\SelectFilter::make('unit')->label('Unidade')
                    ->options(fn () => Unit::orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $id) => $q->inUnit((int) $id))),
                Tables\Filters\SelectFilter::make('category')->label('Categoria')
                    ->options(fn () => \App\Models\SpaceCategory::ordered()->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $id) => $q->whereHas('space', fn ($s) => $s->where('space_category_id', $id)))),
                Tables\Filters\SelectFilter::make('billing_unit')->label('Cobrança')->options(BillingUnit::class),
                Tables\Filters\Filter::make('today')->label('Começam hoje')->toggle()
                    ->query(fn (Builder $query) => $query->whereDate('starts_at', today())),
                Tables\Filters\Filter::make('period')->label('Período')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('De'),
                        Forms\Components\DatePicker::make('until')->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
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

            $actionClass::make('checkOut')->label('Concluir')->icon('heroicon-o-flag')->color('gray')
                ->visible(fn (Reservation $record) => $can($record, ReservationStatus::Completed))
                ->action(fn (Reservation $record) => $run($record, 'checkOut', 'Reserva concluída')),

            $actionClass::make('noShow')->label('Não compareceu')->icon('heroicon-o-user-minus')->color('warning')
                ->visible(fn (Reservation $record) => $can($record, ReservationStatus::NoShow) && $record->starts_at->isPast())
                ->requiresConfirmation()
                ->action(fn (Reservation $record) => $run($record, 'markNoShow', 'Marcada como não comparecimento')),

            $actionClass::make('cancel')->label('Cancelar')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (Reservation $record) => $can($record, ReservationStatus::Cancelled))
                ->requiresConfirmation()->modalDescription('O período será liberado e, se pago, o valor será estornado.')
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
                            Infolists\Components\TextEntry::make('period_label')->label('Período'),
                            Infolists\Components\TextEntry::make('unit_price')->label('Valor unitário')
                                ->formatStateUsing(fn (Reservation $record) => 'R$ '.number_format((float) $record->unit_price, 2, ',', '.').$record->billing_unit->suffix()),
                            Infolists\Components\TextEntry::make('total')->label('Total')->money('BRL', locale: 'pt_BR')->size('lg')->weight(FontWeight::Bold),
                        ]),
                        Infolists\Components\TextEntry::make('space.unit.full_address')->label('Endereço da unidade')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('notes')->label('Observações do cliente')->placeholder('Nenhuma')->columnSpanFull(),
                    ]),

                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Cliente')->icon('heroicon-o-user')->schema([
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
