@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-night-100 shadow-sm focus:border-gold-500 focus:ring-gold-500']) }}>
