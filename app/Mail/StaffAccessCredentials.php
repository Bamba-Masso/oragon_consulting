<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail d'ouverture d'un compte collaborateur : nom d'utilisateur, e-mail et mot de passe temporaire.
 *
 * Volontairement non mis en file d'attente (pas de ShouldQueue) : le mot de passe en clair
 * serait sinon sérialisé et stocké dans la table des jobs.
 */
class StaffAccessCredentials extends Mailable
{
    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue sur Oragon Consulting',
        );
    }

    public function content(): Content
    {
        $roleLabel = $this->user->role_label;

        return new Content(
            view: 'mail.account-access',
            with: [
                'account' => $this->user,
                'spaceLabel' => 'Espace collaborateur',
                'introduction' => 'Votre compte vient d\'être créé dans l\'espace collaborateur'
                    .($roleLabel ? " avec le profil {$roleLabel}" : '')
                    .'. Vous pouvez dès maintenant vous connecter sur la plateforme.',
                'loginUrl' => route('cabinet.login'),
                'buttonLabel' => 'Accéder à mon espace',
                'showUsername' => true,
            ],
        );
    }
}
