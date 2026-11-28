<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffAuthController extends Controller
{
    use ResetsForgottenPasswords, ThrottlesLoginAttempts;

    protected function space(): string
    {
        return 'cabinet';
    }

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

        $this->ensureLoginIsNotLocked($request, 'cabinet');

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $isAuthenticated = Auth::guard('cabinet')->attempt(
            [$field => $credentials['login'], 'password' => $credentials['password'], 'is_active' => true],
            $request->boolean('remember'),
        );

        if (! $isAuthenticated) {
            if ($this->isBlockedAccount($field, $credentials)) {
                throw ValidationException::withMessages(['login' => self::BLOCKED_ACCOUNT_MESSAGE]);
            }

            $this->recordFailedLogin($request, 'cabinet');

            throw ValidationException::withMessages([
                'login' => 'Identifiant ou mot de passe incorrect.',
            ]);
        }

        $this->clearFailedLogins($request, 'cabinet');
        $request->session()->regenerate();

        Auth::guard('cabinet')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('cabinet.dashboard'));
    }

    /**
     * Bons identifiants, mais compte désactivé. Ne répond vrai qu'avec le bon mot de passe,
     * pour ne pas révéler l'état d'un compte à quelqu'un qui ne le connaît pas.
     *
     * @param  array{login: string, password: string}  $credentials
     */
    private function isBlockedAccount(string $field, array $credentials): bool
    {
        $provider = Auth::guard('cabinet')->getProvider();
        $user = $provider->retrieveByCredentials([$field => $credentials['login']]);

        return $user instanceof User
            && ! $user->is_active
            && $provider->validateCredentials($user, ['password' => $credentials['password']]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('cabinet')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
