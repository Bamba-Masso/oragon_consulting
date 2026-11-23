<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuids;

    /**
     * Libellés affichés des rôles du cabinet (guard « cabinet »), dans l'ordre de présentation.
     *
     * @var array<string, string>
     */
    public const CABINET_LABELS = [
        'secretaire' => 'Secrétaire',
        'comptable' => 'Comptable',
        'fiscaliste' => 'Fiscaliste',
        'financier' => 'Financier',
        'auditeur' => 'Contrôleur / auditeur',
        'manager' => 'Responsable de dossier',
        'direction' => 'Direction',
        'administrateur' => 'Administrateur',
    ];

    /**
     * Pôle métier correspondant à chaque rôle de production (les autres rôles n'ont pas de pôle).
     *
     * @var array<string, string>
     */
    public const POLES = [
        'secretaire' => 'Secrétariat',
        'comptable' => 'Comptabilité',
        'fiscaliste' => 'Fiscalité',
        'financier' => 'Finance',
        'auditeur' => 'Contrôle/Audit',
    ];

    /**
     * Libellé d'un rôle à partir de son nom technique (« comptable » → « Comptable »).
     */
    public static function labelFor(string $name): string
    {
        return self::CABINET_LABELS[$name] ?? ucfirst($name);
    }
}
