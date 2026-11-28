<?php

namespace App\Http\Controllers\Auth;

use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * « Mot de passe oublié » pour un espace (cabinet ou client) : demande du lien par e-mail,
 * puis choix d'un nouveau mot de passe depuis ce lien.
 *
 * La réponse à la demande est toujours la même, que l'adresse corresponde à un compte ou non,
 * pour ne pas révéler quelles adresses sont inscrites. Seuls les comptes actifs reçoivent un lien.
 */
trait ResetsForgottenPasswords
{
    public const RESET_LINK_SENT_MESSAGE = 'Si un compte actif correspond à cette adresse, vous allez recevoir un e-mail avec un lien pour choisir un nouveau mot de passe.';

    public const INVALID_RESET_LINK_MESSAGE = 'Ce lien est invalide ou a expiré. Faites une nouvelle demande de réinitialisation.';

    public const PASSWORD_RESET_MESSAGE = 'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.';

    /**
     * Espace concerné : « cabinet » ou « client ».
     */
    abstract protected function space(): string;

    public function forgotPassword(): View
    {
        return view('auth.forgot-password', ['space' => $this->space()]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Saisissez votre adresse e-mail.',
            'email.email' => 'Saisissez une adresse e-mail valide.',
        ]);

        try {
            $this->passwordBroker()->sendResetLink(['email' => $validated['email'], 'is_active' => true]);
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'email' => 'L\'e-mail n\'a pas pu être envoyé pour le moment. Réessayez dans quelques minutes.',
            ]);
        }

        return back()->with('status', self::RESET_LINK_SENT_MESSAGE);
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'space' => $this->space(),
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'token.required' => self::INVALID_RESET_LINK_MESSAGE,
            'email.required' => 'Saisissez votre adresse e-mail.',
            'email.email' => 'Saisissez une adresse e-mail valide.',
            'password.required' => 'Saisissez un nouveau mot de passe.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ]);

        $status = $this->passwordBroker()->reset(
            [...$validated, 'is_active' => true],
            function (User|ClientUser $account, string $password): void {
                $account->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($account));
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => self::INVALID_RESET_LINK_MESSAGE,
            ]);
        }

        return to_route("{$this->space()}.login")->with('status', self::PASSWORD_RESET_MESSAGE);
    }

    /**
     * Gestionnaire de réinitialisation de l'espace (config/auth.php, clé « passwords »).
     */
    private function passwordBroker(): PasswordBroker
    {
        return Password::broker($this->space() === 'cabinet' ? 'cabinet_users' : 'client_users');
    }
}
