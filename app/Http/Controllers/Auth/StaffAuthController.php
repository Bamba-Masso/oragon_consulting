<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffAuthController extends Controller
{
    public function index(): View
    {
        return view('auth', ['space' => 'cabinet']);
    }

    /**
     * Connecte un collaborateur du cabinet (par e-mail ou nom d'utilisateur).
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

        $isAuthenticated = Auth::guard('cabinet')->attempt(
            [$field => $credentials['login'], 'password' => $credentials['password'], 'is_active' => true],
            $request->boolean('remember'),
        );

        if (! $isAuthenticated) {
            throw ValidationException::withMessages([
                'login' => 'Identifiant ou mot de passe incorrect.',
            ]);
        }

        $request->session()->regenerate();

        Auth::guard('cabinet')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('cabinet.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('cabinet')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
