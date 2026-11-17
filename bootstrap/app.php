<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Visiteur non connecté sur une page protégée : vers la bonne page de connexion.
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('cabinet', 'cabinet/*', 'auth/Administration/*')
            ? route('cabinet.login')
            : route('client.login'));

        // Utilisateur déjà connecté sur une page de connexion : vers son tableau de bord.
        $middleware->redirectUsersTo(fn (Request $request): string => $request->is('auth/Administration/*')
            ? route('cabinet.dashboard')
            : route('client.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
