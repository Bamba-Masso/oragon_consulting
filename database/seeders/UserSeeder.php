<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Collaborateurs du cabinet : l'administrateur fonctionnel initial.
 */
class UserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'goub.bamba@uvci.edu.ci';

    /**
     * Mot de passe en clair : il est haché automatiquement (cast « hashed » du modèle User)
     * avant l'enregistrement en base.
     */
    public const ADMIN_PASSWORD = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administrator = User::firstOrNew(['email' => self::ADMIN_EMAIL]);

        $administrator->fill([
            'username' => 'administrateur',
            'first_name' => 'Admin',
            'last_name' => 'Cabinet',
            'phone' => '+225 07 00 00 00 00',
            'is_active' => true,
        ]);

        if (! $administrator->exists) {
            $administrator->password = self::ADMIN_PASSWORD;
            $administrator->email_verified_at = now();
        }

        $administrator->save();
        $administrator->syncRoles(['administrateur']);
    }
}
