<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail envoyé quand les identifiants de connexion d'un collaborateur changent
 * (nom d'utilisateur recalculé après un changement de nom, ou nouvelle adresse e-mail).
 * Le mot de passe, lui, ne change pas.
 */
class StaffAccessUpdated extends Mailable
{
    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos accès Oragon Consulting ont été mis à jour',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.account-access-updated',
            with: [
                'account' => $this->user,
                'spaceLabel' => 'Espace collaborateur',
                'loginUrl' => route('cabinet.login'),
                'buttonLabel' => 'Accéder à mon espace',
                'showUsername' => true,
            ],
        );
    }
}
