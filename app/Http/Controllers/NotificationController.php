<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifications de l'utilisateur connecté, communes au cabinet et au client
 * (cloche de la barre du haut).
 */
class NotificationController extends Controller
{
    /**
     * Ouvre la page liée à la notification et la marque comme lue.
     * Une notification d'un autre utilisateur répond 404.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url ? redirect()->to($url) : back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
