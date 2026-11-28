<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Priority;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $accountant;

    private ClientUser $client;

    private Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
        Mail::fake();

        $organization = Organization::factory()->create();
        $this->manager = User::factory()->withRole('manager')->create(['first_name' => 'Awa', 'last_name' => 'Koné']);
        $this->accountant = User::factory()->withRole('comptable')->create(['first_name' => 'Koffi', 'last_name' => 'Yao']);
        $this->client = ClientUser::factory()->withRole()->for($organization)->create(['first_name' => 'Marie', 'last_name' => 'Kouassi']);
        $this->folder = Folder::factory()->for($organization)->managedBy($this->manager)->assignedTo($this->accountant)
            ->create(['reference' => 'DOS-2026-0001', 'title' => 'Bilan annuel']);
    }

    public function test_the_assignee_of_a_new_task_is_notified_but_not_its_author(): void
    {
        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.tasks.store', $this->folder), $this->taskData(['assigned_to' => $this->accountant->id]))
            ->assertSessionHasNoErrors();

        $task = Task::firstOrFail();
        $this->assertNotification($this->accountant, 'Awa Koné vous a assigné la tâche « Rapprochement bancaire » du dossier DOS-2026-0001.', route('folder.tasks.show', $task));
        $this->assertSame(0, $this->manager->notifications()->count());

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.tasks.store', $this->folder), $this->taskData(['assigned_to' => $this->manager->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $this->manager->notifications()->count());
    }

    public function test_only_a_new_assignee_is_notified_when_a_task_is_updated(): void
    {
        $task = Task::factory()->for($this->folder)->create(['title' => 'Rapprochement bancaire']);
        $update = fn () => $this->actingAs($this->manager, 'cabinet')
            ->put(route('cabinet.folders.tasks.update', [$this->folder, $task]), $this->taskData([
                'form' => 'task-edit',
                'task_id' => $task->id,
                'assigned_to' => $this->accountant->id,
            ]))
            ->assertSessionHasNoErrors();

        $update();
        $update();

        $this->assertSame(1, $this->accountant->notifications()->count());
    }

    public function test_a_collaborator_added_to_the_team_is_notified(): void
    {
        $auditor = User::factory()->withRole('auditeur')->create();

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.members.store', $this->folder), ['user_id' => $auditor->id])
            ->assertSessionHasNoErrors();

        $this->assertNotification($auditor, 'Awa Koné vous a ajouté(e) à l\'équipe du dossier DOS-2026-0001 — Bilan annuel.', route('cabinet.folders.show', $this->folder));
    }

    public function test_the_new_manager_and_the_new_members_are_notified_when_the_team_changes(): void
    {
        $auditor = User::factory()->withRole('auditeur')->create();
        $taxAdvisor = User::factory()->withRole('fiscaliste')->create();
        $this->folder->assignees()->attach($taxAdvisor);

        $this->actingAs($this->manager, 'cabinet')
            ->put(route('cabinet.folders.update', $this->folder), [
                'title' => $this->folder->title,
                'mission_type' => $this->folder->mission_type,
                'period' => $this->folder->period,
                'priority_id' => $this->folder->priority_id,
                'manager_id' => $this->accountant->id,
                'assignee_ids' => [$this->manager->id, $auditor->id, $taxAdvisor->id],
                'due_date' => $this->folder->due_date?->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();

        $url = route('cabinet.folders.show', $this->folder);
        $this->assertNotification($this->accountant, 'Awa Koné vous a désigné(e) responsable du dossier DOS-2026-0001 — Bilan annuel.', $url);
        $this->assertNotification($auditor, 'Awa Koné vous a ajouté(e) à l\'équipe du dossier DOS-2026-0001 — Bilan annuel.', $url);
        $this->assertSame(0, $taxAdvisor->notifications()->count());
        $this->assertSame(0, $this->manager->notifications()->count());
    }

    public function test_the_team_and_the_company_follow_the_folder_status(): void
    {
        $inactiveClient = ClientUser::factory()->withRole()->inactive()->for($this->folder->organization)->create();

        $this->actingAs($this->manager, 'cabinet')->patch(route('cabinet.folders.start', $this->folder));

        $this->assertNotification($this->client, 'Votre dossier DOS-2026-0001 — Bilan annuel a démarré.', route('client.folders.show', $this->folder));
        $this->assertSame(0, $this->accountant->notifications()->count());

        $this->actingAs($this->manager, 'cabinet')->patch(route('cabinet.folders.close', $this->folder));

        $this->assertNotification($this->accountant, 'Awa Koné a terminé le dossier DOS-2026-0001 — Bilan annuel.', route('cabinet.folders.show', $this->folder));
        $this->assertSame(2, $this->client->notifications()->count());
        $this->assertSame(0, $this->manager->notifications()->count());
        $this->assertSame(0, $inactiveClient->notifications()->count());
    }

    public function test_the_assignee_is_notified_of_a_comment_on_their_task_except_their_own(): void
    {
        $task = Task::factory()->for($this->folder)->assignedTo($this->accountant)->create(['title' => 'Saisie des factures']);

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.tasks.comments.store', $task), ['form' => 'comment', 'content' => 'Pense aux avoirs.']);
        $this->actingAs($this->accountant, 'cabinet')
            ->post(route('cabinet.tasks.comments.store', $task), ['form' => 'comment', 'content' => 'C\'est noté.']);

        $this->assertNotification($this->accountant, 'Awa Koné a commenté la tâche « Saisie des factures ».', route('folder.tasks.show', $task));
    }

    public function test_the_company_is_notified_of_a_new_request_and_of_its_reminder(): void
    {
        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.document-requests.store', $this->folder), [
                'title' => 'Relevés bancaires T3',
                'description' => 'Relevés de juillet à septembre.',
            ])
            ->assertSessionHasNoErrors();

        $documentRequest = DocumentRequest::firstOrFail();
        $url = route('client.document-requests.show', $documentRequest);
        $this->assertNotification($this->client, "Le cabinet vous demande des pièces : « Relevés bancaires T3 » ({$documentRequest->reference}).", $url);

        $this->actingAs($this->manager, 'cabinet')->post(route('cabinet.document-requests.remind', $documentRequest));

        $this->assertEqualsCanonicalizing(
            [
                "Le cabinet vous demande des pièces : « Relevés bancaires T3 » ({$documentRequest->reference}).",
                "Relance : le cabinet attend toujours vos pièces pour la demande « Relevés bancaires T3 » ({$documentRequest->reference}).",
            ],
            $this->client->notifications()->get()->pluck('data.message')->all(),
        );
    }

    public function test_the_requester_and_the_folder_manager_are_notified_once_of_the_client_answer(): void
    {
        $received = DocumentRequest::factory()->for($this->folder)
            ->create(['requested_by' => $this->accountant->id, 'reference' => 'DEM-2026-0001', 'title' => 'Relevés T3']);
        $rejected = DocumentRequest::factory()->for($this->folder)
            ->create(['requested_by' => $this->manager->id, 'reference' => 'DEM-2026-0002', 'title' => 'Factures']);

        $this->actingAs($this->client, 'client')->patch(route('client.document-requests.accept', $received));
        $this->actingAs($this->client, 'client')
            ->patch(route('client.document-requests.reject', $rejected), ['rejection_reason' => 'Déjà transmises.']);

        $this->assertNotification($this->accountant, 'Marie Kouassi a confirmé la réception de la demande DEM-2026-0001 « Relevés T3 ».', route('cabinet.document-requests.show', $received));
        $this->assertEqualsCanonicalizing(
            ['Marie Kouassi a confirmé la réception de la demande DEM-2026-0001 « Relevés T3 ».', 'Marie Kouassi a rejeté la demande DEM-2026-0002 « Factures » : Déjà transmises.'],
            $this->manager->notifications()->get()->pluck('data.message')->all(),
        );
    }

    public function test_each_side_is_notified_of_the_messages_of_the_other(): void
    {
        $documentRequest = DocumentRequest::factory()->for($this->folder)->received()
            ->create(['requested_by' => $this->manager->id, 'reference' => 'DEM-2026-0001', 'title' => 'Relevés T3']);

        $this->actingAs($this->client, 'client')
            ->post(route('client.document-requests.messages.store', $documentRequest), ['message' => 'Voici les relevés.']);
        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.document-requests.messages.store', $documentRequest), ['message' => 'Merci.']);

        $this->assertNotification($this->manager, 'Nouveau message de Marie Kouassi sur la demande DEM-2026-0001 « Relevés T3 ».', route('cabinet.document-requests.show', $documentRequest));
        $this->assertNotification($this->client, 'Nouveau message du cabinet sur la demande DEM-2026-0001 « Relevés T3 ».', route('client.document-requests.show', $documentRequest));
    }

    public function test_the_company_is_notified_of_a_document_sent_by_the_cabinet(): void
    {
        $document = Document::factory()->for($this->folder)->create(['name' => 'Bilan signé']);

        $this->actingAs($this->manager, 'cabinet')
            ->patch(route('cabinet.documents.publish', $document))
            ->assertSessionHas('status');

        $this->assertNotification($this->client, 'Nouveau document disponible : « Bilan signé ».', route('client.documents.index'));
    }

    private function assertNotification(User|ClientUser $notifiable, string $message, string $url): void
    {
        $this->assertSame(['message' => $message, 'url' => $url], $notifiable->notifications()->sole()->data);
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array<string, string|null>
     */
    private function taskData(array $overrides = []): array
    {
        return [
            'form' => 'task',
            'title' => 'Rapprochement bancaire',
            'description' => null,
            'assigned_to' => null,
            'priority_id' => Priority::where('name', 'normal')->value('id'),
            'due_date' => now()->addWeek()->format('Y-m-d'),
            ...$overrides,
        ];
    }
}
