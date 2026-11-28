@php
    $isCabinet = $space === 'cabinet';
    $inputClasses = 'block w-full rounded-xl border bg-slate-50/50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition';
    $validClasses = 'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15';
    $invalidClasses = 'border-red-400 focus:border-red-500 focus:ring-red-500/10';
@endphp
<x-layouts.auth
    :space="$space"
    title="Nouveau mot de passe"
    heading="Nouveau mot de passe"
    description="Choisissez votre nouveau mot de passe. Vous pourrez ensuite vous connecter avec.">

    <form method="POST" action="{{ route($isCabinet ? 'cabinet.password.update' : 'client.password.update') }}" class="space-y-6" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <!-- E-mail -->
        <div>
            <label for="email" class="block mb-2 text-sm font-medium text-navy-900">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
                class="{{ $inputClasses }} {{ $errors->has('email') ? $invalidClasses : $validClasses }}">
            @error('email')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Nouveau mot de passe -->
        <div>
            <label for="password" class="block mb-2 text-sm font-medium text-navy-900">Nouveau mot de passe</label>
            <input type="password" id="password" name="password" required autofocus autocomplete="new-password"
                class="{{ $inputClasses }} {{ $errors->has('password') ? $invalidClasses : $validClasses }}"
                placeholder="••••••••">
            @error('password')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirmation -->
        <div>
            <label for="password_confirmation" class="block mb-2 text-sm font-medium text-navy-900">Confirmer le mot de passe</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                class="{{ $inputClasses }} {{ $validClasses }}"
                placeholder="••••••••">
        </div>

        <button type="submit" class="group w-full inline-flex justify-center items-center gap-2 px-5 py-3.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white font-semibold text-sm shadow-lg shadow-navy-900/15 transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
            Enregistrer le mot de passe
            <svg class="w-4 h-4 text-gold-500 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>

        <p class="text-center">
            <a href="{{ route($isCabinet ? 'cabinet.login' : 'client.login') }}" class="text-xs font-medium text-gold-700 hover:text-gold-600 transition">Retour à la connexion</a>
        </p>
    </form>
</x-layouts.auth>
