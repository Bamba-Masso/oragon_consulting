<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
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

    public function test_the_list_can_be_searched_and_filtered_by_status(): void
    {
        Organization::factory()->create(['name' => 'Société Ivoirienne de Cacao']);
        Organization::factory()->inactive()->create(['name' => 'Ancien Client SARL']);

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.organizations.index', ['search' => 'cacao']))
            ->assertOk()
            ->assertSee('Société Ivoirienne de Cacao')
            ->assertDontSee('Ancien Client SARL');

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.organizations.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Ancien Client SARL')
            ->assertDontSee('Société Ivoirienne de Cacao');
    }

    public function test_access_follows_the_permissions_of_the_role(): void
    {
        $organization = Organization::factory()->create();
        $secretary = User::factory()->withRole('secretaire')->create();
        $accountant = User::factory()->withRole('comptable')->create();

        $this->actingAs($secretary, 'cabinet')->get(route('cabinet.organizations.show', $organization))->assertOk();
        $this->actingAs($secretary, 'cabinet')->get(route('cabinet.organizations.index'))->assertOk()->assertDontSee('create-organization-modal');
        $this->actingAs($secretary, 'cabinet')->post(route('cabinet.organizations.store'), $this->validData())->assertForbidden();
        $this->actingAs($secretary, 'cabinet')->get(route('cabinet.organizations.edit', $organization))->assertForbidden();

        $this->actingAs($accountant, 'cabinet')->get(route('cabinet.organizations.index'))->assertForbidden();
    }

    public function test_client_users_cannot_reach_the_organization_pages(): void
    {
        $client = ClientUser::factory()->withRole()->create();

        $this->actingAs($client, 'client')
            ->get(route('cabinet.organizations.show', $client->organization))
            ->assertRedirect(route('cabinet.login'));
    }

    public function test_the_list_contains_the_creation_form_for_authorized_roles(): void
    {
        $response = $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.organizations.index'))
            ->assertOk()
            ->assertSee('create-organization-modal')
            ->assertSee(route('cabinet.organizations.store'));

        foreach (['name', 'legal_form', 'registration_number', 'tax_number', 'email', 'phone', 'address', 'city'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
    }

    public function test_the_creation_modal_reopens_with_the_errors_after_a_failed_validation(): void
    {
        $this->actingAs($this->administrator, 'cabinet')
            ->from(route('cabinet.organizations.index'))
            ->followingRedirects()
            ->post(route('cabinet.organizations.store'), [...$this->validData(), 'name' => ''])
            ->assertOk()
            ->assertSee("getInstance('Modal', 'create-organization-modal')", false)
            ->assertSee('Le champ « raison sociale » est obligatoire.');
    }

    public function test_an_administrator_creates_an_organization(): void
    {
        $response = $this->actingAs($this->administrator, 'cabinet')
            ->post(route('cabinet.organizations.store'), $this->validData());

        $organization = Organization::where('registration_number', 'CI-ABJ-2026-B-12345')->firstOrFail();

        $response->assertRedirect(route('cabinet.organizations.show', $organization))->assertSessionHas('status');
        $this->assertSame('Société Test', $organization->name);
        $this->assertTrue($organization->is_active);
        $this->assertSame($this->administrator->id, $organization->created_by);
        $this->assertSame($this->administrator->id, $organization->updated_by);
    }

    public function test_the_creation_is_validated(): void
    {
        Organization::factory()->create(['registration_number' => 'CI-ABJ-2026-B-12345']);
        $organizationsCount = Organization::count();

        $this->actingAs($this->administrator, 'cabinet')
            ->from(route('cabinet.organizations.index'))
            ->post(route('cabinet.organizations.store'), [...$this->validData(), 'name' => '', 'legal_form' => 'XYZ', 'email' => 'pas-un-email'])
            ->assertRedirect(route('cabinet.organizations.index'))
            ->assertSessionHasErrors(['name', 'legal_form', 'email', 'registration_number']);

        $this->assertSame($organizationsCount, Organization::count());
    }

    public function test_an_administrator_updates_an_organization_and_keeps_its_own_identifiers(): void
    {
        $organization = Organization::factory()->create(['registration_number' => 'CI-ABJ-2026-B-12345']);

        $this->actingAs($this->administrator, 'cabinet')
            ->put(route('cabinet.organizations.update', $organization), [...$this->validData(), 'name' => 'Nouveau Nom', 'is_active' => false])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.organizations.show', $organization));

        $organization->refresh();
        $this->assertSame('Nouveau Nom', $organization->name);
        $this->assertTrue($organization->is_active);
        $this->assertSame($this->administrator->id, $organization->updated_by);
    }

    public function test_deactivating_an_organization_blocks_its_users_until_it_is_reactivated(): void
    {
        $client = ClientUser::factory()->withRole()->create(['username' => 'client-test']);
        $organization = $client->organization;

        $this->actingAs($this->administrator, 'cabinet')
            ->patch(route('cabinet.organizations.activation', $organization))
            ->assertSessionHas('status');
        $this->assertFalse($organization->refresh()->is_active);

        $this->post(route('client.login.store'), ['login' => 'client-test', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->actingAs($this->administrator, 'cabinet')
            ->patch(route('cabinet.organizations.activation', $organization));
        $this->assertTrue($organization->refresh()->is_active);
    }

    public function test_deactivation_and_deletion_are_confirmed_in_modals_instead_of_browser_alerts(): void
    {
        $organization = Organization::factory()->create();

        $this->actingAs($this->administrator, 'cabinet')
            ->get(route('cabinet.organizations.show', $organization))
            ->assertOk()
            ->assertSee('id="activation-organization-modal"', false)
            ->assertSee('id="delete-organization-modal"', false)
            ->assertSee(route('cabinet.organizations.activation', $organization))
            ->assertSee(route('cabinet.organizations.destroy', $organization))
            ->assertDontSee('return confirm(', false);

        $this->actingAs(User::factory()->withRole('secretaire')->create(), 'cabinet')
            ->get(route('cabinet.organizations.show', $organization))
            ->assertOk()
            ->assertDontSee('activation-organization-modal')
            ->assertDontSee('delete-organization-modal');
    }

    public function test_an_organization_with_folders_cannot_be_deleted(): void
    {
        $organization = Organization::factory()->create();
        Folder::factory()->for($organization)->create()->delete();

        $this->actingAs($this->administrator, 'cabinet')
            ->delete(route('cabinet.organizations.destroy', $organization))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($organization);
    }

    public function test_an_organization_without_folders_is_soft_deleted(): void
    {
        $organization = Organization::factory()->create();

        $this->actingAs($this->administrator, 'cabinet')
            ->delete(route('cabinet.organizations.destroy', $organization))
            ->assertRedirect(route('cabinet.organizations.index'));

        $this->assertSoftDeleted($organization);
    }

    public function test_the_organization_page_only_lists_the_folders_the_collaborator_can_see(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $organization = Organization::factory()->create();
        Folder::factory()->for($organization)->managedBy($manager)->create(['title' => 'Dossier du manager']);
        Folder::factory()->for($organization)->create(['title' => 'Dossier confidentiel']);

        $this->actingAs($manager, 'cabinet')
            ->get(route('cabinet.organizations.show', $organization))
            ->assertOk()
            ->assertSee('Dossier du manager')
            ->assertDontSee('Dossier confidentiel')
            ->assertSee('1 autre(s) dossier(s) ne vous sont pas affectés.');
    }

    /**
     * @return array<string, string>
     */
    private function validData(): array
    {
        return [
            'name' => 'Société Test',
            'legal_form' => 'SARL',
            'registration_number' => 'CI-ABJ-2026-B-12345',
            'tax_number' => '2600123A',
            'email' => 'contact@societe-test.ci',
            'phone' => '+225 07 00 00 00 00',
            'address' => 'Cocody, Rue des Jardins',
            'city' => 'Abidjan',
        ];
    }
}
