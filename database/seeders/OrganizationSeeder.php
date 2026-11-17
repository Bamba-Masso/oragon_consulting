<?php

namespace Database\Seeders;

use App\Models\ClientUser;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration : une organisation cliente et son utilisateur du portail.
 * N'est exécuté qu'hors production (voir DatabaseSeeder).
 */
class OrganizationSeeder extends Seeder
{
    public const DEMO_REGISTRATION_NUMBER = 'DEMO-ORG-001';

    public const DEMO_CLIENT_EMAIL = 'mansogbebamba154@gmail.com';

    /**
     * Mot de passe en clair : il est haché automatiquement (cast « hashed » du modèle ClientUser)
     * avant l'enregistrement en base.
     */
    public const DEMO_CLIENT_PASSWORD = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administrator = User::where('email', UserSeeder::ADMIN_EMAIL)->firstOrFail();

        $organization = Organization::firstOrNew(['registration_number' => self::DEMO_REGISTRATION_NUMBER]);

        $organization->fill([
            'name' => 'Entreprise Démo',
            'legal_form' => 'SARL',
            'tax_number' => 'DEMO-NCC-001',
            'email' => 'contact@entreprise-demo.test',
            'phone' => '+225 07 22 22 22 22',
            'address' => 'Plateau, Avenue de la République',
            'city' => 'Abidjan',
            'is_active' => true,
        ]);

        if (! $organization->exists) {
            $organization->created_by = $administrator->id;
        }

        $organization->updated_by = $administrator->id;
        $organization->save();

        $clientUser = ClientUser::firstOrNew(['email' => self::DEMO_CLIENT_EMAIL]);

        $clientUser->fill([
            'username' => 'client-demo',
            'first_name' => 'Client',
            'last_name' => 'Démo',
            'phone' => '+225 07 11 11 11 11',
            'job_title' => 'Responsable administratif',
            'is_active' => true,
        ]);

        $clientUser->organization()->associate($organization);

        if (! $clientUser->exists) {
            $clientUser->password = self::DEMO_CLIENT_PASSWORD;
            $clientUser->email_verified_at = now();
            $clientUser->created_by = $administrator->id;
        }

        $clientUser->updated_by = $administrator->id;
        $clientUser->save();
        $clientUser->syncRoles(['client']);
    }
}
