<x-filament-panels::page>
    @unless (auth()->user()->isProfessional())
        <div class="flex flex-wrap items-center justify-between gap-4">
            <x-filament::tabs>
                @foreach (['professional' => ['Spa & Serviços', 'heroicon-m-sparkles'], 'space' => ['Salas', 'heroicon-m-presentation-chart-bar'], 'room' => ['Hospedagem', 'heroicon-m-home-modern'], 'all' => ['Tudo', 'heroicon-m-squares-2x2']] as $key => [$label, $icon])
                    <x-filament::tabs.item :active="$kind === $key" :icon="$icon" wire:click="setKind('{{ $key }}')">
                        {{ $label }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>

            <div class="flex items-center gap-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded" style="background:#C8A15A"></span> Spa</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded" style="background:#0EA5E9"></span> Salas</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded" style="background:#2B3F5C"></span> Hospedagem</span>
                <span class="flex items-center gap-1.5"><span class="ev-legend h-3 w-3 rounded opacity-60" style="background:repeating-linear-gradient(45deg,#C8A15A,#C8A15A 2px,#fff 2px,#fff 4px)"></span> Aguardando pagamento</span>
            </div>
        </div>
    @endunless

    @livewire(\App\Filament\Calendar\ReservationCalendarWidget::class, ['kind' => $kind], key('calendar-'.$kind))
</x-filament-panels::page>
