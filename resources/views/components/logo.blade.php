@props(['light' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 40 40" class="h-9 w-9 shrink-0" aria-hidden="true">
        <circle cx="20" cy="20" r="19" fill="none" stroke="#C8A15A" stroke-width="1.5"/>
        <path d="M8 25 Q20 6 32 25" fill="none" stroke="#C8A15A" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M11 29h18" stroke="{{ $light ? '#E9DCC9' : '#0F1E33' }}" stroke-width="2" stroke-linecap="round"/>
        <circle cx="20" cy="18" r="3.2" fill="#C8A15A"/>
    </svg>
    <span class="leading-none">
        <span @class(['block font-display text-xl font-semibold tracking-[0.18em]', 'text-white' => $light, 'text-night-900' => ! $light])>AURORA</span>
        <span @class(['block text-[0.58rem] font-medium uppercase tracking-[0.42em]', 'text-sand-200' => $light, 'text-gold-600' => ! $light])>Workspaces</span>
    </span>
</span>
