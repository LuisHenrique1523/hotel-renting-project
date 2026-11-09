@props(['space', 'showUnit' => true])

<a href="{{ route('spaces.show', $space) }}" class="group card flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-xl">
    <div class="relative aspect-[4/3] overflow-hidden">
        <img src="{{ $space->image_url }}" alt="{{ $space->name }}" loading="lazy" class="img-zoom h-full w-full object-cover">
        <span class="absolute left-3 top-3 chip !bg-white/90 backdrop-blur">
            <x-dynamic-component :component="$space->category->icon ?: 'heroicon-o-cube'" class="h-3.5 w-3.5" />
            {{ $space->category->name }}
        </span>
        @if ($space->is_shared)
            <span class="absolute right-3 top-3 chip !bg-night-900/80 !text-sand-100 backdrop-blur">Compartilhado</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        @if ($showUnit)
            <p class="text-xs font-semibold uppercase tracking-wider text-gold-600">{{ $space->unit->name }} · {{ $space->unit->city }}</p>
        @endif
        <h3 class="mt-1 font-display text-xl leading-snug">{{ $space->name }}</h3>
        <div class="mt-auto flex items-end justify-between gap-3 pt-4 text-sm text-night-500">
            <span>{{ $space->is_shared ? $space->capacity.' estações' : 'até '.$space->capacity.' '.($space->capacity === 1 ? 'pessoa' : 'pessoas') }}</span>
            <span class="text-right">
                <span class="font-display text-lg font-semibold text-night-900">@money($space->price)</span><span class="text-night-400">{{ $space->billing_unit->suffix() }}</span>
                @if ($space->is_shared)<span class="block text-[0.65rem] text-night-400">por pessoa</span>@endif
            </span>
        </div>
    </div>
</a>
