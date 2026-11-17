@php
    $isCabinet = $space === 'cabinet';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Connexion {{ $isCabinet ? 'collaborateur' : 'espace client' }} — {{ config('app.name', 'Cabinet Conseil & Expertise') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-amber-50/40 text-slate-800 antialiased selection:bg-gold-600 selection:text-white">

    <main class="min-h-screen flex flex-col items-center justify-center px-4 py-12">

        <div class="relative w-full max-w-md overflow-hidden bg-white rounded-2xl shadow-2xl shadow-navy-900/[0.06] ring-1 ring-slate-200/70 p-8 sm:p-10">
            <!-- Filet doré -->
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-gold-500 via-gold-600 to-gold-700"></div>

            <!-- En-tête -->
            <div class="mb-9 space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-3">
                        <span class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] {{ $isCabinet ? 'text-navy-900' : 'text-gold-700' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-gold-500"></span>
                            {{ $isCabinet ? 'Espace collaborateur' : 'Espace client' }}
                        </span>
                        <h1 class="text-3xl font-bold tracking-tight text-navy-900">Connexion</h1>
                    </div>

                    <!-- Logo -->
                    <a href="{{ url('/') }}" class="shrink-0 focus:outline-none">
                        <img src="{{ asset('logo.png') }}" alt="Logo du Cabinet" class="h-12 w-auto object-contain">
                    </a>
                </div>
                <p class="text-sm leading-relaxed text-slate-500">
                    {{ $isCabinet
                        ? 'Accédez aux dossiers et aux outils du cabinet.'
                        : 'Suivez vos dossiers, déposez vos pièces et consultez vos livrables.' }}
                </p>
            </div>

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
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="text-sm font-medium text-navy-900">Mot de passe</label>
                        <a href="#" class="text-xs font-medium text-gold-700 hover:text-gold-600 transition">Mot de passe oublié ?</a>
                    </div>
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
                    @error('password')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
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
        </div>

        <p class="mt-8 flex items-center gap-2 text-xs text-slate-400">
            <svg class="w-3.5 h-3.5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Connexion sécurisée · © {{ date('Y') }} {{ config('app.name', 'Cabinet Conseil & Expertise') }}
        </p>

    </main>

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
</body>
</html>
