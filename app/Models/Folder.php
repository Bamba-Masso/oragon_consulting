<?php

namespace App\Models;

use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Dossier client : une mission sur une période, rattachée à une organisation.
 */
#[Fillable([
    'organization_id',
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
     * Types de mission proposés à la saisie (une autre valeur reste possible).
     *
     * @var list<string>
     */
    public const MISSION_TYPES = [
        'Tenue comptable',
        'Révision des comptes',
        'Déclarations fiscales',
        'Conseil fiscal',
        'Analyse financière',
        'Audit et contrôle',
        'Conformité',
        'Secrétariat juridique',
    ];

    /**
     * Parcours d'un dossier : En attente → En cours ⇄ Suspendu → Terminé.
     */
    public const PENDING = 'pending';

    public const IN_PROGRESS = 'in_progress';

    /**
     * Un problème est survenu sur le dossier ; « Reprendre » le remet en cours.
     */
    public const SUSPENDED = 'suspended';

    /**
     * Dossier terminé : définitif, plus aucune action n'est possible (consultation seule).
     */
    public const CLOSED = 'closed';

    /**
     * Code du statut d'un dossier qui vient d'être ouvert.
     */
    public const INITIAL_STATUS = self::PENDING;

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
     * Prochaine référence libre de l'année : DOS-2026-0001, DOS-2026-0002…
     * (dossiers supprimés compris, pour ne jamais réutiliser une référence).
     */
    public static function nextReference(): string
    {
        $prefix = 'DOS-'.now()->year.'-';

        $lastNumber = static::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->pluck('reference')
            ->map(fn (string $reference): int => (int) Str::after($reference, $prefix))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function statusId(string $code): ?string
    {
        return Status::ofType(StatusType::FOLDER)->where('code', $code)->value('statuses.id');
    }

    public function hasStatus(string $code): bool
    {
        return $this->status?->code === $code;
    }

    /**
     * Le dossier est terminé : plus aucune action n'est possible.
     */
    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * Le collaborateur est le responsable du dossier.
     */
    public function isManagedBy(User $user): bool
    {
        if ($this->relationLoaded('managers')) {
            return $this->managers->contains($user);
        }

        return $this->managers()->whereKey($user->getKey())->exists();
    }

    /**
     * Le dossier a dépassé son échéance sans être clôturé.
     *
     * @return Attribute<bool, never>
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn (): bool => $this->due_date !== null
            && $this->closed_at === null
            && $this->due_date->isBefore(today()));
    }

    /**
     * Collaborateur qui a ouvert le dossier.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Historique des statuts, du plus récent au plus ancien.
     *
     * @return HasMany<FolderStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(FolderStatusChange::class)->latest('created_at');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Collaborateurs affectés au dossier (équipe, responsable compris).
     *
     * @return BelongsToMany<User, $this, FolderAssignment>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'folder_assignments')
            ->using(FolderAssignment::class)
            ->withPivot(['id', 'is_manager', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * Affecté marqué « responsable » (un seul par dossier). À charger pour utiliser $folder->manager.
     *
     * @return BelongsToMany<User, $this, FolderAssignment>
     */
    public function managers(): BelongsToMany
    {
        return $this->assignees()->wherePivot('is_manager', true);
    }

    /**
     * Responsable du dossier, ou null s'il n'est pas encore désigné.
     *
     * @return Attribute<User|null, never>
     */
    protected function manager(): Attribute
    {
        return Attribute::get(fn (): ?User => $this->managers->first());
    }

    /**
     * Aligne l'équipe sur la sélection : ajoute les nouveaux membres, retire ceux qui n'y sont plus
     * (les membres conservés gardent leur « assigned_by »), puis désigne le responsable,
     * qui fait toujours partie de l'équipe.
     *
     * @param  list<string>  $memberIds
     */
    public function syncTeam(array $memberIds, ?string $managerId, User $assignedBy): void
    {
        $memberIds = collect($memberIds)->push($managerId)->filter()->unique()->values();
        $currentIds = $this->assignees()->pluck('users.id');

        $this->assignees()->detach($currentIds->diff($memberIds)->all());

        foreach ($memberIds->diff($currentIds) as $memberId) {
            $this->assignees()->attach($memberId, ['assigned_by' => $assignedBy->getKey()]);
        }

        $this->assignManager($managerId ? User::find($managerId) : null, $assignedBy);
    }

    /**
     * Désigne le responsable (null : aucun). L'ancien responsable reste dans l'équipe comme simple affecté,
     * le nouveau y est ajouté s'il n'en faisait pas partie.
     */
    public function assignManager(?User $manager, User $assignedBy): void
    {
        $this->assignees()->newPivotQuery()->update(['is_manager' => false]);

        if ($manager === null) {
            return;
        }

        if ($this->assignees()->whereKey($manager->getKey())->exists()) {
            $this->assignees()->updateExistingPivot($manager->getKey(), ['is_manager' => true]);

            return;
        }

        $this->assignees()->attach($manager, ['is_manager' => true, 'assigned_by' => $assignedBy->getKey()]);
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
     * - autre collaborateur : les dossiers auxquels il est affecté (comme responsable ou non)
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
            $query->whereHas('assignees', fn (Builder $query) => $query->whereKey($user->getKey()))
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
