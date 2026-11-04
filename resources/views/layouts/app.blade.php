<x-layouts.site title="Meu perfil">
    @isset($header)
        <div class="container-site max-w-4xl pt-12">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}
</x-layouts.site>
