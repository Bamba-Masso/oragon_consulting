<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_the_cabinet_dashboard_uses_the_cabinet_layout(): void
    {
        $user = User::factory()->withRole('administrateur')->create(['first_name' => 'Awa', 'last_name' => 'Koné']);

        $this->actingAs($user, 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Espace collaborateur')
            ->assertSee('Awa Koné')
            ->assertSee('Administrateur')
            ->assertSee(route('cabinet.logout'));
    }

    public function test_the_cabinet_menu_follows_the_permissions_of_the_role(): void
    {
        $administrator = User::factory()->withRole('administrateur')->create();
        $secretary = User::factory()->withRole('secretaire')->create();

        $this->actingAs($administrator, 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertSee('Collaborateurs')
            ->assertSee('Rôles et permissions');

        $this->actingAs($secretary, 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertSee('Utilisateurs clients')
            ->assertDontSee('Collaborateurs')
            ->assertDontSee('Rôles et permissions');
    }

    public function test_the_client_dashboard_uses_the_client_layout(): void
    {
        $client = ClientUser::factory()->withRole()->create(['first_name' => 'Yao', 'last_name' => 'Kouassi']);

        $this->actingAs($client, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Espace client')
            ->assertSee('Yao Kouassi')
            ->assertSee($client->organization->name)
            ->assertSee('Mes dossiers')
            ->assertDontSee('Collaborateurs')
            ->assertSee(route('client.logout'));
    }
}
