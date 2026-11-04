@props(['image', 'eyebrow', 'title', 'subtitle' => null])

<section class="relative overflow-hidden bg-night-950 pb-28 pt-40">
    <img src="{{ asset("storage/img/{$image}.jpg") }}" alt="" class="absolute inset-0 h-full w-full animate-slow-zoom object-cover opacity-40">
    <div class="absolute inset-0 bg-gradient-to-t from-night-950 to-night-950/30"></div>
    <div class="container-site relative">
        <p class="eyebrow animate-fade-up !text-gold-300">{{ $eyebrow }}</p>
        <h1 class="mt-4 max-w-3xl animate-fade-up font-display text-4xl font-semibold leading-tight text-white [animation-delay:100ms] sm:text-6xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-5 max-w-2xl animate-fade-up text-lg text-sand-200/85 [animation-delay:200ms]">{{ $subtitle }}</p>
        @endif
    </div>
</section>
