{{--
    E-mail « nouveau document disponible » adressé à un utilisateur de l'entreprise.
    Variables : $account, $document (dossier chargé), $documentsUrl. Même présentation que mail/account-access.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau document disponible</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Helvetica, Arial, sans-serif; color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden;">

                    {{-- Logo du cabinet, intégré à l'e-mail --}}
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
                            <p style="margin:0; font-size:11px; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:#f59e0b;">Nouveau document</p>
                            <p style="margin:8px 0 0; font-size:20px; font-weight:700; color:#ffffff;">{{ $document->name }}</p>
                        </td>
                    </tr>

                    {{-- Message --}}
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; font-size:16px; font-weight:600; color:#0f172a;">
                                Bonjour {{ $account->first_name ?: $account->full_name }},
                            </p>
                            <p style="margin:0 0 24px; font-size:14px; line-height:22px;">
                                Le cabinet a mis un nouveau document à votre disposition. Vous pouvez le consulter et le télécharger depuis votre espace.
                            </p>

                            {{-- Détail du document --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0; font-size:12px; color:#64748b;">Dossier</p>
                                        <p style="margin:4px 0 0; font-size:15px; font-weight:600; color:#0f172a;">{{ $document->folder->reference }} — {{ $document->folder->title }}</p>

                                        <p style="margin:16px 0 0; font-size:12px; color:#64748b;">Catégorie</p>
                                        <p style="margin:4px 0 0; font-size:14px; color:#0f172a;">{{ $document->category }}@if ($document->period) · {{ $document->period }}@endif</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Bouton --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0 0;">
                                <tr>
                                    <td style="background-color:#0f172a; border-radius:12px;">
                                        <a href="{{ $documentsUrl }}" style="display:inline-block; padding:14px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                                            Voir mes documents
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Pied --}}
                    <tr>
                        <td style="padding:20px 32px; border-top:1px solid #f1f5f9;">
                            <p style="margin:0; font-size:12px; line-height:18px; color:#94a3b8;">
                                © {{ date('Y') }} {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:16px 0 0; font-size:11px; color:#94a3b8;">Lien de vos documents : {{ $documentsUrl }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
