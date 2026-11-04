<x-layouts.site title="Spa & Bem-estar" overlay>
    <x-page-hero image="spa-stones" eyebrow="Aurora Spa"
                 title="Um tempo só seu, no horário que você escolher"
                 subtitle="Escolha a terapia, o profissional e veja na hora os horários livres na agenda de cada um." />

    <section class="container-site mt-14" x-data="{ tab: 'all' }">
        <div class="flex flex-wrap gap-2">
            <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-night-900 text-white' : 'bg-white text-night-700 hover:bg-sand-100'"
                    class="rounded-full px-5 py-2.5 text-sm font-semibold shadow-sm transition">Todos</button>
            @foreach ($categories as $category)
                <button @click="tab = '{{ $category->value }}'"
                        :class="tab === '{{ $category->value }}' ? 'bg-night-900 text-white' : 'bg-white text-night-700 hover:bg-sand-100'"
                        class="rounded-full px-5 py-2.5 text-sm font-semibold shadow-sm transition">{{ $category->getLabel() }}</button>
            @endforeach
        </div>

        <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <a href="{{ route('spa.show', $service) }}" x-show="tab === 'all' || tab === '{{ $service->category->value }}'"
                   x-transition.opacity class="group card flex flex-col overflow-hidden">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="{{ $service->image_url }}" alt="{{ $service->name }}" class="img-zoom h-full w-full object-cover">
                        <span class="absolute left-4 top-4 chip !bg-white/90 backdrop-blur">⏱ {{ $service->duration_label }}</span>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gold-600">{{ $service->category->getLabel() }}</p>
                        <h3 class="mt-1 font-display text-2xl">{{ $service->name }}</h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-night-500">{{ $service->description }}</p>
                        <div class="mt-5 flex items-center justify-between border-t border-night-50 pt-4">
                            <span class="font-display text-xl font-semibold">@money($service->price)</span>
                            <span class="text-sm text-night-500">{{ $service->professionals_count }} {{ $service->professionals_count === 1 ? 'profissional' : 'profissionais' }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.site>
