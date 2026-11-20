<?php

namespace App\Models;

use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dossier client : une mission sur une période, rattachée à une organisation.
 */
#[Fillable([
    'organization_id',
    'assigned_manager_id',
    'status_id',
    'priority_id',
    'reference',
    'title',
    'mission_type',
    'period',
    'due_date',
    'closed_at',
])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Responsable du dossier.
     *
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_manager_id');
    }

    /**
     * Collaborateurs affectés au dossier.
     *
     * @return BelongsToMany<User, $this, FolderAssignment>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'folder_assignments')
            ->using(FolderAssignment::class)
            ->withPivot(['id', 'pole', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Status, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * @return BelongsTo<Priority, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<DocumentRequest, $this>
     */
    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    /**
     * @return HasMany<Anomaly, $this>
     */
    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class);
    }

    /**
     * Restreint aux dossiers que l'utilisateur a le droit de voir :
     * - client : les dossiers de son organisation ;
     * - collaborateur avec « viewAll-folders » : tous les dossiers ;
     * - autre collaborateur : les dossiers dont il est responsable, auxquels il est affecté
     *   ou dans lesquels une tâche lui est assignée.
     */
    #[Scope]
    protected function visibleTo(Builder $query, User|ClientUser $user): void
    {
        if ($user instanceof ClientUser) {
            $query->where('organization_id', $user->organization_id);

            return;
        }

        if ($user->can('viewAll-folders')) {
            return;
        }

        $query->where(function (Builder $query) use ($user): void {
            $query->where('assigned_manager_id', $user->getKey())
                ->orWhereHas('assignees', fn (Builder $query) => $query->whereKey($user->getKey()))
                ->orWhereHas('tasks', fn (Builder $query) => $query->where('assigned_to', $user->getKey()));
        });
    }

    /**
     * Applique la même règle que le scope visibleTo() à ce dossier précis
     * (y compris s'il est supprimé logiquement, pour la restauration).
     */
    public function isVisibleTo(User|ClientUser $user): bool
    {
        return static::withTrashed()
            ->visibleTo($user)
            ->whereKey($this->getKey())
            ->exists();
    }
}
