<?php

namespace App\Models;

use App\Mail\PasswordResetLink;
use App\Models\Concerns\HasProfileIdentity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Traits\HasRoles;

/**
 * Collaborateur du cabinet (guard « cabinet »).
 */
#[Fillable(['username', 'first_name', 'last_name', 'email', 'phone', 'address', 'avatar_path', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasProfileIdentity, HasRoles, HasUuids, Notifiable, SoftDeletes;

    /**
     * Guard d'authentification des collaborateurs (config/auth.php).
     */
    public const GUARD = 'cabinet';

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
     * Libellé du rôle principal du collaborateur (« Comptable », « Manager »…), ou null.
     *
     * @return Attribute<string|null, never>
     */
    protected function roleLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => ($role = $this->getRoleNames()->first()) ? Role::labelFor($role) : null);
    }

    /**
     * Pôle métier déduit du rôle (« Comptabilité » pour un comptable), ou null pour les rôles sans pôle.
     *
     * @return Attribute<string|null, never>
     */
    protected function pole(): Attribute
    {
        return Attribute::get(fn (): ?string => Role::POLES[$this->getRoleNames()->first()] ?? null);
    }

    /**
     * Dossiers dont le collaborateur est le responsable.
     *
     * @return BelongsToMany<Folder, $this, FolderAssignment>
     */
    public function managedFolders(): BelongsToMany
    {
        return $this->assignedFolders()->wherePivot('is_manager', true);
    }

    /**
     * Dossiers auxquels le collaborateur est affecté (comme responsable ou non).
     *
     * @return BelongsToMany<Folder, $this, FolderAssignment>
     */
    public function assignedFolders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'folder_assignments')
            ->using(FolderAssignment::class)
            ->withPivot(['id', 'is_manager', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Envoie le lien « Mot de passe oublié » avec l'e-mail du cabinet (et non la notification anglaise de Laravel).
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        Mail::to($this->email, $this->full_name)->send(new PasswordResetLink($this, $token));
    }
}
