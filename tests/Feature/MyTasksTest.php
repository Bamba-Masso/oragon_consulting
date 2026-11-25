<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_a_collaborator_only_sees_the_tasks_assigned_to_them_without_filters_nor_add_button(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $colleague = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->assignedTo($accountant)->assignedTo($colleague)->create();
        Task::factory()->for($folder)->assignedTo($accountant)->create(['title' => 'Saisie des achats']);
        Task::factory()->for($folder)->assignedTo($colleague)->create(['title' => 'Lettrage des clients']);

        $this->actingAs($accountant, 'cabinet')
            ->get(route('folder.tasks'))
            ->assertOk()
            ->assertSee('Saisie des achats')
            ->assertDontSee('Lettrage des clients')
            ->assertDontSee('Nouvelle tâche')
            ->assertDontSee('<select', false);
    }

    public function test_the_search_looks_into_the_task_and_its_folder(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->assignedTo($accountant)->create(['title' => 'Bilan annuel']);
        Task::factory()->for($folder)->assignedTo($accountant)->create(['title' => 'Saisie des achats']);
        Task::factory()->for(Folder::factory()->assignedTo($accountant))->assignedTo($accountant)->create(['title' => 'Déclaration de TVA']);

        $this->actingAs($accountant, 'cabinet')
            ->get(route('folder.tasks', ['search' => 'Bilan']))
            ->assertSee('Saisie des achats')
            ->assertDontSee('Déclaration de TVA');
    }

    public function test_the_list_links_to_the_task_detail_without_showing_the_checklist(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $task->checklistItems()->create(['label' => 'Relevés de banque reçus', 'order' => 1]);

        $this->actingAs($accountant, 'cabinet')
            ->get(route('folder.tasks'))
            ->assertSee(route('folder.tasks.show', $task))
            ->assertSee($task->status->name)
            ->assertDontSee('Relevés de banque reçus');
    }

    public function test_the_task_detail_shows_its_checklist_and_its_comments(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $task->checklistItems()->create(['label' => 'Relevés de banque reçus', 'order' => 1]);
        $comment = new TaskComment(['content' => 'Le client a envoyé les relevés.']);
        $comment->created_by = $accountant->id;
        $task->comments()->save($comment);

        $this->actingAs($accountant, 'cabinet')
            ->get(route('folder.tasks.show', $task))
            ->assertOk()
            ->assertSee($task->title)
            ->assertSee('Relevés de banque reçus')
            ->assertSee('Le client a envoyé les relevés.')
            ->assertSee(route('cabinet.tasks.checklist-items.store', $task));
    }

    public function test_the_detail_of_a_task_outside_the_scope_answers_not_found(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $task = Task::factory()->create();

        $this->actingAs($accountant, 'cabinet')
            ->get(route('folder.tasks.show', $task))
            ->assertNotFound();
    }

    public function test_a_collaborator_adds_a_point_to_the_checklist_of_their_task(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $task->checklistItems()->create(['label' => 'Premier point', 'order' => 1]);

        $this->actingAs($accountant, 'cabinet')
            ->post(route('cabinet.tasks.checklist-items.store', $task), ['label' => 'Relevés de banque reçus'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('folder.tasks.show', $task));

        $item = $task->checklistItems()->where('label', 'Relevés de banque reçus')->firstOrFail();
        $this->assertSame(2, $item->order);
        $this->assertFalse($item->is_completed);
    }

    public function test_an_empty_point_is_refused(): void
    {
        [$accountant, $task] = $this->assignedTask();

        $this->actingAs($accountant, 'cabinet')
            ->from(route('folder.tasks.show', $task))
            ->post(route('cabinet.tasks.checklist-items.store', $task), ['label' => ''])
            ->assertRedirect(route('folder.tasks.show', $task))
            ->assertSessionHasErrorsIn('storeChecklist', 'label');

        $this->assertDatabaseCount('task_checklists', 0);
    }

    public function test_a_point_is_marked_as_completed_then_back_to_do(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $item = $task->checklistItems()->create(['label' => 'Relevés de banque reçus', 'order' => 1]);

        $this->actingAs($accountant, 'cabinet')
            ->patch(route('cabinet.tasks.checklist-items.completion', [$task, $item]))
            ->assertRedirect(route('folder.tasks.show', $task));

        $item->refresh();
        $this->assertTrue($item->is_completed);
        $this->assertSame($accountant->id, $item->completed_by);

        $this->actingAs($accountant, 'cabinet')
            ->patch(route('cabinet.tasks.checklist-items.completion', [$task, $item]));

        $item->refresh();
        $this->assertFalse($item->is_completed);
        $this->assertNull($item->completed_by);
    }

    public function test_a_point_is_deleted_from_the_checklist(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $item = $task->checklistItems()->create(['label' => 'Relevés de banque reçus', 'order' => 1]);

        $this->actingAs($accountant, 'cabinet')
            ->delete(route('cabinet.tasks.checklist-items.destroy', [$task, $item]))
            ->assertRedirect(route('folder.tasks.show', $task))
            ->assertSessionHas('status');

        $this->assertModelMissing($item);
    }

    public function test_a_point_of_another_task_cannot_be_reached_through_this_task(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $otherTask = Task::factory()->for($task->folder)->assignedTo($accountant)->create();
        $otherItem = $otherTask->checklistItems()->create(['label' => 'Autre point', 'order' => 1]);

        $this->actingAs($accountant, 'cabinet')
            ->delete(route('cabinet.tasks.checklist-items.destroy', [$task, $otherItem]))
            ->assertNotFound();

        $this->assertModelExists($otherItem);
    }

    public function test_the_checklist_of_a_folder_outside_the_scope_answers_not_found(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $task = Task::factory()->create();

        $this->actingAs($accountant, 'cabinet')
            ->post(route('cabinet.tasks.checklist-items.store', $task), ['label' => 'Intrusion'])
            ->assertNotFound();

        $this->assertDatabaseCount('task_checklists', 0);
    }

    public function test_a_collaborator_without_the_right_to_update_tasks_only_reads_the_checklist(): void
    {
        $auditor = User::factory()->withRole('auditeur')->create();
        $folder = Folder::factory()->assignedTo($auditor)->create();
        $task = Task::factory()->for($folder)->assignedTo($auditor)->create();
        $item = $task->checklistItems()->create(['label' => 'Point à contrôler', 'order' => 1]);

        $this->actingAs($auditor, 'cabinet')
            ->get(route('folder.tasks.show', $task))
            ->assertOk()
            ->assertSee('Point à contrôler')
            ->assertDontSee('id="delete-checklist-item-'.$item->id.'"', false)
            ->assertDontSee(route('cabinet.tasks.checklist-items.store', $task));

        $this->actingAs($auditor, 'cabinet')
            ->patch(route('cabinet.tasks.checklist-items.completion', [$task, $item]))
            ->assertForbidden();

        $this->assertFalse(TaskChecklist::find($item->id)->is_completed);
    }

    /**
     * @return array{0: User, 1: Task}
     */
    private function assignedTask(): array
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->assignedTo($accountant)->create();

        return [$accountant, Task::factory()->for($folder)->assignedTo($accountant)->create()];
    }
}
