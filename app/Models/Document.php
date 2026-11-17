<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pièce ou livrable rattaché à un dossier.
 *
 * La publication au client (is_published_to_client, published_at, published_by)
 * n'est volontairement pas assignable en masse : elle passe par une action dédiée.
 */
#[Fillable([
    'organization_id',
    'folder_id',
    'name',
    'original_filename',
    'file_path',
    'mime_type',
    'file_size',
    'category',
    'period',
    'confidentiality_level',
    'parent_document_id',
    'version',
])]
#[Hidden(['file_path'])]
class Document extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'version' => 'integer',
            'is_published_to_client' => 'boolean',
            'published_at' => 'datetime',
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
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Auteur du dépôt : collaborateur (User) ou client (ClientUser).
     *
     * @return MorphTo<Model, $this>
     */
    public function uploader(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Version précédente du document.
     *
     * @return BelongsTo<Document, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    /**
     * Versions ultérieures du document.
     *
     * @return HasMany<Document, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    #[Scope]
    protected function publishedToClient(Builder $query): void
    {
        $query->where('is_published_to_client', true);
    }
}
