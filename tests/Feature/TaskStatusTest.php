<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;

    private Folder $folder;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();

        $this->accountant = User::factory()->withRole('comptable')->create();
        $this->folder = Folder::factory()->assignedTo($this->accountant)->create();
        $this->task = Task::factory()->for($this->folder)->assignedTo($this->accountant)->create();
    }

    public function test_the_person_in_charge_starts_then_completes_the_task_from_the_list(): void
    {
        $this->actingAs($this->accountant, 'cabinet')
            ->get(route('folder.tasks'))
            ->assertSee('action="'.route('cabinet.tasks.start', $this->task).'"', false)
            ->assertDontSee('action="'.route('cabinet.tasks.complete', $this->task).'"', false);

        $this->actingAs($this->accountant, 'cabinet')
            ->from(route('folder.tasks'))
            ->patch(route('cabinet.tasks.start', $this->task))
            ->assertRedirect(route('folder.tasks'));
        $this->assertSame(Task::statusId(Task::IN_PROGRESS), $this->task->refresh()->status_id);

        $this->actingAs($this->accountant, 'cabinet')
            ->patch(route('cabinet.tasks.complete', $this->task))
            ->assertRedirect();

        $this->task->refresh();
        $this->assertSame(Task::statusId(Task::COMPLETED), $this->task->status_id);
        $this->assertNotNull($this->task->completed_at);
    }

    public function test_a_completed_or_cancelled_task_cannot_be_reopened(): void
    {
        $this->task->update(['status_id' => Task::statusId(Task::COMPLETED)]);

        foreach (['cabinet.tasks.start', 'cabinet.tasks.complete', 'cabinet.tasks.cancel'] as $route) {
            $this->actingAs($this->accountant, 'cabinet')->patch(route($route, $this->task))->assertForbidden();
        }

        $this->task->update(['status_id' => Task::statusId(Task::CANCELLED)]);

        foreach (['cabinet.tasks.start', 'cabinet.tasks.complete'] as $route) {
            $this->actingAs($this->accountant, 'cabinet')->patch(route($route, $this->task))->assertForbidden();
        }
    }

    public function test_only_the_person_in_charge_and_the_administrator_change_the_status(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $administrator = User::factory()->withRole('administrateur')->create();
        $this->folder->assignees()->attach([$manager->id, $administrator->id]);

        $this->actingAs($manager, 'cabinet')
            ->patch(route('cabinet.tasks.start', $this->task))
            ->assertForbidden();

        $this->actingAs($administrator, 'cabinet')
            ->patch(route('cabinet.tasks.cancel', $this->task))
            ->assertRedirect();

        $this->assertSame(Task::statusId(Task::CANCELLED), $this->task->refresh()->status_id);
    }

    public function test_the_status_of_a_task_in_a_closed_folder_cannot_change(): void
    {
        $this->folder->forceFill(['status_id' => Folder::statusId(Folder::CLOSED), 'closed_at' => now()])->save();

        $this->actingAs($this->accountant, 'cabinet')
            ->patch(route('cabinet.tasks.start', $this->task))
            ->assertForbidden();
    }

    public function test_the_folder_progress_counts_completed_tasks_without_the_cancelled_ones(): void
    {
        $manager = User::factory()->withRole('manager')->create();
        $this->folder->assignees()->attach($manager->id, ['is_manager' => true]);
        Task::factory()->for($this->folder)->create(['status_id' => Task::statusId(Task::COMPLETED)]);
        Task::factory()->for($this->folder)->create(['status_id' => Task::statusId(Task::CANCELLED)]);

        $this->actingAs($manager, 'cabinet')
            ->get(route('cabinet.folders.index'))
            ->assertSee('1 / 2 tâches terminées · 50 %');

        $this->actingAs($manager, 'cabinet')
            ->get(route('cabinet.folders.show', $this->folder))
            ->assertSee('1 / 2 tâches terminées · 50 %');
    }

    public function test_an_empty_sub_task_is_refused_with_a_french_message(): void
    {
        $this->actingAs($this->accountant, 'cabinet')
            ->post(route('cabinet.tasks.checklist-items.store', $this->task), ['label' => ''])
            ->assertSessionHasErrorsIn('storeChecklist', ['label' => 'Saisissez l\'intitulé de la sous-tâche.']);
    }
}
