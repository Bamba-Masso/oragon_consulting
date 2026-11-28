<?php

namespace App\Mail;

use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail « Mot de passe oublié » : lien pour choisir un nouveau mot de passe, valable une durée limitée.
 *
 * Volontairement non mis en file d'attente (pas de ShouldQueue) : le jeton de réinitialisation
 * serait sinon sérialisé et stocké dans la table des jobs.
 */
class PasswordResetLink extends Mailable
{
    public function __construct(
        public User|ClientUser $account,
        #[\SensitiveParameter] public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialisation de votre mot de passe Oragon Consulting',
        );
    }

    public function content(): Content
    {
        $isCabinet = $this->account instanceof User;

        return new Content(
            view: 'mail.password-reset',
            with: [
                'spaceLabel' => $isCabinet ? 'Espace collaborateur' : 'Espace client',
                'resetUrl' => route($isCabinet ? 'cabinet.password.reset' : 'client.password.reset', [
                    'token' => $this->token,
                    'email' => $this->account->email,
                ]),
                'expiresInMinutes' => config('auth.passwords.'.($isCabinet ? 'cabinet_users' : 'client_users').'.expire'),
            ],
        );
    }
}
