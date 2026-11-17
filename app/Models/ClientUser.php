<?php

namespace App\Models;

use Database\Factories\ClientUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
#[Fillable(['organization_id', 'username', 'first_name', 'last_name', 'email', 'password', 'phone', 'job_title', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class ClientUser extends Authenticatable
{
    /** @use HasFactory<ClientUserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

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
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim("{$this->first_name} {$this->last_name}") ?: $this->username,
        );
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
