<x-app-layout>
    <x-slot name="header">
        <p class="eyebrow">Conta</p>
        <h1 class="heading mt-2">Meu perfil</h1>
    </x-slot>

    <div class="container-site max-w-4xl space-y-6 py-10">
        <div class="card p-6 sm:p-8">
            <div class="max-w-xl">@include('profile.partials.update-profile-information-form')</div>
        </div>
        <div class="card p-6 sm:p-8">
            <div class="max-w-xl">@include('profile.partials.update-password-form')</div>
        </div>
        <div class="card p-6 sm:p-8">
            <div class="max-w-xl">@include('profile.partials.delete-user-form')</div>
        </div>
    </div>
</x-app-layout>
