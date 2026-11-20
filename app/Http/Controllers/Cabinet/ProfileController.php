<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Profil du collaborateur connecté : consultation, coordonnées et mot de passe.
 * L'e-mail et le nom d'utilisateur servent d'identifiants : seul l'administrateur les modifie.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('cabinet.profile', [
            'user' => $request->user(User::GUARD),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('profile', [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'first_name.required' => 'Saisissez votre prénom.',
            'last_name.required' => 'Saisissez votre nom.',
            '*.max' => 'Ce champ est trop long.',
        ]);

        $request->user(User::GUARD)->update($validated);

        return to_route('cabinet.profile')->with('status', 'Vos informations ont été enregistrées.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:'.User::GUARD],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.required' => 'Saisissez votre mot de passe actuel.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required' => 'Saisissez un nouveau mot de passe.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ]);

        $request->user(User::GUARD)->update(['password' => $validated['password']]);

        return to_route('cabinet.profile')->with('status', 'Votre mot de passe a été modifié.');
    }
}
