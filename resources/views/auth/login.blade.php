<x-guest-layout>
    <h1 class="font-display text-4xl font-semibold">Bem-vindo de volta</h1>
    <p class="mt-2 text-night-500">Entre para reservar e acompanhar suas estadias.</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf
        <label class="block">
            <span class="field-label">E-mail</span>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field" placeholder="voce@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </label>

        <label class="block">
            <span class="field-label flex justify-between">
                Senha
                @if (Route::has('password.request'))
                    <a class="normal-case tracking-normal text-gold-700 hover:underline" href="{{ route('password.request') }}">Esqueceu a senha?</a>
                @endif
            </span>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="field" placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </label>

        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-night-200 text-gold-600 focus:ring-gold-500">
            <span class="text-sm text-night-600">Manter conectado</span>
        </label>

        <button class="btn-dark w-full !py-3.5">Entrar</button>
    </form>

    <p class="mt-8 text-center text-sm text-night-500">
        Ainda não tem conta? <a href="{{ route('register') }}" class="font-semibold text-gold-700 hover:underline">Cadastre-se grátis</a>
    </p>

    {{-- Atalhos de demonstração (ativados por AURORA_DEMO) --}}
    @if (config('aurora.demo_mode'))
        <div class="mt-10 rounded-2xl border border-dashed border-night-200 p-4" x-data>
            <p class="text-xs font-semibold uppercase tracking-wider text-night-400">Contas de demonstração · senha aurora123</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (['cliente@aurora.test' => 'Cliente', 'recepcao@aurora.test' => 'Recepção', 'admin@aurora.test' => 'Admin'] as $email => $label)
                    <button type="button" class="chip hover:bg-gold-100"
                            @click="document.getElementById('email').value='{{ $email }}'; document.getElementById('password').value='aurora123'">{{ $label }}</button>
                @endforeach
            </div>
        </div>
    @endif
</x-guest-layout>
