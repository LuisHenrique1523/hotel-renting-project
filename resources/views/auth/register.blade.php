<x-guest-layout>
    <h1 class="font-display text-4xl font-semibold">Crie sua conta</h1>
    <p class="mt-2 text-night-500">Leva menos de um minuto, e suas reservas ficam todas em um só lugar.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf
        <label class="block">
            <span class="field-label">Nome completo</span>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="field">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </label>

        <label class="block">
            <span class="field-label">E-mail</span>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="field">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </label>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="block">
                <span class="field-label">Senha</span>
                <input type="password" name="password" required autocomplete="new-password" class="field">
            </label>
            <label class="block">
                <span class="field-label">Confirmar senha</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="field">
            </label>
        </div>
        <x-input-error :messages="$errors->get('password')" class="-mt-2" />

        <button class="btn-gold w-full !py-3.5">Criar conta</button>
    </form>

    <p class="mt-8 text-center text-sm text-night-500">
        Já tem conta? <a href="{{ route('login') }}" class="font-semibold text-gold-700 hover:underline">Entrar</a>
    </p>
</x-guest-layout>
