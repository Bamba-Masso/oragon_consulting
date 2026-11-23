<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Identité affichée d'un compte (collaborateur ou utilisateur du portail client) :
 * nom complet, initiales, photo de profil et nom d'utilisateur généré.
 * Le modèle doit avoir les colonnes first_name, last_name, username et avatar_path, et utiliser SoftDeletes.
 */
trait HasProfileIdentity
{
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
     * Nom d'utilisateur libre construit à partir du prénom et du nom : « Aya Kouamé » → « aya.kouame »,
     * puis « aya.kouame2 », « aya.kouame3 »… s'il est déjà pris (comptes supprimés compris).
     * $ignoredAccount : compte dont on recalcule le nom, son nom actuel ne compte pas comme pris.
     */
    public static function generateUsername(string $firstName, string $lastName, ?self $ignoredAccount = null): string
    {
        $base = Str::of("{$firstName} {$lastName}")->slug('.')->limit(40, '')->trim('.')->toString() ?: 'utilisateur';

        $takenUsernames = static::withTrashed()
            ->when($ignoredAccount, fn (Builder $query) => $query->whereKeyNot($ignoredAccount->getKey()))
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
