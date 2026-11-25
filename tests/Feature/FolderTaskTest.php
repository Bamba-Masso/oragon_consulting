<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Priority;
use App\Models\Status;
use App\Models\StatusType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_a_manager_adds_a_pending_task_to_their_folder(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create(['first_name' => 'Awa', 'last_name' => 'Koné']);
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->create();

        $this->actingAs($manager, 'cabinet')
            ->post(route('cabinet.folders.tasks.store', $folder), $this->validData(['assigned_to' => $accountant->id]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.folders.show', $folder))
            ->assertSessionHas('status');

        $task = Task::where('title', 'Rapprochement bancaire')->firstOrFail();
        $this->assertTrue($task->folder->is($folder));
        $this->assertSame(Status::ofType(StatusType::TASK)->where('code', 'pending')->value('statuses.id'), $task->status_id);
        $this->assertSame('En attente', $task->status->name);
        $this->assertSame($accountant->id, $task->assigned_to);
        $this->assertSame($manager->id, $task->created_by);

        $this->actingAs($manager, 'cabinet')
            ->get(route('cabinet.folders.show', $folder))
            ->assertSee('Rapprochement bancaire')
            ->assertSee('Awa Koné · Comptabilité')
            ->assertSee('En attente');
    }

    public function test_a_task_can_only_be_assigned_to_a_member_of_the_folder_team(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $outsider = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->create();

        $this->actingAs($manager, 'cabinet')
            ->post(route('cabinet.folders.tasks.store', $folder), $this->validData(['assigned_to' => $outsider->id]))
            ->assertSessionHasErrorsIn('storeTask', 'assigned_to');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_the_task_form_reopens_with_its_errors_without_touching_the_folder_form(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $folder = Folder::factory()->managedBy($manager)->create(['title' => 'Bilan annuel']);

        $this->actingAs($manager, 'cabinet')
            ->from(route('cabinet.folders.show', $folder))
            ->followingRedirects()
            ->post(route('cabinet.folders.tasks.store', $folder), $this->validData(['title' => '']))
            ->assertOk()
            ->assertSee("getInstance('Modal', 'create-task-modal')", false)
            ->assertDontSee("getInstance('Modal', 'edit-folder-modal')", false)
            ->assertSee('value="Bilan annuel"', false);
    }

    public function test_only_collaborators_allowed_to_create_tasks_see_the_button_and_can_add_one(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->create();

        $this->actingAs($manager, 'cabinet')
            ->get(route('cabinet.folders.show', $folder))
            ->assertSee('id="create-task-modal"', false);

        $this->actingAs($accountant, 'cabinet')
            ->get(route('cabinet.folders.show', $folder))
            ->assertDontSee('id="create-task-modal"', false);

        $this->actingAs($accountant, 'cabinet')
            ->post(route('cabinet.folders.tasks.store', $folder), $this->validData())
            ->assertForbidden();
    }

    public function test_a_manager_updates_a_task_and_its_assignee(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->create();
        $task = Task::factory()->for($folder)->create(['title' => 'Ancien intitulé']);

        $this->actingAs($manager, 'cabinet')
            ->put(route('cabinet.folders.tasks.update', [$folder, $task]), $this->validData([
                'form' => 'task-edit',
                'task_id' => $task->id,
                'title' => 'Nouvel intitulé',
                'assigned_to' => $accountant->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.folders.show', $folder));

        $task->refresh();
        $this->assertSame('Nouvel intitulé', $task->title);
        $this->assertSame($accountant->id, $task->assigned_to);
        $this->assertSame($manager->id, $task->updated_by);
    }

    public function test_a_collaborator_without_the_right_to_assign_cannot_change_the_assignee(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->create();
        $task = Task::factory()->for($folder)->assignedTo($accountant)->create();

        $this->actingAs($accountant, 'cabinet')
            ->put(route('cabinet.folders.tasks.update', [$folder, $task]), $this->validData([
                'title' => 'Intitulé corrigé',
                'assigned_to' => $manager->id,
            ]))
            ->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame('Intitulé corrigé', $task->title);
        $this->assertSame($accountant->id, $task->assigned_to);
    }

    public function test_a_task_of_another_folder_cannot_be_reached_through_this_folder(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $folder = Folder::factory()->managedBy($manager)->create();
        $otherTask = Task::factory()->for(Folder::factory()->managedBy($manager))->create();

        $this->actingAs($manager, 'cabinet')
            ->put(route('cabinet.folders.tasks.update', [$folder, $otherTask]), $this->validData())
            ->assertNotFound();
    }

    public function test_only_collaborators_allowed_to_delete_tasks_can_delete_one(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $administrator = User::factory()->withRole('administrateur')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($administrator)->create();
        $task = Task::factory()->for($folder)->create();

        $this->actingAs($manager, 'cabinet')
            ->delete(route('cabinet.folders.tasks.destroy', [$folder, $task]))
            ->assertForbidden();

        $this->actingAs($administrator, 'cabinet')
            ->delete(route('cabinet.folders.tasks.destroy', [$folder, $task]))
            ->assertRedirect(route('cabinet.folders.show', $folder));

        $this->assertSoftDeleted($task);
    }

    public function test_only_the_edited_task_modal_reopens_after_a_failed_update(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $folder = Folder::factory()->managedBy($manager)->create();
        $edited = Task::factory()->for($folder)->create();
        $other = Task::factory()->for($folder)->create();

        $this->actingAs($manager, 'cabinet')
            ->from(route('cabinet.folders.show', $folder))
            ->followingRedirects()
            ->put(route('cabinet.folders.tasks.update', [$folder, $edited]), $this->validData([
                'form' => 'task-edit',
                'task_id' => $edited->id,
                'title' => '',
            ]))
            ->assertOk()
            ->assertSee("getInstance('Modal', 'edit-task-{$edited->id}')", false)
            ->assertDontSee("getInstance('Modal', 'edit-task-{$other->id}')", false)
            ->assertDontSee("getInstance('Modal', 'create-task-modal')", false);
    }

    public function test_a_folder_outside_the_scope_answers_not_found(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $folder = Folder::factory()->create();

        $this->actingAs($manager, 'cabinet')
            ->post(route('cabinet.folders.tasks.store', $folder), $this->validData())
            ->assertNotFound();
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array<string, string|null>
     */
    private function validData(array $overrides = []): array
    {
        return [
            'form' => 'task',
            'title' => 'Rapprochement bancaire',
            'description' => 'Rapprocher les relevés du trimestre.',
            'assigned_to' => null,
            'priority_id' => Priority::where('name', 'normal')->value('id'),
            'due_date' => now()->addWeek()->format('Y-m-d'),
            ...$overrides,
        ];
    }
}
