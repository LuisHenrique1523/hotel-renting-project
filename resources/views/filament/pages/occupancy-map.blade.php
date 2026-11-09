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

    <x-filament::tabs>
        <x-filament::tabs.item :active="$unit === null" icon="heroicon-m-squares-2x2" wire:click="setUnit(null)">Todas as unidades</x-filament::tabs.item>
        @foreach ($units as $u)
            <x-filament::tabs.item :active="$unit === $u->id" wire:click="setUnit({{ $u->id }})">{{ $u->name }}</x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    <div class="space-y-6" wire:loading.class="opacity-50">
        @forelse ($groups as $unitName => $items)
            <x-filament::section :heading="$unitName" :description="$items->first()['space']->unit->full_address.' · '.$items->first()['space']->unit->hours_label" compact>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($items as $item)
                        @php [$label, $classes] = $states[$item['state']]; @endphp
                        <a href="{{ $item['url'] }}"
                           class="group relative flex min-h-[7.5rem] flex-col justify-between rounded-xl p-3 ring-1 transition hover:-translate-y-0.5 hover:shadow-lg {{ $classes }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold leading-tight">{{ $item['space']->name }}</p>
                                    <p class="truncate text-xs opacity-70">{{ $item['space']->category->name }} · {{ $item['space']->capacity }} pessoas</p>
                                </div>
                                <span class="shrink-0 text-[0.65rem] font-semibold uppercase tracking-wide opacity-80">{{ $label }}</span>
                            </div>
                            <div class="text-xs">
                                <p class="truncate font-semibold">{{ $item['detail'] }}</p>
                                @if ($item['sub'])
                                    <p class="truncate opacity-70">{{ $item['sub'] }}</p>
                                @endif
                                @isset($item['percent'])
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-black/10 dark:bg-white/10">
                                        <div class="h-full rounded-full bg-current opacity-60" style="width: {{ $item['percent'] }}%"></div>
                                    </div>
                                @endisset
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <p class="text-sm text-gray-500">Nenhum espaço cadastrado. Comece pelo menu Cadastros → Unidades.</p>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
