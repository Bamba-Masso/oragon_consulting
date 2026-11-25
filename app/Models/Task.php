<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'folder_id',
    'assigned_to',
    'priority_id',
    'depends_on_task_id',
    'title',
    'description',
    'status_id',
    'due_date',
    'completed_at',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Parcours d'une tâche : En attente → En cours → Terminée, ou Annulée.
     * Terminée et Annulée sont définitifs.
     */
    public const PENDING = 'pending';

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    /**
     * Code du statut (type « tâche ») d'une tâche qui vient d'être créée.
     */
    public const INITIAL_STATUS = self::PENDING;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public static function statusId(string $code): ?string
    {
        return Status::ofType(StatusType::TASK)->where('code', $code)->value('statuses.id');
    }

    public function hasStatus(string ...$codes): bool
    {
        return in_array($this->status?->code, $codes, true);
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Priority, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    /**
     * @return BelongsTo<Status, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Tâche qui doit être terminée avant celle-ci.
     *
     * @return BelongsTo<Task, $this>
     */
    public function dependency(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }

    /**
     * Tâches qui attendent la fin de celle-ci.
     *
     * @return HasMany<Task, $this>
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(Task::class, 'depends_on_task_id');
    }

    /**
     * @return HasMany<TaskChecklist, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('order');
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }
}
