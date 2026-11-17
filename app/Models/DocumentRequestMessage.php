<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Message échangé sur une demande de pièces, par le cabinet ou par le client.
 */
#[Fillable(['document_request_id', 'message', 'read_at'])]
class DocumentRequestMessage extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DocumentRequest, $this>
     */
    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    /**
     * Expéditeur : collaborateur (User) ou client (ClientUser).
     *
     * @return MorphTo<Model, $this>
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<DocumentMessageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentMessageAttachment::class);
    }
}
