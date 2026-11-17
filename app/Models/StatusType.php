<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Famille de statuts (dossier, tâche, demande de pièce, anomalie).
 */
#[Fillable(['name', 'code'])]
class StatusType extends Model
{
    use HasUuids;

    public const FOLDER = 'folder';

    public const TASK = 'task';

    public const DOCUMENT_REQUEST = 'document_request';

    public const ANOMALY = 'anomaly';

    /**
     * @return HasMany<Status, $this>
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class);
    }
}
