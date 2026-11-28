<?php

namespace App\Models;

use App\Notifications\ActivityNotification;
use Database\Factories\DocumentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * Demande de pièces adressée par le cabinet au client.
 *
 * Parcours : En attente → Reçue ou Rejetée (réponse du client) → Clôturée (par le cabinet, après la discussion).
 * Une demande rejetée ou clôturée n'accepte plus aucune action.
 */
#[Fillable(['folder_id', 'reference', 'title', 'description', 'status_id', 'last_reminded_at'])]
class DocumentRequest extends Model
{
    /** @use HasFactory<DocumentRequestFactory> */
    use HasFactory, HasUuids;

    public const PENDING = 'pending';

    public const RECEIVED = 'received';

    public const REJECTED = 'rejected';

    public const CLOSED = 'closed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_reminded_at' => 'datetime',
            'responded_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Prochaine référence de l'année : DEM-2026-0001, DEM-2026-0002…
     */
    public static function nextReference(): string
    {
        $prefix = 'DEM-'.now()->year.'-';

        $lastNumber = static::query()
            ->where('reference', 'like', $prefix.'%')
            ->pluck('reference')
            ->map(fn (string $reference): int => (int) Str::after($reference, $prefix))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Identifiant du statut « demande de pièces » portant ce code.
     */
    public static function statusId(string $code): ?string
    {
        return Status::ofType(StatusType::DOCUMENT_REQUEST)->where('code', $code)->value('id');
    }

    public function hasStatus(string $code): bool
    {
        return $this->status?->code === $code;
    }

    /**
     * Passe la demande au statut indiqué (sans l'enregistrer).
     */
    public function moveTo(string $code): void
    {
        $this->status_id = static::statusId($code);
        $this->unsetRelation('status');
    }

    /**
     * Prévient le cabinet d'une action du client : l'auteur de la demande et le responsable du dossier.
     */
    public function notifyCabinet(string $message): void
    {
        $this->loadMissing(['requester', 'folder.managers']);

        ActivityNotification::sendTo(
            [$this->requester, $this->folder->manager],
            $message,
            route('cabinet.document-requests.show', $this),
        );
    }

    /**
     * Prévient les utilisateurs actifs de l'entreprise d'une action du cabinet.
     */
    public function notifyClient(string $message): void
    {
        $this->loadMissing('folder');

        ActivityNotification::sendTo(
            ClientUser::query()->active()->where('organization_id', $this->folder->organization_id)->get(),
            $message,
            route('client.document-requests.show', $this),
        );
    }

    /**
     * Demandes dont le dossier est dans le périmètre de l'utilisateur (cabinet ou client).
     */
    #[Scope]
    protected function visibleTo(Builder $query, User|ClientUser $user): void
    {
        $query->whereHas('folder', fn (Builder $query) => $query->visibleTo($user));
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Collaborateur à l'origine de la demande.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Utilisateur client qui a marqué la demande reçue ou rejetée.
     *
     * @return BelongsTo<ClientUser, $this>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'responded_by');
    }

    /**
     * Collaborateur qui a clôturé la demande.
     *
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @return BelongsTo<Status, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * @return HasMany<DocumentRequestMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(DocumentRequestMessage::class);
    }

    /**
     * Fichiers joints aux messages de la demande.
     *
     * @return HasManyThrough<DocumentMessageAttachment, DocumentRequestMessage, $this>
     */
    public function attachments(): HasManyThrough
    {
        return $this->hasManyThrough(DocumentMessageAttachment::class, DocumentRequestMessage::class);
    }
}
