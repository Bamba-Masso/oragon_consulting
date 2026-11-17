<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_both_login_pages_are_displayed(): void
    {
        $this->get(route('client.login'))->assertOk()->assertSee('Espace client');
        $this->get(route('cabinet.login'))->assertOk()->assertSee('Espace collaborateur');
    }

    public function test_a_client_user_can_log_in_with_their_email_or_username(): void
    {
        $clientUser = ClientUser::factory()->create();

        $this->post(route('client.login.store'), ['login' => $clientUser->email, 'password' => 'password'])
            ->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($clientUser, 'client');
        $this->assertNotNull($clientUser->fresh()->last_login_at);

        $this->post(route('client.logout'))->assertRedirect('/');
        $this->assertGuest('client');

        $this->post(route('client.login.store'), ['login' => $clientUser->username, 'password' => 'password'])
            ->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($clientUser, 'client');
    }

    public function test_a_client_user_cannot_log_in_with_a_wrong_password(): void
    {
        $clientUser = ClientUser::factory()->create();

        $this->from(route('client.login'))
            ->post(route('client.login.store'), ['login' => $clientUser->email, 'password' => 'wrong-password'])
            ->assertRedirect(route('client.login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest('client');
    }

    public function test_an_inactive_client_user_or_a_client_of_an_inactive_organization_cannot_log_in(): void
    {
        $inactiveClientUser = ClientUser::factory()->inactive()->create();
        $clientOfInactiveOrganization = ClientUser::factory()
            ->for(Organization::factory()->inactive())
            ->create();

        foreach ([$inactiveClientUser, $clientOfInactiveOrganization] as $clientUser) {
            $this->post(route('client.login.store'), ['login' => $clientUser->email, 'password' => 'password'])
                ->assertSessionHasErrors('login');
        }

        $this->assertGuest('client');
    }

    public function test_a_cabinet_member_can_log_in_on_the_staff_page_only(): void
    {
        $user = User::factory()->create();

        $this->post(route('client.login.store'), ['login' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('login');
        $this->assertGuest('client');

        $this->post(route('cabinet.login.store'), ['login' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('cabinet.dashboard'));
        $this->assertAuthenticatedAs($user, 'cabinet');

        $this->post(route('cabinet.logout'))->assertRedirect('/');
        $this->assertGuest('cabinet');
    }

    public function test_an_inactive_cabinet_member_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('cabinet.login.store'), ['login' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest('cabinet');
    }

    public function test_guests_are_redirected_to_the_login_page_of_the_right_space(): void
    {
        $this->get(route('client.dashboard'))->assertRedirect(route('client.login'));
        $this->get(route('cabinet.dashboard'))->assertRedirect(route('cabinet.login'));
    }

    public function test_logged_in_users_are_redirected_from_the_login_page_to_their_dashboard(): void
    {
        $this->actingAs(ClientUser::factory()->create(), 'client')
            ->get(route('client.login'))
            ->assertRedirect(route('client.dashboard'));

        $this->actingAs(User::factory()->create(), 'cabinet')
            ->get(route('cabinet.login'))
            ->assertRedirect(route('cabinet.dashboard'));
    }

    public function test_login_attempts_are_limited(): void
    {
        $clientUser = ClientUser::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('client.login.store'), ['login' => $clientUser->email, 'password' => 'wrong-password']);
        }

        $this->post(route('client.login.store'), ['login' => $clientUser->email, 'password' => 'password'])
            ->assertTooManyRequests();
    }
}
