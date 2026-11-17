<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_the_seeded_administrator_has_the_administrator_role_on_the_cabinet_guard(): void
    {
        $administrator = User::where('email', UserSeeder::ADMIN_EMAIL)->firstOrFail();

        $this->assertTrue($administrator->hasRole('administrateur'));
        $this->assertTrue($administrator->hasPermissionTo('create-organizations'));
        $this->assertTrue($administrator->hasPermissionTo('create-client-users'));
        $this->assertTrue($administrator->is_active);
    }

    public function test_the_seeded_client_user_belongs_to_the_demo_organization(): void
    {
        $administrator = User::where('email', UserSeeder::ADMIN_EMAIL)->firstOrFail();
        $clientUser = ClientUser::with('organization')
            ->where('email', OrganizationSeeder::DEMO_CLIENT_EMAIL)
            ->firstOrFail();

        $this->assertSame(OrganizationSeeder::DEMO_REGISTRATION_NUMBER, $clientUser->organization->registration_number);
        $this->assertSame($administrator->id, $clientUser->created_by);
        $this->assertTrue($clientUser->hasRole('client'));
        $this->assertTrue($clientUser->hasPermissionTo('upload-documents'));
    }

    public function test_a_client_user_never_receives_cabinet_permissions(): void
    {
        $clientUser = ClientUser::where('email', OrganizationSeeder::DEMO_CLIENT_EMAIL)->firstOrFail();

        $this->assertFalse($clientUser->checkPermissionTo('view-users'));
        $this->assertFalse($clientUser->checkPermissionTo('publish-documents'));
        $this->assertFalse($clientUser->hasRole('administrateur'));
    }

    public function test_each_guard_only_authenticates_its_own_users(): void
    {
        $this->assertTrue(Auth::guard('cabinet')->attempt(['email' => UserSeeder::ADMIN_EMAIL, 'password' => UserSeeder::ADMIN_PASSWORD]));
        $this->assertFalse(Auth::guard('client')->attempt(['email' => UserSeeder::ADMIN_EMAIL, 'password' => UserSeeder::ADMIN_PASSWORD]));

        $this->assertTrue(Auth::guard('client')->attempt(['email' => OrganizationSeeder::DEMO_CLIENT_EMAIL, 'password' => OrganizationSeeder::DEMO_CLIENT_PASSWORD]));
        $this->assertFalse(Auth::guard('cabinet')->attempt(['email' => OrganizationSeeder::DEMO_CLIENT_EMAIL, 'password' => OrganizationSeeder::DEMO_CLIENT_PASSWORD]));
    }

    public function test_role_assignments_store_the_morph_alias_instead_of_the_class_name(): void
    {
        $this->assertDatabaseHas('model_has_roles', ['model_type' => 'user']);
        $this->assertDatabaseHas('model_has_roles', ['model_type' => 'client_user']);
        $this->assertDatabaseMissing('model_has_roles', ['model_type' => ClientUser::class]);
    }

    public function test_factories_create_client_users_attached_to_an_organization_with_the_client_role(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create();

        $this->assertNotNull($clientUser->organization_id);
        $this->assertTrue($clientUser->hasRole('client'));
        $this->assertSame(ClientUser::GUARD, $clientUser->roles->first()->guard_name);
    }
}
