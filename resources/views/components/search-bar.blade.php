@props(['cities' => collect(), 'categories' => collect(), 'filters' => [], 'glass' => false])

@php $box = $glass ? 'bg-white/95' : 'bg-sand-50'; @endphp

<form action="{{ route('spaces.index') }}" method="GET"
      @class([
          'grid gap-3 rounded-3xl p-3 sm:grid-cols-2 lg:grid-cols-[1fr_1.2fr_0.7fr_auto] lg:items-end',
          'border border-white/20 bg-white/10 shadow-2xl backdrop-blur-xl' => $glass,
          'card' => ! $glass,
      ])>
    <label class="block rounded-2xl {{ $box }} px-4 py-2.5">
        <span class="field-label !text-night-400">Cidade</span>
        <select name="cidade" class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-night-900 focus:ring-0">
            <option value="">Todas as cidades</option>
            @foreach ($cities as $city)
                <option value="{{ $city }}" @selected(($filters['cidade'] ?? null) === $city)>{{ $city }}</option>
            @endforeach
        </select>
    </label>
    <label class="block rounded-2xl {{ $box }} px-4 py-2.5">
        <span class="field-label !text-night-400">O que você precisa</span>
        <select name="categoria" class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-night-900 focus:ring-0">
            <option value="">Qualquer espaço</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" @selected(($filters['categoria'] ?? null) === $category->slug)>{{ $category->name }} · {{ mb_strtolower($category->billing_unit->getLabel()) }}</option>
            @endforeach
        </select>
    </label>
    <label class="block rounded-2xl {{ $box }} px-4 py-2.5">
        <span class="field-label !text-night-400">Pessoas</span>
        <input type="number" name="pessoas" min="1" max="1000" placeholder="Qualquer" value="{{ $filters['pessoas'] ?? '' }}"
               class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-night-900 placeholder:text-night-300 focus:ring-0">
    </label>
    @if (! empty($filters['unidade']))
        <input type="hidden" name="unidade" value="{{ $filters['unidade'] }}">
    @endif
    <button class="btn-gold h-full min-h-[3.5rem] !rounded-2xl sm:col-span-2 lg:col-span-1">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
        Buscar espaços
    </button>
</form>
