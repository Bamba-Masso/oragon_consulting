<?php

namespace App\Notifications;

use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationSender;

/**
 * Notification de la cloche (canal « database »), pour les collaborateurs comme pour les clients :
 * un message et la page à ouvrir au clic.
 */
class ActivityNotification extends Notification
{
    public function __construct(public string $message, public ?string $url = null) {}

    /**
     * Envoie la notification aux destinataires actifs, sans doublon et jamais à l'auteur de l'action.
     *
     * @param  iterable<int, User|ClientUser|null>  $recipients
     */
    public static function sendTo(iterable $recipients, string $message, ?string $url = null, User|ClientUser|null $author = null): void
    {
        $recipients = collect($recipients)
            ->filter(fn (User|ClientUser|null $recipient): bool => $recipient !== null && $recipient->is_active)
            ->reject(fn (User|ClientUser $recipient): bool => $author !== null && $recipient->is($author))
            ->unique(fn (User|ClientUser $recipient): string => $recipient::class.$recipient->getKey());

        NotificationSender::send($recipients, new self($message, $url));
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{message: string, url: string|null}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
