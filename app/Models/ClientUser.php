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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * Utilisateur du portail client, rattaché à une organisation cliente (guard « client »).
 */
#[Fillable(['organization_id', 'username', 'first_name', 'last_name', 'email', 'password', 'phone', 'address', 'job_title', 'avatar_path', 'is_active', 'created_by', 'updated_by'])]
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
     * Initiales affichées à la place de la photo : « Aya Kouamé » → « AK ».
     *
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => collect(explode(' ', $this->full_name))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join(''));
    }

    /**
     * URL publique de la photo de profil, ou null s'il n'y en a pas.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar_path
            ? Storage::disk('public')->url($this->avatar_path)
            : null);
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

    /**
     * Nom d'utilisateur libre construit à partir du prénom et du nom : « Aya Kouamé » → « aya.kouame »,
     * puis « aya.kouame2 », « aya.kouame3 »… s'il est déjà pris (comptes supprimés compris).
     * $ignoredClientUser : utilisateur dont on recalcule le nom, son nom actuel ne compte pas comme pris.
     */
    public static function generateUsername(string $firstName, string $lastName, ?self $ignoredClientUser = null): string
    {
        $base = Str::of("{$firstName} {$lastName}")->slug('.')->limit(40, '')->trim('.')->toString() ?: 'client';

        $takenUsernames = static::withTrashed()
            ->when($ignoredClientUser, fn (Builder $query) => $query->whereKeyNot($ignoredClientUser->getKey()))
            ->where('username', 'like', "{$base}%")
            ->pluck('username')
            ->map(fn (string $username): string => mb_strtolower($username))
            ->all();

        $username = $base;

        for ($suffix = 2; in_array($username, $takenUsernames, true); $suffix++) {
            $username = $base.$suffix;
        }

        return $username;
    }
}
