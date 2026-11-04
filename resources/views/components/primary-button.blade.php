<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-night-900 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-widest hover:bg-night-700 focus:bg-night-700 active:bg-night-950 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
