<?php

namespace App\Mail;

use App\Models\ClientUser;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail d'ouverture de l'espace client : identifiant et mot de passe temporaire.
 *
 * Volontairement non mis en file d'attente (pas de ShouldQueue) : le mot de passe en clair
 * serait sinon sérialisé et stocké dans la table des jobs.
 */
class ClientAccessCredentials extends Mailable
{
    public function __construct(
        public ClientUser $clientUser,
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
        $organizationName = $this->clientUser->organization?->name;

        return new Content(
            view: 'mail.account-access',
            with: [
                'account' => $this->clientUser,
                'spaceLabel' => 'Espace client',
                'introduction' => 'Votre compte vient d\'être créé sur notre portail client'
                    .($organizationName ? " pour {$organizationName}" : '')
                    .'. Vous pouvez dès maintenant vous connecter sur la plateforme pour suivre vos dossiers et échanger avec notre équipe.',
                'loginUrl' => route('client.login'),
                'buttonLabel' => 'Accéder à mon espace client',
                'showUsername' => false,
            ],
        );
    }
}
