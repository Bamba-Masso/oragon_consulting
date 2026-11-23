{{--
    Modale de modification d'un collaborateur, incluse sur la liste (une par ligne) et sur la fiche.
    Attend $collaborator et $assignableRoles. Le nom d'utilisateur n'est pas saisi : il est recalculé
    si le prénom ou le nom change. Un collaborateur ne modifie pas son propre rôle.
    Erreurs dans le sac « updateUser » ; ancienne saisie et ouverture automatique seulement
    pour le collaborateur dont le formulaire a été soumis (champ caché user_id).
--}}
@php
    $modalId = 'edit-user-'.$collaborator->id;
    $isSubmitted = old('user_id') === $collaborator->id;
    $isSelf = $collaborator->is(auth('cabinet')->user());
    $field = fn (string $name): string => "{$modalId}-{$name}";
@endphp
<x-modal-form
    :id="$modalId"
    :title="'Modifier '.$collaborator->full_name"
    :action="route('cabinet.users.update', $collaborator)"
    method="PUT"
    submit-label="Enregistrer"
    max-width="max-w-2xl"
    :auto-open="$isSubmitted && $errors->updateUser->any()"
    enctype="multipart/form-data"
>
    <input type="hidden" name="user_id" value="{{ $collaborator->id }}">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input name="first_name" :id="$field('first_name')" label="Prénom" :value="$collaborator->first_name"
            bag="updateUser" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input name="last_name" :id="$field('last_name')" label="Nom" :value="$collaborator->last_name"
            bag="updateUser" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input type="email" name="email" :id="$field('email')" label="E-mail" :value="$collaborator->email"
            bag="updateUser" :submitted="$isSubmitted" required autocomplete="off" />
        <x-form.input type="tel" name="phone" :id="$field('phone')" label="Téléphone" :value="$collaborator->phone"
            bag="updateUser" :submitted="$isSubmitted" autocomplete="off" />
    </div>

    @if ($isSelf)
        <p class="text-sm text-slate-500">
            Rôle : <span class="font-medium text-navy-900">{{ $collaborator->role_label ?? '—' }}</span>
            <span class="text-xs">(vous ne pouvez pas modifier votre propre rôle)</span>
        </p>
    @else
        <x-form.select name="role" :id="$field('role')" label="Rôle" :options="$assignableRoles" :value="$collaborator->getRoleNames()->first()"
            bag="updateUser" :submitted="$isSubmitted" placeholder="Choisir un rôle…" required />
    @endif

    <x-form.input name="address" :id="$field('address')" label="Adresse" :value="$collaborator->address"
        bag="updateUser" :submitted="$isSubmitted" autocomplete="off" />

    <div class="flex items-start gap-4">
        @if ($collaborator->avatar_url)
            <img src="{{ $collaborator->avatar_url }}" alt="" class="mt-7 w-12 h-12 shrink-0 rounded-full object-cover ring-1 ring-slate-200">
        @else
            <span class="mt-7 w-12 h-12 shrink-0 flex items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-gold-500">{{ $collaborator->initials }}</span>
        @endif
        <div class="flex-1">
            <x-form.input type="file" name="avatar" :id="$field('avatar')" label="Photo" bag="updateUser" :submitted="$isSubmitted"
                accept="image/jpeg,image/png,image/webp" hint="Laisser vide pour conserver la photo actuelle. JPG, PNG ou WEBP, 2 Mo maximum."
                class="file:mr-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white" />
        </div>
    </div>

    <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
        Nom d'utilisateur actuel : <span class="font-semibold text-navy-900">{{ $collaborator->username }}</span>.
        Si le prénom, le nom ou l'e-mail change, les identifiants sont mis à jour et envoyés par e-mail au collaborateur. Son mot de passe ne change pas.
    </p>
</x-modal-form>
