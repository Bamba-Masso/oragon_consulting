<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Affectation d'un collaborateur à un dossier (pivot Folder ↔ User).
 * Le modèle pivot est nécessaire pour générer l'UUID à chaque attach().
 */
class FolderAssignment extends Pivot
{
    use HasUuids;

    protected $table = 'folder_assignments';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_manager' => 'boolean',
        ];
    }

    /**
     * Collaborateur qui a réalisé l'affectation.
     *
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
