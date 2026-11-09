<x-layouts.site title="Espaços">
    <section class="container-site pt-10">
        <p class="eyebrow">Encontre o espaço ideal</p>
        <h1 class="heading mt-2">
            @if ($active = $categories->firstWhere('slug', $filters['categoria'] ?? null))
                {{ $active->name }}
            @else
                Todos os espaços
            @endif
            @if (! empty($filters['cidade'])) <span class="text-night-400">em {{ $filters['cidade'] }}</span> @endif
        </h1>

        <div class="mt-8">
            <x-search-bar :cities="$cities" :categories="$categories" :filters="$filters" />
        </div>

        {{-- Atalhos por categoria --}}
        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('spaces.index', array_filter(['cidade' => $filters['cidade'] ?? null, 'pessoas' => $filters['pessoas'] ?? null])) }}"
               @class(['chip !px-4 !py-2 !text-sm transition', '!bg-night-900 !text-white' => empty($filters['categoria']), 'hover:!bg-sand-200' => ! empty($filters['categoria'])])>Todos</a>
            @foreach ($categories as $category)
                <a href="{{ route('spaces.index', array_filter(['categoria' => $category->slug, 'cidade' => $filters['cidade'] ?? null, 'pessoas' => $filters['pessoas'] ?? null])) }}"
                   @class(['chip !px-4 !py-2 !text-sm transition', '!bg-night-900 !text-white' => ($filters['categoria'] ?? null) === $category->slug, 'hover:!bg-sand-200' => ($filters['categoria'] ?? null) !== $category->slug])>
                    <x-dynamic-component :component="$category->icon ?: 'heroicon-o-cube'" class="h-4 w-4" />
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        @if (! empty($filters['unidade']) && ($unit = $units->firstWhere('slug', $filters['unidade'])))
            <p class="mt-4 text-sm text-night-500">Mostrando apenas a unidade <strong>{{ $unit->name }}</strong> ·
                <a class="font-semibold text-gold-700 hover:underline" href="{{ route('spaces.index', collect($filters)->except('unidade')->filter()->all()) }}">ver todas</a></p>
        @endif
    </section>

    <section class="container-site mt-10">
        <p class="text-sm text-night-500">{{ $spaces->count() }} {{ $spaces->count() === 1 ? 'espaço encontrado' : 'espaços encontrados' }}</p>

        @if ($spaces->isEmpty())
            <div class="card mt-6 flex flex-col items-center p-14 text-center">
                <span class="text-5xl">🔍</span>
                <p class="mt-4 font-display text-2xl">Nenhum espaço com esses filtros</p>
                <p class="mt-1 text-night-500">Tente outra cidade, outro tipo de espaço ou menos pessoas.</p>
                <a href="{{ route('spaces.index') }}" class="btn-gold mt-6">Limpar filtros</a>
            </div>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($spaces as $space)
                    <x-space-card :space="$space" />
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.site>
