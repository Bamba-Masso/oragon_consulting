{{--
    E-mail « Mot de passe oublié » (collaborateur du cabinet ou utilisateur du portail client).
    Variables : $account, $spaceLabel, $resetUrl, $expiresInMinutes. Même présentation que mail/account-access.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de votre mot de passe</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Helvetica, Arial, sans-serif; color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden;">

                    {{-- Logo du cabinet, intégré à l'e-mail (une image hébergée sur le site ne s'afficherait pas toujours) --}}
                    @if (file_exists(public_path('logo.png')))
                        <tr>
                            <td align="center" style="padding:24px 32px; background-color:#ffffff;">
                                <img src="{{ $message->embed(public_path('logo.png')) }}" alt="{{ config('app.name') }}" height="48" style="display:block; height:48px; width:auto; border:0;">
                            </td>
                        </tr>
                    @endif

                    {{-- En-tête --}}
                    <tr>
                        <td style="background-color:#0f172a; padding:28px 32px;">
                            <p style="margin:0; font-size:11px; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:#f59e0b;">{{ $spaceLabel }}</p>
                            <p style="margin:8px 0 0; font-size:20px; font-weight:700; color:#ffffff;">Mot de passe oublié ?</p>
                        </td>
                    </tr>

                    {{-- Message --}}
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; font-size:16px; font-weight:600; color:#0f172a;">
                                Bonjour {{ $account->first_name ?: $account->full_name }},
                            </p>
                            <p style="margin:0; font-size:14px; line-height:22px;">
                                Vous avez demandé à réinitialiser votre mot de passe. Pas de souci : cliquez sur le bouton ci-dessous pour en choisir un nouveau.
                            </p>

                            {{-- Bouton --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                                <tr>
                                    <td style="background-color:#0f172a; border-radius:12px;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block; padding:14px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                                            Choisir un nouveau mot de passe
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px; font-size:13px; line-height:20px; color:#64748b;">
                                Ce lien est valable {{ $expiresInMinutes }} minutes et ne peut servir qu'une seule fois.
                            </p>

                            <p style="margin:0; padding:14px 16px; font-size:13px; line-height:20px; color:#92400e; background-color:#fffbeb; border-radius:10px;">
                                Vous n'êtes pas à l'origine de cette demande ? Ignorez simplement cet e-mail : votre mot de passe actuel reste inchangé.
                            </p>
                        </td>
                    </tr>

                    {{-- Pied --}}
                    <tr>
                        <td style="padding:20px 32px; border-top:1px solid #f1f5f9;">
                            <p style="margin:0; font-size:12px; line-height:18px; color:#94a3b8;">
                                Ne transférez pas cet e-mail : le lien qu'il contient permet de modifier votre mot de passe.<br>
                                © {{ date('Y') }} {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:16px 0 0; font-size:11px; line-height:16px; color:#94a3b8;">
                    Le bouton ne fonctionne pas ? Copiez ce lien dans votre navigateur :<br>
                    <span style="word-break:break-all;">{{ $resetUrl }}</span>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
