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
            subject: 'Vos accès à l\'espace client — '.config('app.name'),
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
                'introduction' => 'Le cabinet vous a ouvert un accès à son espace client'
                    .($organizationName ? " pour {$organizationName}" : '')
                    .'. Vous pourrez y suivre vos dossiers, déposer vos pièces et consulter les documents qui vous sont destinés.',
                'loginUrl' => route('client.login'),
            ],
        );
    }
}
