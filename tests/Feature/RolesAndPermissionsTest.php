<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_every_seeded_role_has_an_entry_in_the_permission_matrix(): void
    {
        $this->assertEqualsCanonicalizing(
            array_keys(RolePermissionSeeder::cabinetMatrix()),
            Role::where('guard_name', User::GUARD)->pluck('name')->all(),
        );

        $this->assertEqualsCanonicalizing(
            array_keys(RolePermissionSeeder::clientMatrix()),
            Role::where('guard_name', ClientUser::GUARD)->pluck('name')->all(),
        );
    }

    public function test_every_role_has_permissions_of_its_own_guard_only(): void
    {
        Role::with('permissions')->get()->each(function (Role $role): void {
            $this->assertNotEmpty($role->permissions, "Le rôle [{$role->name}] n'a aucune permission.");

            $role->permissions->each(fn (Permission $permission) => $this->assertSame(
                $role->guard_name,
                $permission->guard_name,
                "La permission [{$permission->name}] du rôle [{$role->name}] n'est pas sur le bon guard.",
            ));
        });
    }

    public function test_seeding_twice_does_not_duplicate_roles_or_permissions(): void
    {
        $roleCount = Role::count();
        $permissionCount = Permission::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($roleCount, Role::count());
        $this->assertSame($permissionCount, Permission::count());
    }

    public function test_only_the_administrator_and_the_direction_can_create_and_update_cabinet_users(): void
    {
        Role::where('guard_name', User::GUARD)->get()->each(function (Role $role): void {
            $canManageUsers = in_array($role->name, ['administrateur', 'direction'], true);

            $this->assertSame($canManageUsers, $role->hasPermissionTo('create-users'), $role->name);
            $this->assertSame($canManageUsers, $role->hasPermissionTo('update-users'), $role->name);
        });
    }

    public function test_only_the_administrator_can_manage_roles(): void
    {
        Role::where('guard_name', User::GUARD)->get()->each(function (Role $role): void {
            $this->assertSame($role->name === 'administrateur', $role->hasPermissionTo('update-roles'), $role->name);
        });
    }
}
