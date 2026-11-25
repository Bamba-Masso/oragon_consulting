<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Limite des tentatives de connexion : après 4 échecs pour un même identifiant
 * depuis la même adresse IP, la connexion est bloquée pendant 2 minutes.
 */
trait ThrottlesLoginAttempts
{
    /**
     * Nombre d'échecs autorisés avant le blocage.
     */
    private const MAX_LOGIN_ATTEMPTS = 4;

    /**
     * Durée pendant laquelle les échecs sont comptés (15 minutes).
     */
    private const FAILED_ATTEMPTS_WINDOW_SECONDS = 900;

    /**
     * Durée du blocage, comptée à partir du dernier échec (2 minutes).
     */
    private const LOCKOUT_SECONDS = 120;

    /**
     * Message affiché quand les identifiants sont bons mais que le compte est désactivé.
     */
    private const BLOCKED_ACCOUNT_MESSAGE = 'Désolé, votre compte a été bloqué. Veuillez contacter l\'administrateur pour réactiver vos accès.';

    /**
     * Refuse la tentative tant que le blocage est en cours.
     *
     * @throws ValidationException
     */
    protected function ensureLoginIsNotLocked(Request $request, string $space): void
    {
        $lockoutKey = $this->lockoutKey($request, $space);

        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            $this->throwLockout(RateLimiter::availableIn($lockoutKey));
        }
    }

    /**
     * Compte l'échec ; au 4e, déclenche le blocage de 2 minutes.
     *
     * @throws ValidationException
     */
    protected function recordFailedLogin(Request $request, string $space): void
    {
        $attemptsKey = $this->loginAttemptsKey($request, $space);

        RateLimiter::hit($attemptsKey, self::FAILED_ATTEMPTS_WINDOW_SECONDS);

        if (RateLimiter::attempts($attemptsKey) < self::MAX_LOGIN_ATTEMPTS) {
            return;
        }

        RateLimiter::clear($attemptsKey);
        RateLimiter::hit($this->lockoutKey($request, $space), self::LOCKOUT_SECONDS);
        event(new Lockout($request));

        $this->throwLockout(self::LOCKOUT_SECONDS);
    }

    protected function clearFailedLogins(Request $request, string $space): void
    {
        RateLimiter::clear($this->loginAttemptsKey($request, $space));
    }

    /**
     * @throws ValidationException
     */
    private function throwLockout(int $seconds): never
    {
        $delay = $seconds >= 60
            ? trans_choice(':count minute|:count minutes', (int) ceil($seconds / 60))
            : trans_choice(':count seconde|:count secondes', max($seconds, 1));

        throw ValidationException::withMessages([
            'login' => "Trop de tentatives de connexion. Réessayez dans {$delay}.",
        ]);
    }

    private function loginAttemptsKey(Request $request, string $space): string
    {
        $login = Str::transliterate(Str::lower($request->string('login')->trim()->toString()));

        return "login|{$space}|{$login}|{$request->ip()}";
    }

    private function lockoutKey(Request $request, string $space): string
    {
        return $this->loginAttemptsKey($request, $space).'|lockout';
    }
}
