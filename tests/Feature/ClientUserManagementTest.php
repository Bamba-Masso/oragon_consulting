<?php

namespace Tests\Feature;

use App\Mail\ClientAccessCredentials;
use App\Mail\ClientAccessUpdated;
use App\Models\ClientUser;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ClientUserManagementTest extends TestCase
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

    public function test_the_list_can_be_searched_and_filtered_by_organization(): void
    {
        $cacao = Organization::factory()->create(['name' => 'Société Ivoirienne de Cacao']);
        ClientUser::factory()->withRole()->for($cacao)->create(['first_name' => 'Aya', 'last_name' => 'Kouamé']);
        ClientUser::factory()->withRole()->create(['first_name' => 'Ibrahim', 'last_name' => 'Traoré']);

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.client-users.index', ['search' => 'cacao']))
            ->assertOk()
            ->assertSee('Aya Kouamé')
            ->assertDontSee('Ibrahim Traoré');

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.client-users.index', ['organization' => $cacao->id]))
            ->assertOk()
            ->assertSee('Aya Kouamé')
            ->assertDontSee('Ibrahim Traoré');
    }

    public function test_the_list_contains_the_creation_form_with_the_active_organizations_only(): void
    {
        Organization::factory()->create(['name' => 'Cliente Active']);
        Organization::factory()->inactive()->create(['name' => 'Cliente Désactivée']);

        $response = $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.client-users.index'))
            ->assertOk()
            ->assertSee('create-client-user-modal')
            ->assertSee(route('cabinet.client-users.store'));

        $creationForm = str($response->getContent())->after('create-client-user-modal')->before('</form>')->toString();

        foreach (['organization_id', 'first_name', 'last_name', 'email', 'phone', 'address', 'job_title', 'avatar'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $creationForm);
        }
        $this->assertStringNotContainsString('name="username"', $creationForm);
        $this->assertStringContainsString('enctype="multipart/form-data"', $creationForm);

        $this->assertStringContainsString('Cliente Active', $creationForm);
        $this->assertStringNotContainsString('Cliente Désactivée', $creationForm);
    }

    public function test_the_creation_modal_reopens_with_the_errors_after_a_failed_validation(): void
    {
        $this->actingAs($this->administrator, 'cabinet')
            ->from(route('cabinet.client-users.index'))
            ->followingRedirects()
            ->post(route('cabinet.client-users.store'), [...$this->validData(), 'first_name' => ''])
            ->assertOk()
            ->assertSee("getInstance('Modal', 'create-client-user-modal')", false)
            ->assertSee('Le champ « prénom » est obligatoire.');
    }

    public function test_the_creation_modal_stays_closed_without_errors(): void
    {
        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.client-users.index'))
            ->assertDontSee("getInstance('Modal'", false);
    }

    public function test_access_follows_the_permissions_of_the_role(): void
    {
        $this->actingAs(User::factory()->withRole('comptable')->create(), 'cabinet')
            ->get(route('cabinet.client-users.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->withRole('auditeur')->create(), 'cabinet')
            ->post(route('cabinet.client-users.store'), $this->validData())
            ->assertForbidden();
    }

    public function test_client_users_cannot_reach_the_client_user_management(): void
    {
        $this->actingAs(ClientUser::factory()->withRole()->create(), 'client')
            ->get(route('cabinet.client-users.index'))
            ->assertRedirect(route('cabinet.login'));
    }

    public function test_creating_a_client_user_sends_their_access_by_email(): void
    {
        Mail::fake();
        $secretary = User::factory()->withRole('secretaire')->create();

        $data = $this->validData();

        $this->actingAs($secretary, 'cabinet')
            ->post(route('cabinet.client-users.store'), $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.client-users.index'))
            ->assertSessionHas('status');

        $clientUser = ClientUser::where('username', 'aya.kouame')->firstOrFail();
        $this->assertSame($data['organization_id'], $clientUser->organization_id);
        $this->assertSame('Cocody, Rue des Jardins', $clientUser->address);
        $this->assertTrue($clientUser->organization->is(Organization::find($data['organization_id'])));
        $this->assertTrue($clientUser->hasRole('client'));
        $this->assertTrue($clientUser->is_active);
        $this->assertSame($secretary->id, $clientUser->created_by);

        Mail::assertSent(ClientAccessCredentials::class, function (ClientAccessCredentials $mail) use ($clientUser): bool {
            return $mail->hasTo('aya.kouame@entreprise.ci')
                && $mail->clientUser->is($clientUser)
                && Hash::check($mail->temporaryPassword, $clientUser->password);
        });
    }

    public function test_the_username_is_generated_from_the_first_and_last_names(): void
    {
        Mail::fake();
        ClientUser::factory()->create(['username' => 'aya.kouame']);
        ClientUser::factory()->create(['username' => 'aya.kouame2'])->delete();

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.client-users.store'), [...$this->validData(), 'email' => 'aya@autre.ci', 'username' => 'choisi-a-la-main'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('client_users', ['email' => 'aya@autre.ci', 'username' => 'aya.kouame3']);
        $this->assertDatabaseMissing('client_users', ['username' => 'choisi-a-la-main']);
    }

    public function test_generated_usernames_have_no_accents_nor_spaces(): void
    {
        $this->assertSame('jean.eric.nguessan', ClientUser::generateUsername('Jean-Éric', "N'Guessan"));
        $this->assertSame('marie.kone.traore', ClientUser::generateUsername('  Marie ', 'Koné Traoré'));
        $this->assertSame('utilisateur', ClientUser::generateUsername('', ''));
    }

    public function test_the_access_email_shows_the_login_and_the_temporary_password(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create(['username' => 'aya.kouame']);

        $mail = new ClientAccessCredentials($clientUser, 'MotDePasse123');

        $mail->assertHasSubject('Bienvenue sur Oragon Consulting');
        $mail->assertSeeInHtml($clientUser->email);
        $mail->assertSeeInHtml('MotDePasse123');
        $mail->assertSeeInHtml($clientUser->organization->name);
        $mail->assertSeeInHtml('Accéder à mon espace client');
        $mail->assertDontSeeInHtml('Nom d\'utilisateur', false);
        $mail->assertSeeInHtml(route('client.login'));
    }

    public function test_the_account_is_kept_when_the_email_cannot_be_sent(): void
    {
        Mail::shouldReceive('to')->andThrow(new TransportException('Serveur SMTP indisponible'));

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.client-users.store'), $this->validData())
            ->assertRedirect(route('cabinet.client-users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('client_users', ['username' => 'aya.kouame']);
    }

    public function test_the_creation_is_validated(): void
    {
        Mail::fake();
        ClientUser::factory()->create(['email' => 'aya.kouame@entreprise.ci', 'username' => 'aya.kouame']);

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.client-users.store'), [...$this->validData(), 'organization_id' => fake()->uuid(), 'first_name' => ''])
            ->assertSessionHasErrors(['organization_id', 'first_name', 'email']);

        Mail::assertNothingSent();
    }

    public function test_a_client_user_is_updated_without_changing_their_organization(): void
    {
        Mail::fake();
        $clientUser = ClientUser::factory()->withRole()->create(['email' => 'aya.kouame@entreprise.ci']);
        $organizationId = $clientUser->organization_id;
        $secretary = User::factory()->withRole('secretaire')->create();

        $this->actingAs($secretary, 'cabinet')
            ->from(route('cabinet.client-users.index'))
            ->put(route('cabinet.client-users.update', $clientUser), [
                ...$this->validData(),
                'last_name' => 'Koné',
                'organization_id' => Organization::factory()->create()->id,
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.client-users.index'));

        $clientUser->refresh();
        $this->assertSame('Koné', $clientUser->last_name);
        $this->assertSame($organizationId, $clientUser->organization_id);
        $this->assertTrue($clientUser->is_active);
        $this->assertSame($secretary->id, $clientUser->updated_by);
    }

    public function test_changing_the_name_regenerates_the_username_and_sends_the_new_access(): void
    {
        Mail::fake();
        $clientUser = ClientUser::factory()->withRole()->create([
            'first_name' => 'Aya',
            'last_name' => 'Kouamé',
            'username' => 'aya.kouame',
            'email' => 'aya.kouame@entreprise.ci',
        ]);

        $this->actingAs($this->administrator, 'cabinet')
            ->from(route('cabinet.client-users.index'))
            ->put(route('cabinet.client-users.update', $clientUser), [...$this->validData(), 'last_name' => 'Koné', 'username' => 'ignore-moi'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertSame('aya.kone', $clientUser->refresh()->username);
        Mail::assertSent(ClientAccessUpdated::class, fn (ClientAccessUpdated $mail): bool => $mail->hasTo('aya.kouame@entreprise.ci')
            && $mail->clientUser->is($clientUser));
    }

    public function test_changing_the_email_sends_the_new_access_without_changing_the_username(): void
    {
        Mail::fake();
        $clientUser = ClientUser::factory()->withRole()->create([
            'first_name' => 'Aya',
            'last_name' => 'Kouamé',
            'username' => 'aya.kouame',
            'email' => 'ancienne@entreprise.ci',
        ]);

        $this->actingAs($this->administrator, 'cabinet')
            ->put(route('cabinet.client-users.update', $clientUser), [...$this->validData(), 'email' => 'nouvelle@entreprise.ci'])
            ->assertSessionHasNoErrors();

        $this->assertSame('aya.kouame', $clientUser->refresh()->username);
        Mail::assertSent(ClientAccessUpdated::class, fn (ClientAccessUpdated $mail): bool => $mail->hasTo('nouvelle@entreprise.ci'));
    }

    public function test_no_email_is_sent_when_the_access_does_not_change(): void
    {
        Mail::fake();
        $clientUser = ClientUser::factory()->withRole()->create([
            'first_name' => 'Aya',
            'last_name' => 'Kouame',
            'username' => 'aya.kouame',
            'email' => 'aya.kouame@entreprise.ci',
        ]);

        // Accent ajouté au nom et nouveau téléphone : le nom d'utilisateur reste « aya.kouame ».
        $this->actingAs($this->administrator, 'cabinet')
            ->put(route('cabinet.client-users.update', $clientUser), [...$this->validData(), 'phone' => '+225 01 02 03 04 05'])
            ->assertSessionHasNoErrors();

        $clientUser->refresh();
        $this->assertSame('Kouamé', $clientUser->last_name);
        $this->assertSame('+225 01 02 03 04 05', $clientUser->phone);
        $this->assertSame('aya.kouame', $clientUser->username);
        Mail::assertNothingSent();
    }

    public function test_the_edit_modal_has_no_username_field(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create();

        $editForm = str($this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.client-users.show', $clientUser))
            ->assertOk()
            ->getContent())->after('id="edit-client-user-'.$clientUser->id.'"')->before('</form>')->toString();

        $this->assertStringContainsString('name="first_name"', $editForm);
        $this->assertStringNotContainsString('name="username"', $editForm);
    }

    public function test_the_access_updated_email_shows_the_new_login_without_password(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create(['username' => 'aya.kone', 'email' => 'aya.kone@entreprise.ci']);

        $mail = new ClientAccessUpdated($clientUser);

        $mail->assertHasSubject('Vos accès Oragon Consulting ont été mis à jour');
        $mail->assertSeeInHtml('aya.kone@entreprise.ci');
        $mail->assertSeeInHtml('utilisez votre mot de passe actuel');
        $mail->assertSeeInHtml('Accéder à mon espace client');
        $mail->assertDontSeeInHtml('Nom d\'utilisateur', false);
        $mail->assertSeeInHtml('data:image/png;base64', false);
    }

    public function test_the_update_rejects_an_email_used_by_another_client_user(): void
    {
        ClientUser::factory()->create(['email' => 'pris@entreprise.ci']);
        $clientUser = ClientUser::factory()->create();

        $this->actingAs($this->administrator, 'cabinet')
            ->put(route('cabinet.client-users.update', $clientUser), [...$this->validData(), 'email' => 'pris@entreprise.ci'])
            ->assertSessionHasErrorsIn('updateClientUser', 'email');
    }

    public function test_an_administrator_deactivates_and_reactivates_an_access(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create(['username' => 'aya.kouame']);

        $this->actingAs($this->administrator, 'cabinet')
            ->patch(route('cabinet.client-users.activation', $clientUser))
            ->assertSessionHas('status');
        $this->assertFalse($clientUser->refresh()->is_active);

        $this->post(route('client.login.store'), ['login' => 'aya.kouame', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->actingAs($this->administrator, 'cabinet')
            ->patch(route('cabinet.client-users.activation', $clientUser));
        $this->assertTrue($clientUser->refresh()->is_active);
    }

    public function test_the_deactivation_is_confirmed_in_a_modal_instead_of_a_browser_alert(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create(['first_name' => 'Aya', 'last_name' => 'Kouamé']);

        foreach ([route('cabinet.client-users.index'), route('cabinet.client-users.show', $clientUser)] as $page) {
            $this->actingAs($this->administrator, 'cabinet')
                ->get($page)
                ->assertOk()
                ->assertSee('data-modal-target="activation-client-user-'.$clientUser->id.'"', false)
                ->assertSee('id="activation-client-user-'.$clientUser->id.'"', false)
                ->assertSee(route('cabinet.client-users.activation', $clientUser))
                ->assertSee('Désactiver l&#039;accès ?', false)
                ->assertDontSee('return confirm(', false);
        }

        $this->actingAs(User::factory()->withRole('secretaire')->create(), 'cabinet')
            ->get(route('cabinet.client-users.index'))
            ->assertDontSee('activation-client-user-'.$clientUser->id);
    }

    public function test_only_the_roles_allowed_to_lock_can_deactivate_an_access(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create();

        $this->actingAs(User::factory()->withRole('secretaire')->create(), 'cabinet')
            ->patch(route('cabinet.client-users.activation', $clientUser))
            ->assertForbidden();

        $this->assertTrue($clientUser->refresh()->is_active);
    }

    public function test_the_client_user_page_shows_all_their_information(): void
    {
        $creator = User::factory()->withRole('secretaire')->create(['first_name' => 'Awa', 'last_name' => 'Koné']);
        $clientUser = ClientUser::factory()->withRole()->create([
            'first_name' => 'Aya',
            'last_name' => 'Kouamé',
            'username' => 'aya.kouame',
            'email' => 'aya.kouame@entreprise.ci',
            'phone' => '+225 07 11 22 33 44',
            'address' => 'Cocody, Rue des Jardins',
            'job_title' => 'Comptable',
            'created_by' => $creator->id,
        ]);

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.client-users.show', $clientUser))
            ->assertOk()
            ->assertSee('Aya Kouamé')
            ->assertSee('aya.kouame')
            ->assertSee('aya.kouame@entreprise.ci')
            ->assertSee('+225 07 11 22 33 44')
            ->assertSee('Cocody, Rue des Jardins')
            ->assertSee('Fonction')
            ->assertSee('Comptable')
            ->assertSee($clientUser->organization->name)
            ->assertSee('par Awa Koné')
            ->assertSee('edit-client-user-'.$clientUser->id);

        $this->actingAs(User::factory()->withRole('comptable')->create(), 'cabinet')
            ->get(route('cabinet.client-users.show', $clientUser))
            ->assertForbidden();
    }

    public function test_the_list_shows_the_photo_or_the_initials_of_each_client_user(): void
    {
        Storage::fake('public');
        $withPhoto = ClientUser::factory()->withRole()->create(['avatar_path' => 'client-avatars/photo-aya.jpg']);
        $withoutPhoto = ClientUser::factory()->withRole()->create(['first_name' => 'Ibrahim', 'last_name' => 'Traoré']);

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.client-users.index'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('client-avatars/photo-aya.jpg'))
            ->assertSee($withPhoto->email)
            ->assertSee('IT')
            ->assertSee($withoutPhoto->email);
    }

    public function test_the_list_offers_view_and_edit_actions_and_a_short_last_login_label(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create();

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.client-users.index'))
            ->assertOk()
            ->assertSee(route('cabinet.client-users.show', $clientUser))
            ->assertSee('data-modal-target="edit-client-user-'.$clientUser->id.'"', false)
            ->assertSee('Jamais')
            ->assertDontSee('Jamais connecté');
    }

    public function test_only_the_edited_client_user_modal_reopens_after_a_failed_update(): void
    {
        $edited = ClientUser::factory()->withRole()->create();
        $other = ClientUser::factory()->withRole()->create();

        $this->actingAs($this->administrator, 'cabinet')
            ->from(route('cabinet.client-users.index'))
            ->followingRedirects()
            ->put(route('cabinet.client-users.update', $edited), [...$this->validData(), 'username' => 'aya.kouame', 'first_name' => '', 'client_user_id' => $edited->id])
            ->assertOk()
            ->assertSee("getInstance('Modal', 'edit-client-user-{$edited->id}')", false)
            ->assertDontSee("getInstance('Modal', 'edit-client-user-{$other->id}')", false)
            ->assertDontSee("getInstance('Modal', 'create-client-user-modal')", false);
    }

    public function test_a_photo_can_be_added_at_creation(): void
    {
        Mail::fake();
        Storage::fake('public');

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.client-users.store'), [...$this->validData(), 'avatar' => UploadedFile::fake()->image('photo.jpg', 200, 200)])
            ->assertSessionHasNoErrors();

        $clientUser = ClientUser::where('email', 'aya.kouame@entreprise.ci')->firstOrFail();
        $this->assertNotNull($clientUser->avatar_path);
        Storage::disk('public')->assertExists($clientUser->avatar_path);
    }

    public function test_replacing_the_photo_deletes_the_previous_one(): void
    {
        Storage::fake('public');
        $previousPath = UploadedFile::fake()->image('ancienne.jpg')->store('client-avatars', 'public');
        $clientUser = ClientUser::factory()->withRole()->create(['avatar_path' => $previousPath]);

        $this->actingAs($this->administrator, 'cabinet')
            ->put(route('cabinet.client-users.update', $clientUser), [
                ...$this->validData(),
                'username' => $clientUser->username,
                'avatar' => UploadedFile::fake()->image('nouvelle.png'),
            ])
            ->assertSessionHasNoErrors();

        $clientUser->refresh();
        $this->assertNotSame($previousPath, $clientUser->avatar_path);
        Storage::disk('public')->assertExists($clientUser->avatar_path);
        Storage::disk('public')->assertMissing($previousPath);
    }

    public function test_the_photo_must_be_a_small_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.client-users.store'), [...$this->validData(), 'avatar' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('avatar');

        $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.client-users.store'), [...$this->validData(), 'avatar' => UploadedFile::fake()->image('enorme.jpg')->size(3000)])
            ->assertSessionHasErrors('avatar');
    }

    public function test_the_access_email_embeds_the_cabinet_logo(): void
    {
        $mail = new ClientAccessCredentials(ClientUser::factory()->withRole()->create(), 'MotDePasse123');

        $mail->assertSeeInHtml('data:image/png;base64', false);
    }

    /**
     * @return array<string, string>
     */
    private function validData(): array
    {
        return [
            'organization_id' => Organization::factory()->create()->id,
            'first_name' => 'Aya',
            'last_name' => 'Kouamé',
            'email' => 'aya.kouame@entreprise.ci',
            'phone' => '+225 07 11 22 33 44',
            'address' => 'Cocody, Rue des Jardins',
            'job_title' => 'Comptable',
        ];
    }
}
