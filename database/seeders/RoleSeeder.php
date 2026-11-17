<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'secretaire', 'guard_name' => 'cabinet'],
            ['name' => 'comptable', 'guard_name' => 'cabinet'],
            ['name' => 'fiscaliste', 'guard_name' => 'cabinet'],
            ['name' => 'financier', 'guard_name' => 'cabinet'],
            ['name' => 'auditeur', 'guard_name' => 'cabinet'],
            ['name' => 'manager', 'guard_name' => 'cabinet'],
            ['name' => 'direction', 'guard_name' => 'cabinet'],
            ['name' => 'administrateur', 'guard_name' => 'cabinet'],
            ['name' => 'client', 'guard_name' => 'client'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role['name'],
                'guard_name' => $role['guard_name'],
            ]);
        }
    }
}
