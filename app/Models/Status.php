<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['status_type_id', 'name', 'code', 'color'])]
class Status extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<StatusType, $this>
     */
    public function statusType(): BelongsTo
    {
        return $this->belongsTo(StatusType::class);
    }

    /**
     * Restreint aux statuts d'une famille, ex. Status::ofType(StatusType::FOLDER).
     */
    #[Scope]
    protected function ofType(Builder $query, string $statusTypeCode): void
    {
        $query->whereRelation('statusType', 'code', $statusTypeCode);
    }
}
