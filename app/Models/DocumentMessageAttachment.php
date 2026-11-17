<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_request_message_id', 'file_name', 'file_path', 'file_type', 'file_size'])]
#[Hidden(['file_path'])]
class DocumentMessageAttachment extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DocumentRequestMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(DocumentRequestMessage::class, 'document_request_message_id');
    }
}
