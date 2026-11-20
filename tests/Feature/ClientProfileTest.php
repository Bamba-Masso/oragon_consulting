<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_a_client_sees_their_profile_and_their_organization(): void
    {
        $client = ClientUser::factory()->withRole()->create(['first_name' => 'Yao', 'last_name' => 'Kouassi', 'job_title' => 'Gérant']);

        $this->actingAs($client, 'client')
            ->get(route('client.profile'))
            ->assertOk()
            ->assertSee('Yao Kouassi')
            ->assertSee($client->organization->name)
            ->assertSee('Gérant')
            ->assertSee(route('client.profile.update'))
            ->assertSee(route('client.profile.password'));
    }

    public function test_the_client_profile_is_reserved_to_logged_in_clients(): void
    {
        $this->get(route('client.profile'))->assertRedirect(route('client.login'));

        $this->actingAs(User::factory()->withRole('administrateur')->create(), 'cabinet')
            ->get(route('client.profile'))
            ->assertRedirect(route('client.login'));
    }

    public function test_a_client_updates_their_details_but_not_their_identifiers_nor_their_organization(): void
    {
        $client = ClientUser::factory()->withRole()->create(['email' => 'yao@client.test', 'username' => 'ykouassi']);
        $organizationId = $client->organization_id;

        $this->actingAs($client, 'client')
            ->put(route('client.profile.update'), [
                'first_name' => 'Yao',
                'last_name' => 'Kouamé',
                'phone' => '+225 05 11 22 33 44',
                'job_title' => 'Directeur financier',
                'email' => 'pirate@client.test',
                'username' => 'pirate',
                'organization_id' => Organization::factory()->create()->id,
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('client.profile'))
            ->assertSessionHas('status');

        $client->refresh();
        $this->assertSame('Kouamé', $client->last_name);
        $this->assertSame('Directeur financier', $client->job_title);
        $this->assertSame('yao@client.test', $client->email);
        $this->assertSame('ykouassi', $client->username);
        $this->assertSame($organizationId, $client->organization_id);
        $this->assertTrue($client->is_active);
    }

    public function test_a_client_changes_their_password_with_their_current_password(): void
    {
        $client = ClientUser::factory()->withRole()->create();

        $this->actingAs($client, 'client')
            ->put(route('client.profile.password'), [
                'current_password' => 'password',
                'password' => 'nouveau-mot-de-passe',
                'password_confirmation' => 'nouveau-mot-de-passe',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('client.profile'));

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $client->refresh()->password));
    }

    public function test_the_client_password_is_not_changed_with_a_wrong_current_password(): void
    {
        $client = ClientUser::factory()->withRole()->create();

        $this->actingAs($client, 'client')
            ->put(route('client.profile.password'), [
                'current_password' => 'mauvais',
                'password' => 'nouveau-mot-de-passe',
                'password_confirmation' => 'nouveau-mot-de-passe',
            ])
            ->assertSessionHasErrorsIn('password', ['current_password']);

        $this->assertTrue(Hash::check('password', $client->refresh()->password));
    }
}
