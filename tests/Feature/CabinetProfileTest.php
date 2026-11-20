<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CabinetProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_a_collaborator_sees_their_profile(): void
    {
        $user = User::factory()->withRole('comptable')->create(['first_name' => 'Awa', 'last_name' => 'Koné', 'username' => 'akone']);

        $this->actingAs($user, 'cabinet')
            ->get(route('cabinet.profile'))
            ->assertOk()
            ->assertSee('Awa Koné')
            ->assertSee('akone')
            ->assertSee('Comptable')
            ->assertSee(route('cabinet.profile.update'))
            ->assertSee(route('cabinet.profile.password'));
    }

    public function test_the_profile_is_reserved_to_logged_in_collaborators(): void
    {
        $this->get(route('cabinet.profile'))->assertRedirect(route('cabinet.login'));

        $this->actingAs(ClientUser::factory()->withRole()->create(), 'client')
            ->get(route('cabinet.profile'))
            ->assertRedirect(route('cabinet.login'));
    }

    public function test_a_collaborator_updates_their_details_but_not_their_identifiers(): void
    {
        $user = User::factory()->withRole('comptable')->create(['email' => 'awa@cabinet.test', 'username' => 'akone']);

        $this->actingAs($user, 'cabinet')
            ->put(route('cabinet.profile.update'), [
                'first_name' => 'Awa',
                'last_name' => 'Traoré',
                'phone' => '+225 07 12 34 56 78',
                'email' => 'pirate@cabinet.test',
                'username' => 'pirate',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.profile'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('Traoré', $user->last_name);
        $this->assertSame('+225 07 12 34 56 78', $user->phone);
        $this->assertSame('awa@cabinet.test', $user->email);
        $this->assertSame('akone', $user->username);
    }

    public function test_the_first_and_last_names_are_required(): void
    {
        $user = User::factory()->withRole('comptable')->create();

        $this->actingAs($user, 'cabinet')
            ->from(route('cabinet.profile'))
            ->put(route('cabinet.profile.update'), ['first_name' => '', 'last_name' => ''])
            ->assertRedirect(route('cabinet.profile'))
            ->assertSessionHasErrorsIn('profile', ['first_name', 'last_name']);
    }

    public function test_a_collaborator_changes_their_password_with_their_current_password(): void
    {
        $user = User::factory()->withRole('comptable')->create();

        $this->actingAs($user, 'cabinet')
            ->put(route('cabinet.profile.password'), [
                'current_password' => 'password',
                'password' => 'nouveau-mot-de-passe',
                'password_confirmation' => 'nouveau-mot-de-passe',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.profile'));

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $user->refresh()->password));
    }

    public function test_the_password_is_not_changed_with_a_wrong_current_password_or_a_mismatched_confirmation(): void
    {
        $user = User::factory()->withRole('comptable')->create();

        $this->actingAs($user, 'cabinet')
            ->put(route('cabinet.profile.password'), [
                'current_password' => 'mauvais',
                'password' => 'nouveau-mot-de-passe',
                'password_confirmation' => 'autre-chose',
            ])
            ->assertSessionHasErrorsIn('password', ['current_password', 'password']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}
