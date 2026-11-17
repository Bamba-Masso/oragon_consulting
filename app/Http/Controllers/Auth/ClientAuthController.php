<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClientUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientAuthController extends Controller
{
    public function index(): View
    {
        return view('auth', ['space' => 'client']);
    }

    /**
     * Connecte un utilisateur du portail client (par e-mail ou nom d'utilisateur).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Saisissez votre e-mail ou votre nom d\'utilisateur.',
            'password.required' => 'Saisissez votre mot de passe.',
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $isAuthenticated = Auth::guard('client')->attemptWhen(
            [$field => $credentials['login'], 'password' => $credentials['password'], 'is_active' => true],
            fn (ClientUser $clientUser): bool => (bool) $clientUser->organization?->is_active,
            $request->boolean('remember'),
        );

        if (! $isAuthenticated) {
            throw ValidationException::withMessages([
                'login' => 'Identifiant ou mot de passe incorrect.',
            ]);
        }

        $request->session()->regenerate();

        Auth::guard('client')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('client.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
