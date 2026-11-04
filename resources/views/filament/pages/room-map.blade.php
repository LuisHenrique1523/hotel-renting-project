<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <x-filament::icon-button icon="heroicon-m-chevron-left" wire:click="shiftDay(-1)" label="Dia anterior" color="gray" />
            <div class="min-w-[14rem] text-center">
                <p class="font-display text-xl font-semibold text-gray-950 dark:text-white">{{ ucfirst($day->translatedFormat('l, d \d\e F')) }}</p>
                @unless ($day->isToday())
                    <button wire:click="today" class="text-xs font-medium text-primary-600 hover:underline">voltar para hoje</button>
                @else
                    <p class="text-xs text-gray-500">Hoje</p>
                @endunless
            </div>
            <x-filament::icon-button icon="heroicon-m-chevron-right" wire:click="shiftDay(1)" label="Próximo dia" color="gray" />
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($states as $key => [$label, $classes, $dot])
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <span class="h-2.5 w-2.5 rounded-full {{ $dot }}"></span> {{ $label }}
                    <span class="font-bold">{{ $summary[$key] ?? 0 }}</span>
                </span>
            @endforeach
        </div>
    </div>

    <div class="space-y-6" wire:loading.class="opacity-50">
        @foreach ($floors as $floor => $rooms)
            <x-filament::section :heading="$floor == 0 ? 'Bangalôs (térreo)' : $floor.'º andar'" compact>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                    @foreach ($rooms as $item)
                        @php [$label, $classes] = $states[$item['state']]; @endphp
                        <a @if ($item['url']) href="{{ $item['url'] }}" @endif
                           class="group relative flex min-h-[7.5rem] flex-col justify-between rounded-xl p-3 ring-1 transition hover:-translate-y-0.5 hover:shadow-lg {{ $classes }}">
                            <div class="flex flex-wrap items-start justify-between gap-1">
                                <span class="font-display text-2xl font-bold leading-none">{{ $item['room']->number }}</span>
                                <span class="text-[0.65rem] font-semibold uppercase tracking-wide opacity-80">{{ $label }}</span>
                            </div>
                            <div class="text-xs">
                                <p class="truncate opacity-70">{{ $item['room']->type->name }}</p>
                                @if ($item['guest'])
                                    <p class="truncate font-semibold">{{ $item['guest'] }}</p>
                                    <p class="opacity-70">até {{ $item['until']->format('d/m') }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
