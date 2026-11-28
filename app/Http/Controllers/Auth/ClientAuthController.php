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
    use ResetsForgottenPasswords, ThrottlesLoginAttempts;

    protected function space(): string
    {
        return 'client';
    }

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

        $this->ensureLoginIsNotLocked($request, 'client');

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $isAuthenticated = Auth::guard('client')->attemptWhen(
            [$field => $credentials['login'], 'password' => $credentials['password'], 'is_active' => true],
            fn (ClientUser $clientUser): bool => (bool) $clientUser->organization?->is_active,
            $request->boolean('remember'),
        );

        if (! $isAuthenticated) {
            if ($this->isBlockedAccount($field, $credentials)) {
                throw ValidationException::withMessages(['login' => self::BLOCKED_ACCOUNT_MESSAGE]);
            }

            $this->recordFailedLogin($request, 'client');

            throw ValidationException::withMessages([
                'login' => 'Identifiant ou mot de passe incorrect.',
            ]);
        }

        $this->clearFailedLogins($request, 'client');
        $request->session()->regenerate();

        Auth::guard('client')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('client.dashboard'));
    }

    /**
     * Bons identifiants, mais compte ou entreprise désactivé. Ne répond vrai qu'avec le bon mot de passe,
     * pour ne pas révéler l'état d'un compte à quelqu'un qui ne le connaît pas.
     *
     * @param  array{login: string, password: string}  $credentials
     */
    private function isBlockedAccount(string $field, array $credentials): bool
    {
        $provider = Auth::guard('client')->getProvider();
        $clientUser = $provider->retrieveByCredentials([$field => $credentials['login']]);

        return $clientUser instanceof ClientUser
            && (! $clientUser->is_active || ! $clientUser->organization?->is_active)
            && $provider->validateCredentials($clientUser, ['password' => $credentials['password']]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
