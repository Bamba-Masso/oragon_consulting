<?php

namespace App\Models;

use App\Models\Concerns\HasProfileIdentity;
use Database\Factories\ClientUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Utilisateur du portail client, rattaché à une organisation cliente (guard « client »).
 */
#[Fillable(['organization_id', 'username', 'first_name', 'last_name', 'email', 'password', 'phone', 'address', 'job_title', 'avatar_path', 'is_active', 'created_by', 'updated_by'])]
#[Hidden(['password', 'remember_token'])]
class ClientUser extends Authenticatable
{
    /** @use HasFactory<ClientUserFactory> */
    use HasFactory, HasProfileIdentity, HasRoles, HasUuids, Notifiable, SoftDeletes;

    /**
     * Guard d'authentification du portail client (config/auth.php).
     */
    public const GUARD = 'client';

    /**
     * Guard utilisé par spatie/laravel-permission pour les rôles et permissions.
     */
    protected string $guard_name = self::GUARD;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
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
     * Collaborateur du cabinet qui a créé le compte.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
