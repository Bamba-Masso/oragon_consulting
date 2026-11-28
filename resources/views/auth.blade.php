@php
    $isCabinet = $space === 'cabinet';
@endphp
<x-layouts.auth
    :space="$space"
    :title="'Connexion '.($isCabinet ? 'collaborateur' : 'espace client')"
    heading="Connexion"
    :description="$isCabinet
        ? 'Accédez aux dossiers et aux outils du cabinet.'
        : 'Suivez vos dossiers, déposez vos pièces et consultez vos livrables.'">

    @if (session('status'))
        <div class="mb-6">
            <x-flash />
        </div>
    @endif

    <form method="POST" action="{{ route($isCabinet ? 'cabinet.login.store' : 'client.login.store') }}" class="space-y-6" novalidate>
        @csrf

        <!-- Identifiant -->
        <div>
            <label for="login" class="block mb-2 text-sm font-medium text-navy-900">E-mail ou nom d'utilisateur</label>
            <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                class="block w-full rounded-xl border bg-slate-50/50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition {{ $errors->has('login') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' }}"
                placeholder="exemple@entreprise.ci">
            @error('login')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Mot de passe -->
        <div>
            <label for="password" class="block mb-2 text-sm font-medium text-navy-900">Mot de passe</label>
            <div class="relative">
                <input type="password" id="password" name="password" required autocomplete="current-password"
                    class="block w-full rounded-xl border bg-slate-50/50 px-4 py-3 pr-12 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' }}"
                    placeholder="••••••••">
                <button type="button" id="toggle-password" aria-label="Afficher le mot de passe" aria-pressed="false"
                    class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-navy-900 transition focus:outline-none">
                    <svg data-icon="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <svg data-icon="hide" class="hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                </button>
            </div>
            <div class="mt-2 flex items-start justify-between gap-3">
                @error('password')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
                <a href="{{ route($isCabinet ? 'cabinet.password.request' : 'client.password.request') }}" class="ml-auto shrink-0 text-xs font-medium text-gold-700 hover:text-gold-600 transition">Mot de passe oublié ?</a>
            </div>
        </div>

        <!-- Se souvenir de moi -->
        <label for="remember" class="flex items-center gap-2.5 text-sm text-slate-600 cursor-pointer select-none">
            <input type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))
                class="w-4 h-4 rounded border-slate-300 text-navy-900 focus:ring-gold-500/30">
            Se souvenir de moi
        </label>

        <button type="submit" class="group w-full inline-flex justify-center items-center gap-2 px-5 py-3.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white font-semibold text-sm shadow-lg shadow-navy-900/15 transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
            Se connecter
            <svg class="w-4 h-4 text-gold-500 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>
    </form>

    <x-slot:scripts>
        <!-- Afficher / masquer le mot de passe -->
        <script>
            document.getElementById('toggle-password').addEventListener('click', function () {
                const input = document.getElementById('password');
                const isHidden = input.type === 'password';

                input.type = isHidden ? 'text' : 'password';
                this.setAttribute('aria-pressed', String(isHidden));
                this.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                this.querySelector('[data-icon="show"]').classList.toggle('hidden', isHidden);
                this.querySelector('[data-icon="hide"]').classList.toggle('hidden', ! isHidden);
            });
        </script>
    </x-slot:scripts>
</x-layouts.auth>
