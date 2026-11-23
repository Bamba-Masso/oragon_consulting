<?php

namespace Tests\Feature;

use App\Mail\StaffAccessCredentials;
use App\Mail\StaffAccessUpdated;
use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollaboratorManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();

        $this->administrator = User::factory()->withRole('administrateur')->create();
    }

    public function test_the_list_can_be_searched_and_filtered_by_role(): void
    {
        User::factory()->withRole('comptable')->create(['first_name' => 'Awa', 'last_name' => 'Koné']);
        User::factory()->withRole('fiscaliste')->create(['first_name' => 'Ibrahim', 'last_name' => 'Traoré']);

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.users.index', ['search' => 'koné']))
            ->assertOk()
            ->assertSee('Awa Koné')
            ->assertDontSee('Ibrahim Traoré');

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.users.index', ['role' => 'fiscaliste']))
            ->assertOk()
            ->assertSee('Ibrahim Traoré')
            ->assertSee('Fiscaliste')
            ->assertDontSee('Awa Koné');
    }

    public function test_only_the_roles_allowed_to_manage_collaborators_can_reach_the_pages(): void
    {
        $collaborator = User::factory()->withRole('comptable')->create();

        foreach (['comptable', 'secretaire', 'manager'] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create(), 'cabinet')
                ->get(route('cabinet.users.index'))
                ->assertForbidden();
        }

        $this->actingAs(User::factory()->withRole('direction')->create(), 'cabinet')
            ->get(route('cabinet.users.show', $collaborator))
            ->assertOk();
    }

    public function test_client_users_cannot_reach_the_collaborator_pages(): void
    {
        $this->actingAs(ClientUser::factory()->withRole()->create(), 'client')
            ->get(route('cabinet.users.index'))
            ->assertRedirect(route('cabinet.login'));
    }

    public function test_creating_a_collaborator_assigns_the_role_and_sends_the_access_by_email(): void
    {
        Mail::fake();

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.users.store'), $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.users.index'))
            ->assertSessionHas('status');

        $collaborator = User::where('email', 'awa.kone@cabinet.ci')->firstOrFail();
        $this->assertSame('awa.kone', $collaborator->username);
        $this->assertTrue($collaborator->hasRole('comptable'));
        $this->assertSame('Cocody, Rue des Jardins', $collaborator->address);
        $this->assertTrue($collaborator->is_active);

        Mail::assertSent(StaffAccessCredentials::class, fn (StaffAccessCredentials $mail): bool => $mail->hasTo('awa.kone@cabinet.ci')
            && $mail->user->is($collaborator)
            && Hash::check($mail->temporaryPassword, $collaborator->password));
    }

    public function test_the_access_email_points_to_the_staff_login_and_shows_the_role(): void
    {
        $collaborator = User::factory()->withRole('comptable')->create(['username' => 'awa.kone']);

        $mail = new StaffAccessCredentials($collaborator, 'MotDePasse123');

        $mail->assertSeeInHtml('Espace collaborateur');
        $mail->assertSeeInHtml('awa.kone');
        $mail->assertSeeInHtml('MotDePasse123');
        $mail->assertSeeInHtml('Comptable');
        $mail->assertSeeInHtml(route('cabinet.login'));
        $mail->assertSeeInHtml('data:image/png;base64', false);
    }

    public function test_only_an_administrator_can_give_the_administrator_role(): void
    {
        Mail::fake();
        $director = User::factory()->withRole('direction')->create();

        $this->actingAs($director, 'cabinet')
            ->post(route('cabinet.users.store'), [...$this->validData(), 'role' => 'administrateur'])
            ->assertSessionHasErrors('role');

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.users.store'), [...$this->validData(), 'role' => 'administrateur'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(User::where('email', 'awa.kone@cabinet.ci')->firstOrFail()->hasRole('administrateur'));
    }

    public function test_the_direction_cannot_modify_an_administrator_account(): void
    {
        $director = User::factory()->withRole('direction')->create();

        $this->actingAs($director, 'cabinet')
            ->put(route('cabinet.users.update', $this->administrator), $this->validData())
            ->assertForbidden();
    }

    public function test_changing_the_name_and_the_role_regenerates_the_username_and_sends_the_new_access(): void
    {
        Mail::fake();
        $collaborator = User::factory()->withRole('comptable')->create([
            'first_name' => 'Awa',
            'last_name' => 'Koné',
            'username' => 'awa.kone',
            'email' => 'awa.kone@cabinet.ci',
        ]);

        $this->actingAs($this->administrator, 'cabinet')
            ->from(route('cabinet.users.index'))
            ->put(route('cabinet.users.update', $collaborator), [...$this->validData(), 'last_name' => 'Diallo', 'role' => 'manager'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.users.index'));

        $collaborator->refresh();
        $this->assertSame('awa.diallo', $collaborator->username);
        $this->assertTrue($collaborator->hasRole('manager'));
        $this->assertFalse($collaborator->hasRole('comptable'));
        Mail::assertSent(StaffAccessUpdated::class, fn (StaffAccessUpdated $mail): bool => $mail->hasTo('awa.kone@cabinet.ci'));
    }

    public function test_no_email_is_sent_when_the_access_does_not_change(): void
    {
        Mail::fake();
        $collaborator = User::factory()->withRole('comptable')->create([
            'first_name' => 'Awa',
            'last_name' => 'Koné',
            'username' => 'awa.kone',
            'email' => 'awa.kone@cabinet.ci',
        ]);

        $this->actingAs($this->administrator, 'cabinet')
            ->put(route('cabinet.users.update', $collaborator), [...$this->validData(), 'phone' => '+225 01 02 03 04 05'])
            ->assertSessionHasNoErrors();

        $this->assertSame('+225 01 02 03 04 05', $collaborator->refresh()->phone);
        Mail::assertNothingSent();
    }

    public function test_a_collaborator_cannot_change_their_own_role(): void
    {
        $this->actingAs($this->administrator, 'cabinet')
            ->put(route('cabinet.users.update', $this->administrator), [
                ...$this->validData(),
                'email' => $this->administrator->email,
                'role' => 'comptable',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->administrator->refresh()->hasRole('administrateur'));
        $this->assertFalse($this->administrator->hasRole('comptable'));
    }

    public function test_an_administrator_deactivates_a_collaborator_who_can_no_longer_log_in(): void
    {
        $collaborator = User::factory()->withRole('comptable')->create(['username' => 'awa.kone']);

        $this->actingAs($this->administrator, 'cabinet')
            ->patch(route('cabinet.users.activation', $collaborator))
            ->assertSessionHas('status');
        $this->assertFalse($collaborator->refresh()->is_active);

        auth('cabinet')->logout();

        $this->post(route('cabinet.login.store'), ['login' => 'awa.kone', 'password' => 'password'])
            ->assertSessionHasErrors('login');
    }

    public function test_a_collaborator_cannot_deactivate_their_own_account(): void
    {
        $this->actingAs($this->administrator, 'cabinet')
            ->patch(route('cabinet.users.activation', $this->administrator))
            ->assertForbidden();

        $this->assertTrue($this->administrator->refresh()->is_active);
    }

    public function test_the_list_and_the_page_use_modals_for_edition_and_deactivation(): void
    {
        $collaborator = User::factory()->withRole('comptable')->create();

        foreach ([route('cabinet.users.index'), route('cabinet.users.show', $collaborator)] as $page) {
            $this->actingAs($this->administrator, 'cabinet')
                ->get($page)
                ->assertOk()
                ->assertSee('id="edit-user-'.$collaborator->id.'"', false)
                ->assertSee('id="activation-user-'.$collaborator->id.'"', false)
                ->assertDontSee('return confirm(', false);
        }

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.users.index'))
            ->assertSee('id="create-user-modal"', false)
            ->assertDontSee('id="activation-user-'.$this->administrator->id.'"', false);
    }

    public function test_only_the_edited_collaborator_modal_reopens_after_a_failed_update(): void
    {
        $edited = User::factory()->withRole('comptable')->create();
        $other = User::factory()->withRole('comptable')->create();

        $this->actingAs($this->administrator, 'cabinet')
            ->from(route('cabinet.users.index'))
            ->followingRedirects()
            ->put(route('cabinet.users.update', $edited), [...$this->validData(), 'first_name' => '', 'user_id' => $edited->id])
            ->assertOk()
            ->assertSee("getInstance('Modal', 'edit-user-{$edited->id}')", false)
            ->assertDontSee("getInstance('Modal', 'edit-user-{$other->id}')", false)
            ->assertDontSee("getInstance('Modal', 'create-user-modal')", false);
    }

    public function test_a_photo_can_be_added_at_creation(): void
    {
        Mail::fake();
        Storage::fake('public');

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.users.store'), [...$this->validData(), 'avatar' => UploadedFile::fake()->image('photo.jpg')])
            ->assertSessionHasNoErrors();

        $collaborator = User::where('email', 'awa.kone@cabinet.ci')->firstOrFail();
        $this->assertNotNull($collaborator->avatar_path);
        Storage::disk('public')->assertExists($collaborator->avatar_path);
    }

    /**
     * @return array<string, string>
     */
    private function validData(): array
    {
        return [
            'first_name' => 'Awa',
            'last_name' => 'Koné',
            'email' => 'awa.kone@cabinet.ci',
            'phone' => '+225 07 11 22 33 44',
            'address' => 'Cocody, Rue des Jardins',
            'role' => 'comptable',
        ];
    }
}
