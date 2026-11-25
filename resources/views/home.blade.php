<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name', 'Cabinet Conseil & Expertise') }} — Comptabilité, Fiscalité & Audit</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://kit.fontawesome.com/55f637bece.js" crossorigin="anonymous"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-gold-600 selection:text-white">

    <!-- =================================================================
         1. BARRE DE NAVIGATION FLOTTANTE (NAVBAR)
    ================================================================== -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/80 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- Logo depuis le dossier public -->
                <a href="{{ url('/') }}" class="flex items-center gap-3 focus:outline-none">
                    <img src="{{ asset('logo.png') }}" alt="Oragon Consulting" class="h-16 w-auto object-contain">
                </a>

                <!-- Menu Desktop -->
                <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                    <a href="#a-propos" class="hover:text-navy-900 transition">Le Cabinet</a>
                    <a href="#poles" class="hover:text-navy-900 transition">Nos Pôles d'Expertise</a>
                    <a href="#fonctionnement" class="hover:text-navy-900 transition">Notre Approche</a>
                    <a href="#contact" class="hover:text-navy-900 transition">Contact</a>
                </nav>

                <!-- Boutons d'accès (Portail Client & Cabinet) -->
                <div class="hidden md:flex items-center gap-3">
                    @auth('client')
                        <a href="{{ route('client.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition">
                            <span>Mon Espace Client</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    @elseauth('cabinet')
                        <a href="{{ route('cabinet.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold shadow-sm transition">
                            <span>Tableau de bord Cabinet</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    @else
                        <!-- Dropdown Flowbite : choix de l'espace de connexion -->
                        <button id="loginDropdownButton" data-dropdown-toggle="loginDropdown" data-dropdown-placement="bottom-end" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold shadow-md shadow-navy-900/10 transition focus:outline-none focus:ring-4 focus:ring-navy-900/20">
                            <svg class="w-4 h-4 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Se connecter</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div id="loginDropdown" class="z-50 hidden w-60 bg-white rounded-xl shadow-lg border border-slate-200/80">
                            <ul class="p-2 text-sm text-slate-700" aria-labelledby="loginDropdownButton">
                                <li>
                                    <a href="{{ route('client.login') }}" class="flex flex-col px-3 py-2.5 rounded-lg hover:bg-slate-50 transition">
                                        <span class="font-semibold text-navy-900">Espace client</span>
                                        <span class="text-xs text-slate-500">Suivre vos dossiers et documents</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('cabinet.login') }}" class="flex flex-col px-3 py-2.5 rounded-lg hover:bg-slate-50 transition">
                                        <span class="font-semibold text-navy-900">Collaborateur</span>
                                        <span class="text-xs text-slate-500">Membres du cabinet</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>

                <!-- Bouton Menu Mobile -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" type="button" class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu Mobile Déroulant -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-slate-100 px-4 pt-3 pb-6 space-y-3 shadow-lg">
            <a href="#a-propos" class="block py-2 text-base font-medium text-slate-700">Le Cabinet</a>
            <a href="#poles" class="block py-2 text-base font-medium text-slate-700">Nos Pôles d'Expertise</a>
            <a href="#fonctionnement" class="block py-2 text-base font-medium text-slate-700">Notre Approche</a>
            <a href="#contact" class="block py-2 text-base font-medium text-slate-700">Contact</a>
            <div class="pt-3 border-t border-slate-100 space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Se connecter</p>
                <a href="{{ route('client.login') }}" class="w-full flex justify-center items-center px-5 py-3 rounded-xl bg-navy-900 text-white font-semibold">Espace client</a>
                <a href="{{ route('cabinet.login') }}" class="w-full flex justify-center items-center px-5 py-3 rounded-xl border border-slate-200 text-navy-900 font-semibold">Collaborateur</a>
            </div>
        </div>
    </header>

    <!-- =================================================================
         2. SECTION HERO (PHOTO PLEINE LARGEUR + RECHERCHE DE SOLUTION)
    ================================================================== -->
    <section class="relative overflow-hidden bg-navy-950">
        <img src="https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&w=1920&q=80"
             alt="" class="absolute inset-0 w-full h-full object-cover object-right">
        <div class="absolute inset-0 bg-linear-to-r from-navy-950 via-navy-950/85 to-navy-950/10"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-40 lg:pt-24 lg:pb-48">
            <div class="max-w-2xl space-y-6">
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-200">
                    <i class="fa-solid fa-chevron-right text-[10px] text-gold-500" aria-hidden="true"></i>
                    Expertise comptable, fiscale & RH
                </p>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.1]">
                    Des solutions comptables, fiscales et RH <span class="text-gold-500">pour un avenir serein</span>
                </h1>

                <p class="text-base sm:text-lg text-slate-200 max-w-xl leading-relaxed">
                    Oragon Consulting vous apporte des solutions innovantes et personnalisées pour simplifier la gestion
                    de votre entreprise, en comptabilité, en fiscalité et en ressources humaines.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="#poles" class="inline-flex justify-center items-center gap-2 px-6 py-3 rounded-lg bg-gold-600 hover:bg-gold-700 text-white font-semibold text-sm transition">
                        Nos services
                        <i class="fa-solid fa-chevron-right text-[10px]" aria-hidden="true"></i>
                    </a>
                    <a href="#contact" class="inline-flex justify-center items-center px-6 py-3 rounded-lg border border-white/70 hover:bg-white/10 text-white font-semibold text-sm transition">
                        Nous contacter
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte flottante : horaires de consultation -->
        <div class="hidden lg:block absolute right-8 xl:right-16 bottom-36 w-64 rounded-2xl bg-navy-900/95 p-5 text-white shadow-2xl ring-1 ring-white/10">
            <div class="flex items-center gap-3">
                <span class="w-11 h-11 rounded-xl bg-white/10 text-gold-500 flex items-center justify-center shrink-0">
                    <i class="fa-regular fa-calendar-check text-lg" aria-hidden="true"></i>
                </span>
                <div>
                    <p class="text-xs text-slate-300">Planifier une consultation</p>
                    <p class="text-xl font-extrabold tracking-wide">LUN – VEN</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-300 leading-relaxed">De 8h30 à 18h00, au cabinet ou à distance. En dehors de ces horaires, sur rendez-vous.</p>
        </div>
    </section>

    <!-- Recherche de solution, à cheval sur le Hero -->
    <section class="relative z-10 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-28">
            <form id="need-finder" class="bg-white rounded-2xl shadow-xl ring-1 ring-slate-200/70 p-6 sm:p-7">
                <h2 class="text-lg font-bold text-navy-900">Trouvez la solution adaptée à votre besoin</h2>

                <div class="mt-5 grid gap-4 md:grid-cols-4">
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-2.5 focus-within:border-gold-600 transition">
                        <i class="fa-regular fa-building w-4 text-slate-400" aria-hidden="true"></i>
                        <span class="flex-1 min-w-0">
                            <span class="block text-xs font-semibold text-navy-900">Vous êtes</span>
                            <select name="profile" class="w-full border-0 bg-transparent py-0 pl-0 text-sm text-slate-500 focus:ring-0">
                                <option value="">Sélectionner</option>
                                <option>Une PME</option>
                                <option>Une startup</option>
                                <option>Une grande entreprise</option>
                                <option>Une ONG</option>
                                <option>Un porteur de projet</option>
                            </select>
                        </span>
                    </label>

                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-2.5 focus-within:border-gold-600 transition">
                        <i class="fa-regular fa-file-lines w-4 text-slate-400" aria-hidden="true"></i>
                        <span class="flex-1 min-w-0">
                            <span class="block text-xs font-semibold text-navy-900">Type de besoin</span>
                            <select name="need" class="w-full border-0 bg-transparent py-0 pl-0 text-sm text-slate-500 focus:ring-0">
                                <option value="">Sélectionner</option>
                                <option value="comptabilite">Gestion comptable</option>
                                <option value="fiscalite">Conseil fiscal</option>
                                <option value="finance">Finance d'entreprise</option>
                                <option value="audit">Audit et contrôle de gestion</option>
                                <option value="rh">Ressources humaines & juridique</option>
                            </select>
                        </span>
                    </label>

                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-2.5 focus-within:border-gold-600 transition">
                        <i class="fa-solid fa-industry w-4 text-slate-400" aria-hidden="true"></i>
                        <span class="flex-1 min-w-0">
                            <span class="block text-xs font-semibold text-navy-900">Secteur d'activité</span>
                            <select name="sector" class="w-full border-0 bg-transparent py-0 pl-0 text-sm text-slate-500 focus:ring-0">
                                <option value="">Sélectionner</option>
                                <option>Commerce et distribution</option>
                                <option>Industrie</option>
                                <option>Services</option>
                                <option>BTP et immobilier</option>
                                <option>Agriculture et agro-industrie</option>
                                <option>Transport et logistique</option>
                                <option>Technologies</option>
                                <option>Autre</option>
                            </select>
                        </span>
                    </label>

                    <button type="submit" class="inline-flex justify-center items-center gap-2 rounded-xl bg-navy-900 hover:bg-navy-800 px-5 py-3 text-sm font-semibold text-white transition focus:outline-none focus:ring-4 focus:ring-navy-900/20">
                        Rechercher
                        <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- =================================================================
         3. À PROPOS DU CABINET
    ================================================================== -->
    <section id="a-propos" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="relative">
                    <div class="rounded-2xl overflow-hidden shadow-lg">
                        <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=900&q=80" 
                             alt="Analyse financière et audit" 
                             class="w-full h-96 object-cover">
                    </div>
                    <div class="bg-navy-900 text-white p-6 rounded-2xl shadow-xl -mt-12 mx-6 relative z-10 border border-slate-800">
                        <p class="text-gold-500 font-bold text-sm uppercase tracking-wider mb-1">Notre Engagement</p>
                        <p class="text-base font-medium text-slate-200">
                            « Transformer vos obligations comptables et fiscales en véritables leviers de décision stratégique. »
                        </p>
                    </div>
                </div>

                <div class="space-y-6">
                    <span class="text-gold-600 font-bold text-sm uppercase tracking-wider">À propos du cabinet</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-navy-900 leading-tight">
                        Un partenaire de confiance structuré autour de vos enjeux métiers
                    </h2>
                    <p class="text-slate-600 leading-relaxed">
                        Nous accompagnons les entreprises à chaque étape de leur développement : de la création juridique jusqu'à l'audit financier approfondi. Notre force réside dans une organisation rigoureuse par pôles d'expertise, garantissant que chaque dossier est traité par un spécialiste dédié.
                    </p>
                    
                    <ul class="space-y-3 pt-2">
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-gold-500/15 text-gold-600 flex items-center justify-center shrink-0 mt-0.5 font-bold text-sm">✓</span>
                            <span class="text-slate-700 font-medium">Interlocuteur unique et suivi transparent de chaque mission</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-gold-500/15 text-gold-600 flex items-center justify-center shrink-0 mt-0.5 font-bold text-sm">✓</span>
                            <span class="text-slate-700 font-medium">Respect strict des échéances légales, sociales et fiscales</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-gold-500/15 text-gold-600 flex items-center justify-center shrink-0 mt-0.5 font-bold text-sm">✓</span>
                            <span class="text-slate-700 font-medium">Confidentialité absolue et traçabilité numérique de vos données</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section> 

    <!-- =================================================================
         4. NOS 5 PÔLES D'EXPERTISE (ALIGNÉS SUR TON ARCHITECTURE)
    ================================================================== -->
     <section id="poles" class="py-20 bg-slate-50 border-t border-slate-200/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <span class="text-gold-600 font-bold text-sm uppercase tracking-wider">Nos Domaines d'Intervention</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-navy-900">
                    Cinq pôles d'expertise au service de votre sérénité
                </h2>
                <p class="text-slate-600">
                    Une prise en charge complète et coordonnée de vos obligations administratives, comptables, fiscales et financières.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Pôle 1 : Comptabilité -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-navy-900 text-gold-500 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-navy-900 mb-3">Gestion Comptable</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        Tenue et surveillance de comptabilité, révision des comptes, établissement des états financiers annuels (SYSCOHADA révisé) et situations intermédiaires.
                    </p>
                    <span class="text-xs font-bold text-gold-600 uppercase tracking-wider">Bilans • Journaux • États financiers</span>
                </div>

                <!-- Pôle 2 : Fiscalité -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-navy-900 text-gold-500 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-navy-900 mb-3">Conseil & Gestion Fiscale</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        Établissement des déclarations fiscales mensuelles et annuelles (TVA, ITS, BIC, Patente), optimisation fiscale légale et assistance lors des contrôles fiscaux.
                    </p>
                    <span class="text-xs font-bold text-gold-600 uppercase tracking-wider">Déclarations • Conformité • Assistance</span>
                </div>

                <!-- Pôle 3 : Ingénierie Financière -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-navy-900 text-gold-500 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-navy-900 mb-3">Conseil Financier & Pilotage</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        Élaboration de business plans, budgets prévisionnels, tableaux de bord de gestion, suivi de trésorerie et accompagnement à la recherche de financement.
                    </p>
                    <span class="text-xs font-bold text-gold-600 uppercase tracking-wider">Trésorerie • Business Plan • Reporting</span>
                </div>

                <!-- Pôle 4 : Contrôle & Audit -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-navy-900 text-gold-500 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-navy-900 mb-3">Contrôle Interne & Audit</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        Audit organisationnel et financier, revue des procédures internes, détection des risques et recommandations opérationnelles pour sécuriser vos actifs.
                    </p>
                    <span class="text-xs font-bold text-gold-600 uppercase tracking-wider">Procédures • Risques • Conformité</span>
                </div>

                <!-- Pôle 5 : Secrétariat & RH -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-navy-900 text-gold-500 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-navy-900 mb-3">Secrétariat Juridique & RH</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        Constitution de sociétés, rédaction des procès-verbaux d'assemblées, gestion de la paie, déclarations sociales (CNPS) et administration du personnel.
                    </p>
                    <span class="text-xs font-bold text-gold-600 uppercase tracking-wider">Paie • CNPS • Vie juridique</span>
                </div>

                <!-- Carte CTA intégrée à la grille -->
                <div class="bg-navy-900 rounded-2xl p-8 text-white flex flex-col justify-between shadow-lg relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-gold-500/15 rounded-full blur-2xl"></div>
                    <div class="space-y-4 relative z-10">
                        <span class="inline-block px-3 py-1 rounded-full bg-gold-500/20 text-gold-500 text-xs font-bold">Sur mesure</span>
                        <h3 class="text-2xl font-bold leading-snug">Besoin d'un accompagnement personnalisé ?</h3>
                        <p class="text-slate-300 text-sm leading-relaxed">
                            Nos associés analysent votre structure pour vous proposer une lettre de mission adaptée à votre taille et votre secteur.
                        </p>
                    </div>
                    <div class="pt-6 relative z-10">
                        <a href="#contact" class="w-full inline-flex justify-center items-center gap-2 px-5 py-3.5 rounded-xl bg-gold-600 hover:bg-gold-700 text-white font-bold text-sm transition">
                            <span>Demander un devis</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section> 

   
    <!-- =================================================================
         6. COMMENT ÇA MARCHE ? (3 ÉTAPES CLAIRES)
    ================================================================== -->
    <section id="fonctionnement" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
                <span class="text-gold-600 font-bold text-sm uppercase tracking-wider">Collaboration Fluide</span>
                <h2 class="text-3xl font-extrabold text-navy-900">Comment démarrer avec notre cabinet ?</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="relative p-6 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="text-5xl font-extrabold text-slate-200 absolute top-4 right-6">01</span>
                    <h3 class="text-lg font-bold text-navy-900 mb-2 relative z-10">Diagnostic & Ouverture</h3>
                    <p class="text-sm text-slate-600 leading-relaxed relative z-10">
                        Nous analysons vos besoins lors d'un premier entretien et créons votre espace client sécurisé avec votre interlocuteur dédié.
                    </p>
                </div>

                <div class="relative p-6 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="text-5xl font-extrabold text-slate-200 absolute top-4 right-6">02</span>
                    <h3 class="text-lg font-bold text-navy-900 mb-2 relative z-10">Transmission Simplifiée</h3>
                    <p class="text-sm text-slate-600 leading-relaxed relative z-10">
                        Vous déposez vos pièces comptables sur le portail au fil de l'eau ou en réponse à nos demandes de documents ciblées.
                    </p>
                </div>

                <div class="relative p-6 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="text-5xl font-extrabold text-slate-200 absolute top-4 right-6">03</span>
                    <h3 class="text-lg font-bold text-navy-900 mb-2 relative z-10">Traitement & Livrables</h3>
                    <p class="text-sm text-slate-600 leading-relaxed relative z-10">
                        Nos pôles traitent, contrôlent et valident vos déclarations et bilans, puis les publient directement dans votre coffre-fort numérique.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- =================================================================
         7. SECTION CONTACT & PRISE DE RENDEZ-VOUS
    ================================================================== -->
    <section id="contact" class="py-20 bg-slate-50 border-t border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-200/70 overflow-hidden grid lg:grid-cols-12">
                
                <!-- Infos de contact -->
                <div class="lg:col-span-5 bg-navy-900 text-white p-8 sm:p-12 flex flex-col justify-between">
                    <div class="space-y-6">
                        <h2 class="text-3xl font-extrabold">Échangeons sur vos projets</h2>
                        <p class="text-slate-300 text-sm leading-relaxed">
                            Une question sur votre comptabilité, votre fiscalité ou l'ouverture d'un dossier ? Notre équipe vous répond sous 24 heures ouvrées.
                        </p>

                        <div class="space-y-4 pt-4 text-sm">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-white/10 text-gold-500 flex items-center justify-center shrink-0">📍</span>
                                <span>Cocody Angré, Star Avenue — Abidjan, Côte d'Ivoire</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-white/10 text-gold-500 flex items-center justify-center shrink-0">📞</span>
                                <a href="tel:+2250757000043" class="hover:text-gold-500 transition">(+225) 07 57 00 00 43</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-white/10 text-gold-500 flex items-center justify-center shrink-0">✉️</span>
                                <a href="mailto:infos@oragonconsulting-ci.com" class="hover:text-gold-500 transition">infos@oragonconsulting-ci.com</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-white/10 text-gold-500 flex items-center justify-center shrink-0">🕗</span>
                                <span>Du lundi au vendredi, de 8h30 à 18h00</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-8 mt-8 border-t border-slate-800">
                        <a href="https://wa.me/message/353K7FVN7EMQA1" target="_blank" rel="noopener" class="w-full inline-flex justify-center items-center gap-2 px-5 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition">
                            <span>Discuter directement sur WhatsApp</span>
                        </a>
                    </div>
                </div>

                <!-- Formulaire de contact -->
                <div class="lg:col-span-7 p-8 sm:p-12">
                    <h3 class="text-xl font-bold text-navy-900 mb-6">Envoyez-nous un message</h3>
                    
                    @if(session('contact_success'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
                            {{ session('contact_success') }}
                        </div>
                    @endif

                    <form action="#" method="POST" class="space-y-5">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Nom complet *</label>
                                <input type="text" name="name" required placeholder="Ex: Kouassi Jean" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-navy-900 focus:border-navy-900 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Entreprise</label>
                                <input type="text" name="company" placeholder="Nom de votre société" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-navy-900 focus:border-navy-900 text-sm">
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Email professionnel *</label>
                                <input type="email" name="email" required placeholder="nom@entreprise.ci" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-navy-900 focus:border-navy-900 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Téléphone *</label>
                                <input type="tel" name="phone" required placeholder="+225 07..." class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-navy-900 focus:border-navy-900 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Pôle concerné</label>
                            <select name="service" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-navy-900 focus:border-navy-900 text-sm bg-white">
                                <option value="comptabilite">Gestion Comptable & Bilan</option>
                                <option value="fiscalite">Conseil & Déclarations Fiscales</option>
                                <option value="finance">Conseil Financier & Business Plan</option>
                                <option value="audit">Audit & Contrôle Interne</option>
                                <option value="rh">Secrétariat Juridique & Paie</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Votre besoin *</label>
                            <textarea name="message" rows="3" required placeholder="Décrivez brièvement votre demande..." class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-navy-900 focus:border-navy-900 text-sm"></textarea>
                        </div>

                        <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white font-bold text-sm shadow-md transition">
                            Envoyer ma demande
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

    <!-- =================================================================
         8. FOOTER
    ================================================================== -->
    <footer class="bg-navy-950 text-slate-400 py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <img src="{{ asset('logo.png') }}" alt="Oragon Consulting" class="h-14 w-auto object-contain bg-white/95 p-1.5 rounded-lg self-start">

                <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <a href="#a-propos" class="hover:text-white transition">Le Cabinet</a>
                    <a href="#poles" class="hover:text-white transition">Nos Pôles</a>
                    <a href="#fonctionnement" class="hover:text-white transition">Notre Approche</a>
                    <a href="#contact" class="hover:text-white transition">Contact</a>
                </nav>
            </div>

            <div class="pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-slate-500">
                <p>© {{ date('Y') }} {{ config('app.name', 'Cabinet Conseil') }}. Tous droits réservés.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-slate-300">Mentions légales</a>
                    <a href="#" class="hover:text-slate-300">Politique de confidentialité</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Script Menu Mobile -->
    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function () {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Recherche de solution : préremplit le formulaire de contact puis y fait défiler la page.
        document.getElementById('need-finder').addEventListener('submit', function (event) {
            event.preventDefault();

            const finder = event.target.elements;
            const contactService = document.querySelector('#contact select[name="service"]');
            const contactMessage = document.querySelector('#contact textarea[name="message"]');
            const selectedText = (select) => select.value === '' ? null : select.options[select.selectedIndex].text;

            if (finder.need.value !== '') {
                contactService.value = finder.need.value;
            }

            const details = [
                selectedText(finder.profile) && 'Je suis : ' + selectedText(finder.profile).toLowerCase(),
                selectedText(finder.sector) && "Secteur d'activité : " + selectedText(finder.sector).toLowerCase(),
                selectedText(finder.need) && 'Besoin : ' + selectedText(finder.need).toLowerCase(),
            ].filter(Boolean);

            if (details.length > 0 && contactMessage.value.trim() === '') {
                contactMessage.value = details.join('\n') + '\n\n';
            }

            document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
        });
    </script>
</body>
</html>