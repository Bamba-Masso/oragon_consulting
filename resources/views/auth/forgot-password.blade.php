@php
    $isCabinet = $space === 'cabinet';
@endphp
<x-layouts.auth
    :space="$space"
    title="Mot de passe oublié"
    heading="Mot de passe oublié"
    description="Indiquez l'adresse e-mail de votre compte : nous vous enverrons un lien pour choisir un nouveau mot de passe.">

    @if (session('status'))
        <div class="mb-6">
            <x-flash />
        </div>
    @endif

    <form method="POST" action="{{ route($isCabinet ? 'cabinet.password.email' : 'client.password.email') }}" class="space-y-6" novalidate>
        @csrf

        <!-- E-mail -->
        <div>
            <label for="email" class="block mb-2 text-sm font-medium text-navy-900">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="block w-full rounded-xl border bg-slate-50/50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 transition {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-slate-200 focus:border-gold-600 focus:ring-gold-500/15' }}"
                placeholder="exemple@entreprise.ci">
            @error('email')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="group w-full inline-flex justify-center items-center gap-2 px-5 py-3.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white font-semibold text-sm shadow-lg shadow-navy-900/15 transition focus:outline-none focus:ring-4 focus:ring-gold-500/25">
            Envoyer le lien
            <svg class="w-4 h-4 text-gold-500 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>

        <p class="text-center">
            <a href="{{ route($isCabinet ? 'cabinet.login' : 'client.login') }}" class="text-xs font-medium text-gold-700 hover:text-gold-600 transition">Retour à la connexion</a>
        </p>
    </form>
</x-layouts.auth>
