<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\Folder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabinetDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();

        $this->manager = User::factory()->withRole('manager')->create(['first_name' => 'Awa']);
        $this->folder = Folder::factory()->inProgress()->managedBy($this->manager)
            ->create(['title' => 'Bilan annuel', 'due_date' => today()->subDay()]);
    }

    public function test_the_dashboard_shows_the_key_figures_of_the_collaborator_scope(): void
    {
        Folder::factory()->managedBy($this->manager)->create(['due_date' => today()->addMonths(2)]);
        Folder::factory()->closed()->managedBy($this->manager)->create();
        Folder::factory()->create();
        Task::factory()->for($this->folder)->assignedTo($this->manager)->create(['due_date' => today()->subDays(2)]);
        Task::factory()->for($this->folder)->assignedTo($this->manager)->create(['due_date' => null]);
        Task::factory()->for($this->folder)->assignedTo($this->manager)
            ->create(['status_id' => Task::statusId(Task::COMPLETED), 'completed_at' => now()]);
        Task::factory()->for($this->folder)->create();
        DocumentRequest::factory()->for($this->folder)->count(2)->create();
        DocumentRequest::factory()->for($this->folder)->received()->create();
        DocumentRequest::factory()->for($this->folder)->closed()->create();
        Document::factory()->for($this->folder)->create();
        Document::factory()->for($this->folder)->published()->create();

        $response = $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Bonjour Awa');

        $this->assertSame(2, $response->viewData('activeFoldersCount'));
        $this->assertSame(1, $response->viewData('overdueFoldersCount'));
        $this->assertSame(2, $response->viewData('openTasksCount'));
        $this->assertSame(1, $response->viewData('overdueTasksCount'));
        $this->assertSame(2, $response->viewData('pendingRequestsCount'));
        $this->assertSame(1, $response->viewData('receivedRequestsCount'));
        $this->assertSame(1, $response->viewData('documentsToSendCount'));
    }

    public function test_the_dashboard_lists_the_next_tasks_and_the_folders_due_soon(): void
    {
        $task = Task::factory()->for($this->folder)->assignedTo($this->manager)->create(['title' => 'Rapprochement bancaire']);
        Task::factory()->for($this->folder)->create(['title' => 'Tâche d\'un collègue']);
        Folder::factory()->managedBy($this->manager)->create(['title' => 'Dossier lointain', 'due_date' => today()->addMonths(2)]);

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Rapprochement bancaire')
            ->assertSee(route('folder.tasks.show', $task))
            ->assertDontSee('Tâche d\'un collègue')
            ->assertSee('Bilan annuel')
            ->assertSee('en retard depuis le')
            ->assertDontSee('Dossier lointain');
    }

    public function test_the_blocks_follow_the_permissions_of_the_role(): void
    {
        $response = $this->actingAs(User::factory()->withRole('auditeur')->create(), 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Mes prochaines tâches')
            ->assertDontSee('en attente du client');

        $this->assertNull($response->viewData('pendingRequestsCount'));
    }

    public function test_the_dashboard_shows_empty_states(): void
    {
        $this->actingAs(User::factory()->withRole('manager')->create(), 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Aucune tâche à faire pour le moment.')
            ->assertSee('Aucun dossier à rendre prochainement.');
    }
}
