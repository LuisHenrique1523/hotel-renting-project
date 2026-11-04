@props(['reservation'])

<div class="flex gap-4">
    <img src="{{ $reservation->image_url }}" alt="" class="h-24 w-24 shrink-0 rounded-2xl object-cover sm:h-28 sm:w-28">
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-gold-600">{{ $reservation->kind_label }}</p>
        <p class="mt-0.5 font-display text-xl leading-tight">{{ $reservation->title }}</p>
        <p class="text-sm text-night-500">{{ $reservation->subtitle }}</p>
        <p class="mt-2 text-sm font-medium">{{ $reservation->period_label }}</p>
    </div>
</div>
