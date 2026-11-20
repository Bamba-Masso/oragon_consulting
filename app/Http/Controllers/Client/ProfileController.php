<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Profil de l'utilisateur du portail client : consultation, coordonnées et mot de passe.
 * L'e-mail, le nom d'utilisateur et l'organisation sont gérés par le cabinet.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('client.profile', [
            'user' => $request->user(ClientUser::GUARD)->load('organization'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('profile', [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
        ], [
            'first_name.required' => 'Saisissez votre prénom.',
            'last_name.required' => 'Saisissez votre nom.',
            '*.max' => 'Ce champ est trop long.',
        ]);

        $request->user(ClientUser::GUARD)->update($validated);

        return to_route('client.profile')->with('status', 'Vos informations ont été enregistrées.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:'.ClientUser::GUARD],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.required' => 'Saisissez votre mot de passe actuel.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required' => 'Saisissez un nouveau mot de passe.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ]);

        $request->user(ClientUser::GUARD)->update(['password' => $validated['password']]);

        return to_route('client.profile')->with('status', 'Votre mot de passe a été modifié.');
    }
}
