<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_a_manager_adds_a_person_in_charge_of_the_folder(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->create();

        $this->actingAs($manager, 'cabinet')
            ->post(route('cabinet.folders.members.store', $folder), ['user_id' => $accountant->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.folders.show', $folder))
            ->assertSessionHas('status');

        $assignment = $folder->assignees()->whereKey($accountant->id)->first()?->pivot;
        $this->assertNotNull($assignment);
        $this->assertFalse($assignment->is_manager);
        $this->assertSame($manager->id, $assignment->assigned_by);
    }

    public function test_only_active_collaborators_outside_the_team_can_be_added(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $member = User::factory()->withRole('comptable')->create();
        $inactive = User::factory()->withRole('fiscaliste')->inactive()->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($member)->create();

        foreach ([$member, $inactive] as $candidate) {
            $this->actingAs($manager, 'cabinet')
                ->post(route('cabinet.folders.members.store', $folder), ['user_id' => $candidate->id])
                ->assertSessionHasErrorsIn('addMember', 'user_id');
        }

        $this->assertSame(2, $folder->assignees()->count());
    }

    public function test_the_add_modal_only_offers_collaborators_outside_the_team(): void
    {
        $manager = User::factory()->withRole('manager')->create(['first_name' => 'Ibrahim', 'last_name' => 'Traoré']);
        User::factory()->withRole('comptable')->create(['first_name' => 'Awa', 'last_name' => 'Koné']);
        $folder = Folder::factory()->managedBy($manager)->create();

        $addForm = str($this->actingAs($manager, 'cabinet')->get(route('cabinet.folders.show', $folder))->getContent())
            ->after('id="add-member-modal"')->before('</form>')->toString();

        $this->assertStringContainsString('Awa Koné', $addForm);
        $this->assertStringNotContainsString('Ibrahim Traoré', $addForm);
    }

    public function test_removing_a_person_unassigns_their_tasks_of_the_folder(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->create();
        $task = Task::factory()->for($folder)->assignedTo($accountant)->create();

        $this->actingAs($manager, 'cabinet')
            ->delete(route('cabinet.folders.members.destroy', [$folder, $accountant]))
            ->assertRedirect(route('cabinet.folders.show', $folder));

        $this->assertFalse($folder->assignees()->whereKey($accountant->id)->exists());
        $this->assertNull($task->refresh()->assigned_to);
        $this->assertFalse($folder->isVisibleTo($accountant));
    }

    public function test_removing_the_manager_leaves_the_folder_without_manager(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $administrator = User::factory()->withRole('administrateur')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($administrator)->create();

        $this->actingAs($administrator, 'cabinet')
            ->delete(route('cabinet.folders.members.destroy', [$folder, $manager]))
            ->assertRedirect(route('cabinet.folders.show', $folder));

        $folder->unsetRelation('managers');
        $this->assertNull($folder->manager);
    }

    public function test_a_manager_who_removes_themself_returns_to_the_list(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $folder = Folder::factory()->managedBy($manager)->create();

        $this->actingAs($manager, 'cabinet')
            ->delete(route('cabinet.folders.members.destroy', [$folder, $manager]))
            ->assertRedirect(route('cabinet.folders.index'));
    }

    public function test_removing_someone_outside_the_team_answers_not_found(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $outsider = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->create();

        $this->actingAs($manager, 'cabinet')
            ->delete(route('cabinet.folders.members.destroy', [$folder, $outsider]))
            ->assertNotFound();
    }

    public function test_collaborators_without_the_right_to_assign_cannot_change_the_team(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $taxAdvisor = User::factory()->withRole('fiscaliste')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->create();

        $this->actingAs($accountant, 'cabinet')
            ->post(route('cabinet.folders.members.store', $folder), ['user_id' => $taxAdvisor->id])
            ->assertForbidden();

        $this->actingAs($accountant, 'cabinet')
            ->delete(route('cabinet.folders.members.destroy', [$folder, $manager]))
            ->assertForbidden();

        $this->actingAs($accountant, 'cabinet')
            ->get(route('cabinet.folders.show', $folder))
            ->assertDontSee('id="add-member-modal"', false)
            ->assertDontSee('id="remove-member-'.$manager->id.'"', false);
    }
}
