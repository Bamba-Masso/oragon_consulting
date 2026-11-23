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
            subject: 'Vos accès à l\'espace collaborateur — '.config('app.name'),
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
                'introduction' => 'Un compte collaborateur vous a été créé sur la plateforme du cabinet'
                    .($roleLabel ? " (rôle : {$roleLabel})" : '')
                    .'. Vous pourrez y traiter les dossiers qui vous sont affectés.',
                'loginUrl' => route('cabinet.login'),
            ],
        );
    }
}
