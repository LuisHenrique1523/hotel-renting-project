<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <x-filament::tabs>
            @foreach ($units as $u)
                <x-filament::tabs.item :active="$unit === $u->id" icon="heroicon-m-building-office-2" wire:click="setUnit({{ $u->id }})">
                    {{ $u->name }}
                </x-filament::tabs.item>
            @endforeach
            <x-filament::tabs.item :active="$unit === null" icon="heroicon-m-squares-2x2" wire:click="setUnit(null)">
                Todas
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
            @foreach (\App\Enums\BillingUnit::cases() as $b)
                <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded" style="background:{{ $b->hex() }}"></span> {{ $b->getLabel() }}</span>
            @endforeach
            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded opacity-60" style="background:repeating-linear-gradient(45deg,#0EA5E9,#0EA5E9 2px,#fff 2px,#fff 4px)"></span> Aguardando pagamento</span>
        </div>
    </div>

    @livewire(\App\Filament\Calendar\ReservationCalendarWidget::class, ['unit' => $unit], key('calendar-'.($unit ?? 'all')))
</x-filament-panels::page>
