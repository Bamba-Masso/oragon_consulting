<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Nettoyage de la cloche : les notifications lues depuis plus de 7 jours sont supprimées.
 * Les notifications non lues sont toujours conservées.
 */
Artisan::command('notifications:prune {--days=7 : Ancienneté minimale de la lecture, en jours}', function () {
    $deleted = DatabaseNotification::query()
        ->whereNotNull('read_at')
        ->where('read_at', '<', now()->subDays((int) $this->option('days')))
        ->delete();

    $this->info("{$deleted} notification(s) lue(s) supprimée(s).");
})->purpose('Supprime les notifications lues depuis plus de 7 jours');

Schedule::command('notifications:prune')->daily();
