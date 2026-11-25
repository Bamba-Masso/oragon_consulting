<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Priority;
use App\Models\Status;
use App\Models\StatusType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_the_list_only_shows_the_folders_of_the_collaborator_scope(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        Folder::factory()->assignedTo($accountant)->create(['title' => 'Bilan affecté']);
        Folder::factory()->create(['title' => 'Bilan confidentiel']);

        $this->actingAs($accountant, 'cabinet')
            ->get(route('cabinet.folders.index'))
            ->assertOk()
            ->assertSee('Bilan affecté')
            ->assertDontSee('Bilan confidentiel');
    }

    public function test_the_list_can_be_searched_and_filtered_by_status(): void
    {
        $director = User::factory()->withRole('direction')->create();
        Folder::factory()->create(['title' => 'Déclaration TVA']);
        Folder::factory()->create(['title' => 'Audit annuel', 'status_id' => $this->statusId('in_progress')]);

        $this->actingAs($director, 'cabinet')
            ->get(route('cabinet.folders.index', ['search' => 'tva']))
            ->assertOk()
            ->assertSee('Déclaration TVA')
            ->assertDontSee('Audit annuel');

        $this->actingAs($director, 'cabinet')
            ->get(route('cabinet.folders.index', ['status' => $this->statusId('in_progress')]))
            ->assertOk()
            ->assertSee('Audit annuel')
            ->assertDontSee('Déclaration TVA');
    }

    public function test_client_users_cannot_reach_the_cabinet_folder_pages(): void
    {
        $this->actingAs(ClientUser::factory()->withRole()->create(), 'client')
            ->get(route('cabinet.folders.index'))
            ->assertRedirect(route('cabinet.login'));
    }

    public function test_opening_a_folder_sets_the_reference_the_initial_status_and_assigns_the_creator(): void
    {
        $secretary = User::factory()->withRole('secretaire')->create();

        $response = $this->actingAs($secretary, 'cabinet')
            ->post(route('cabinet.folders.store'), $this->validData());

        $folder = Folder::where('title', 'Bilan annuel 2026')->firstOrFail();

        $response->assertSessionHasNoErrors()->assertRedirect(route('cabinet.folders.show', $folder));
        $this->assertSame('DOS-'.now()->year.'-0001', $folder->reference);
        $this->assertSame($this->statusId(Folder::INITIAL_STATUS), $folder->status_id);
        $this->assertSame($secretary->id, $folder->created_by);
        $this->assertTrue($folder->assignees()->whereKey($secretary->id)->exists());
        $this->assertNull($folder->manager);
        $this->assertTrue($folder->isVisibleTo($secretary));

        $this->assertDatabaseHas('folder_status_changes', [
            'folder_id' => $folder->id,
            'from_status_id' => null,
            'to_status_id' => $this->statusId(Folder::INITIAL_STATUS),
            'changed_by' => $secretary->id,
        ]);
    }

    public function test_references_follow_each_other_without_reusing_deleted_ones(): void
    {
        Folder::factory()->create(['reference' => 'DOS-'.now()->year.'-0007'])->delete();
        $secretary = User::factory()->withRole('secretaire')->create();

        $this->actingAs($secretary, 'cabinet')
            ->post(route('cabinet.folders.store'), $this->validData())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('folders', ['title' => 'Bilan annuel 2026', 'reference' => 'DOS-'.now()->year.'-0008']);
    }

    public function test_the_opening_is_validated(): void
    {
        $secretary = User::factory()->withRole('secretaire')->create();
        $inactiveCollaborator = User::factory()->withRole('comptable')->inactive()->create();

        $this->actingAs($secretary, 'cabinet')
            ->post(route('cabinet.folders.store'), [
                ...$this->validData(),
                'organization_id' => Organization::factory()->inactive()->create()->id,
                'title' => '',
                'manager_id' => $inactiveCollaborator->id,
            ])
            ->assertSessionHasErrors(['organization_id', 'title', 'manager_id']);

        $this->assertDatabaseMissing('folders', ['mission_type' => 'Tenue comptable']);
    }

    public function test_any_active_member_of_the_cabinet_can_be_the_folder_manager(): void
    {
        $secretary = User::factory()->withRole('secretaire')->create();
        $accountant = User::factory()->withRole('comptable')->create(['first_name' => 'Awa', 'last_name' => 'Koné']);
        User::factory()->withRole('fiscaliste')->inactive()->create(['first_name' => 'Ancien', 'last_name' => 'Collaborateur']);

        $this->actingAs($secretary, 'cabinet')
            ->get(route('cabinet.folders.index'))
            ->assertOk()
            ->assertSee('Awa Koné — Comptable')
            ->assertDontSee('Ancien Collaborateur');

        $this->actingAs($secretary, 'cabinet')
            ->post(route('cabinet.folders.store'), [...$this->validData(), 'manager_id' => $accountant->id])
            ->assertSessionHasNoErrors();

        $folder = Folder::where('title', 'Bilan annuel 2026')->firstOrFail();
        $this->assertTrue($folder->manager->is($accountant));
        $this->assertTrue($folder->assignees()->whereKey($secretary->id)->exists());
    }

    public function test_changing_the_manager_flags_the_new_one_in_the_team(): void
    {
        $previousManager = User::factory()->withRole('manager')->create();
        $newManager = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($previousManager)->create();

        $this->actingAs($previousManager, 'cabinet')
            ->put(route('cabinet.folders.update', $folder), $this->updateData($folder, ['manager_id' => $newManager->id]))
            ->assertSessionHasNoErrors();

        $folder->refresh();
        $this->assertTrue($folder->manager->is($newManager));
        $this->assertTrue($folder->assignees()->whereKey($previousManager->id)->exists());
        $this->assertSame(1, $folder->managers()->count());
    }

    public function test_the_creator_composes_the_team_and_designates_the_manager(): void
    {
        $secretary = User::factory()->withRole('secretaire')->create();
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $taxAdvisor = User::factory()->withRole('fiscaliste')->create();

        $this->actingAs($secretary, 'cabinet')
            ->post(route('cabinet.folders.store'), [
                ...$this->validData(),
                'manager_id' => $manager->id,
                'assignee_ids' => [$accountant->id, $taxAdvisor->id],
            ])
            ->assertSessionHasNoErrors();

        $folder = Folder::where('title', 'Bilan annuel 2026')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$secretary->id, $manager->id, $accountant->id, $taxAdvisor->id],
            $folder->assignees()->pluck('users.id')->all(),
        );
        $this->assertTrue($folder->manager->is($manager));
        $this->assertSame(1, $folder->managers()->count());
        $this->assertSame($secretary->id, $folder->assignees()->whereKey($accountant->id)->first()->pivot->assigned_by);
    }

    public function test_a_collaborator_allowed_to_assign_changes_the_team_and_the_manager(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $taxAdvisor = User::factory()->withRole('fiscaliste')->create();
        $auditor = User::factory()->withRole('auditeur')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->assignedTo($taxAdvisor)->create();

        $this->actingAs($manager, 'cabinet')
            ->from(route('cabinet.folders.show', $folder))
            ->put(route('cabinet.folders.update', $folder), $this->updateData($folder, [
                'manager_id' => $accountant->id,
                'assignee_ids' => [$manager->id, $auditor->id],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.folders.show', $folder));

        $folder->refresh();
        $this->assertEqualsCanonicalizing(
            [$manager->id, $accountant->id, $auditor->id],
            $folder->assignees()->pluck('users.id')->all(),
        );
        $this->assertTrue($folder->manager->is($accountant));
        $this->assertFalse($folder->isVisibleTo($taxAdvisor));
    }

    public function test_the_team_is_ignored_for_a_collaborator_without_the_right_to_assign(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->create();

        $this->actingAs($accountant, 'cabinet')
            ->put(route('cabinet.folders.update', $folder), $this->updateData($folder, [
                'title' => 'Intitulé corrigé',
                'manager_id' => $accountant->id,
                'assignee_ids' => [$accountant->id],
            ]))
            ->assertSessionHasNoErrors();

        $folder->refresh();
        $this->assertSame('Intitulé corrigé', $folder->title);
        $this->assertTrue($folder->manager->is($manager));
        $this->assertEqualsCanonicalizing([$manager->id, $accountant->id], $folder->assignees()->pluck('users.id')->all());
    }

    public function test_the_edit_modal_offers_the_team_only_to_collaborators_allowed_to_assign(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->create();

        $managerForm = str($this->actingAs($manager, 'cabinet')->get(route('cabinet.folders.show', $folder))->getContent())
            ->after('id="edit-folder-modal"')->before('</form>')->toString();
        $this->assertStringContainsString('name="assignee_ids[]"', $managerForm);
        $this->assertStringContainsString('name="manager_id"', $managerForm);
        $this->assertStringContainsString('Entreprise', $managerForm);

        $accountantForm = str($this->actingAs($accountant, 'cabinet')->get(route('cabinet.folders.show', $folder))->getContent())
            ->after('id="edit-folder-modal"')->before('</form>')->toString();
        $this->assertStringNotContainsString('name="assignee_ids[]"', $accountantForm);
        $this->assertStringContainsString('Seul un responsable de dossier peut modifier', $accountantForm);
    }

    public function test_a_collaborator_who_removes_themself_from_the_team_returns_to_the_list(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->create();

        $this->actingAs($manager, 'cabinet')
            ->from(route('cabinet.folders.show', $folder))
            ->put(route('cabinet.folders.update', $folder), $this->updateData($folder, [
                'manager_id' => $accountant->id,
                'assignee_ids' => [],
            ]))
            ->assertRedirect(route('cabinet.folders.index'));

        $this->assertFalse($folder->refresh()->isVisibleTo($manager));
    }

    public function test_the_auditor_cannot_open_a_folder(): void
    {
        $this->actingAs(User::factory()->withRole('auditeur')->create(), 'cabinet')
            ->post(route('cabinet.folders.store'), $this->validData())
            ->assertForbidden();
    }

    public function test_updating_a_folder_never_changes_its_status(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->assignedTo($accountant)->create();

        $this->actingAs($accountant, 'cabinet')
            ->from(route('cabinet.folders.show', $folder))
            ->put(route('cabinet.folders.update', $folder), [
                ...$this->updateData($folder),
                'title' => 'Nouvel intitulé',
                'status_id' => $this->statusId(Folder::CLOSED),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.folders.show', $folder));

        $folder->refresh();
        $this->assertSame('Nouvel intitulé', $folder->title);
        $this->assertSame($this->statusId(Folder::INITIAL_STATUS), $folder->status_id);
        $this->assertDatabaseCount('folder_status_changes', 0);
    }

    public function test_the_edit_modal_has_no_status_field(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $folder = Folder::factory()->managedBy($manager)->create();

        $editForm = str($this->actingAs($manager, 'cabinet')
            ->get(route('cabinet.folders.show', $folder))
            ->assertOk()
            ->getContent())->after('id="edit-folder-modal"')->before('</form>')->toString();

        $this->assertStringContainsString('name="title"', $editForm);
        $this->assertStringNotContainsString('name="status_id"', $editForm);
    }

    public function test_the_folder_page_shows_the_tasks_the_team_and_the_history(): void
    {
        $manager = User::factory()->withRole('manager')->create(['first_name' => 'Awa', 'last_name' => 'Koné']);
        $folder = Folder::factory()->managedBy($manager)->create(['title' => 'Bilan annuel 2026']);
        Task::factory()->for($folder)->create(['title' => 'Rapprochement bancaire']);
        $folder->statusChanges()->create(['to_status_id' => $folder->status_id, 'changed_by' => $manager->id, 'comment' => 'Ouverture du dossier']);

        $this->actingAs($manager, 'cabinet')
            ->get(route('cabinet.folders.show', $folder))
            ->assertOk()
            ->assertSee('Bilan annuel 2026')
            ->assertSee($folder->reference)
            ->assertSee('Rapprochement bancaire')
            ->assertSee('Awa Koné')
            ->assertSee('Ouverture du dossier')
            ->assertSee('id="edit-folder-modal"', false);
    }

    public function test_a_folder_outside_the_scope_answers_not_found(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->create();

        $this->actingAs($accountant, 'cabinet')->get(route('cabinet.folders.show', $folder))->assertNotFound();
        $this->actingAs($accountant, 'cabinet')->put(route('cabinet.folders.update', $folder), $this->updateData($folder))->assertNotFound();
    }

    public function test_only_authorized_collaborators_can_delete_a_folder(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $administrator = User::factory()->withRole('administrateur')->create();
        $folder = Folder::factory()->assignedTo($accountant)->assignedTo($administrator)->create();

        $this->actingAs($accountant, 'cabinet')
            ->delete(route('cabinet.folders.destroy', $folder))
            ->assertForbidden();

        $this->actingAs($administrator, 'cabinet')
            ->delete(route('cabinet.folders.destroy', $folder))
            ->assertRedirect(route('cabinet.folders.index'));

        $this->assertSoftDeleted($folder);
    }

    private function statusId(string $code): string
    {
        return Status::ofType(StatusType::FOLDER)->where('code', $code)->value('statuses.id');
    }

    /**
     * @return array<string, string|null>
     */
    private function validData(): array
    {
        return [
            'organization_id' => Organization::factory()->create()->id,
            'title' => 'Bilan annuel 2026',
            'mission_type' => 'Tenue comptable',
            'period' => '2026',
            'priority_id' => Priority::where('name', 'normal')->value('id'),
            'manager_id' => null,
            'due_date' => now()->addMonth()->format('Y-m-d'),
        ];
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array<string, string|null>
     */
    private function updateData(Folder $folder, array $overrides = []): array
    {
        return [
            'title' => $folder->title,
            'mission_type' => $folder->mission_type,
            'period' => $folder->period,
            'priority_id' => $folder->priority_id,
            'manager_id' => $folder->manager?->id,
            'assignee_ids' => $folder->assignees()->pluck('users.id')->all(),
            'due_date' => $folder->due_date?->format('Y-m-d'),
            ...$overrides,
        ];
    }
}
