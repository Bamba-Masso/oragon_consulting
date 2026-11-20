{{--
    Modale de modification d'un utilisateur du portail client, incluse sur la liste (une par ligne) et sur la fiche.
    Attend $clientUser. L'organisation n'est pas modifiable : cela ouvrirait l'accès aux dossiers d'un autre client.
    Le nom d'utilisateur n'est pas saisi : il est recalculé si le prénom ou le nom change.
    Erreurs dans le sac « updateClientUser » ; ancienne saisie et ouverture automatique seulement
    pour l'utilisateur dont le formulaire a été soumis (champ caché client_user_id).
--}}
@php
    $modalId = 'edit-client-user-'.$clientUser->id;
    $isSubmitted = old('client_user_id') === $clientUser->id;
    $field = fn (string $name): string => "{$modalId}-{$name}";
@endphp
<x-modal-form
    :id="$modalId"
    :title="'Modifier '.$clientUser->full_name"
    :action="route('cabinet.client-users.update', $clientUser)"
    method="PUT"
    submit-label="Enregistrer"
    max-width="max-w-2xl"
    :auto-open="$isSubmitted && $errors->updateClientUser->any()"
    enctype="multipart/form-data"
>
    <input type="hidden" name="client_user_id" value="{{ $clientUser->id }}">

    <p class="text-sm text-slate-500">
        Organisation : <span class="font-medium text-navy-900">{{ $clientUser->organization?->name ?? '—' }}</span>
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input name="first_name" :id="$field('first_name')" label="Prénom" :value="$clientUser->first_name"
            bag="updateClientUser" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input name="last_name" :id="$field('last_name')" label="Nom" :value="$clientUser->last_name"
            bag="updateClientUser" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input type="email" name="email" :id="$field('email')" label="E-mail" :value="$clientUser->email"
            bag="updateClientUser" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input type="tel" name="phone" :id="$field('phone')" label="Téléphone" :value="$clientUser->phone"
            bag="updateClientUser" :submitted="$isSubmitted" autocomplete="off" />
        <div class="sm:col-span-2">
            <x-form.input name="job_title" :id="$field('job_title')" label="Fonction" :value="$clientUser->job_title"
                bag="updateClientUser" :submitted="$isSubmitted" autocomplete="off" />
        </div>
        <div class="sm:col-span-2">
            <x-form.input name="address" :id="$field('address')" label="Adresse" :value="$clientUser->address"
                bag="updateClientUser" :submitted="$isSubmitted" autocomplete="off" />
        </div>
    </div>

    <div class="flex items-start gap-4">
        @if ($clientUser->avatar_url)
            <img src="{{ $clientUser->avatar_url }}" alt="" class="mt-7 w-12 h-12 shrink-0 rounded-full object-cover ring-1 ring-slate-200">
        @else
            <span class="mt-7 w-12 h-12 shrink-0 flex items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-gold-500">{{ $clientUser->initials }}</span>
        @endif
        <div class="flex-1">
            <x-form.input type="file" name="avatar" :id="$field('avatar')" label="Photo" bag="updateClientUser" :submitted="$isSubmitted"
                accept="image/jpeg,image/png,image/webp" hint="Laisser vide pour conserver la photo actuelle. JPG, PNG ou WEBP, 2 Mo maximum."
                class="file:mr-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white" />
        </div>
    </div>

    <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
        Nom d'utilisateur actuel : <span class="font-semibold text-navy-900">{{ $clientUser->username }}</span>.
        Si le prénom, le nom ou l'e-mail change, les identifiants sont mis à jour et envoyés par e-mail à l'utilisateur. Son mot de passe ne change pas.
    </p>
</x-modal-form>
