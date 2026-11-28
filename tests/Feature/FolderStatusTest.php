<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FolderStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();

        $this->manager = User::factory()->withRole('manager')->create();
        $this->folder = Folder::factory()->managedBy($this->manager)->create();
    }

    public function test_a_new_folder_is_pending_and_the_manager_follows_the_whole_workflow(): void
    {
        $this->assertSame(Folder::statusId(Folder::PENDING), $this->folder->status_id);

        $steps = [
            ['cabinet.folders.start', Folder::IN_PROGRESS],
            ['cabinet.folders.suspend', Folder::SUSPENDED],
            ['cabinet.folders.resume', Folder::IN_PROGRESS],
            ['cabinet.folders.close', Folder::CLOSED],
        ];

        foreach ($steps as [$route, $expectedStatus]) {
            $this->actingAs($this->manager, 'cabinet')
                ->from(route('cabinet.folders.show', $this->folder))
                ->patch(route($route, $this->folder))
                ->assertRedirect(route('cabinet.folders.show', $this->folder))
                ->assertSessionHas('status');

            $this->assertSame(Folder::statusId($expectedStatus), $this->folder->refresh()->status_id);
        }

        $this->assertNotNull($this->folder->closed_at);
        $this->assertSame(4, $this->folder->statusChanges()->count());
        $this->assertDatabaseHas('folder_status_changes', [
            'folder_id' => $this->folder->id,
            'from_status_id' => Folder::statusId(Folder::IN_PROGRESS),
            'to_status_id' => Folder::statusId(Folder::CLOSED),
            'changed_by' => $this->manager->id,
        ]);
    }

    public function test_only_the_folder_manager_and_the_administrator_can_change_its_status(): void
    {
        $member = User::factory()->withRole('manager')->create();
        $administrator = User::factory()->withRole('administrateur')->create();
        $this->folder->assignees()->attach([$member->id, $administrator->id]);

        $this->actingAs($member, 'cabinet')
            ->patch(route('cabinet.folders.start', $this->folder))
            ->assertForbidden();

        $this->actingAs($member, 'cabinet')
            ->get(route('cabinet.folders.show', $this->folder))
            ->assertDontSee('action="'.route('cabinet.folders.start', $this->folder).'"', false);

        $this->actingAs($administrator, 'cabinet')
            ->patch(route('cabinet.folders.start', $this->folder))
            ->assertRedirect();

        $this->assertSame(Folder::statusId(Folder::IN_PROGRESS), $this->folder->refresh()->status_id);
    }

    public function test_a_status_change_must_follow_the_workflow(): void
    {
        $this->actingAs($this->manager, 'cabinet')
            ->patch(route('cabinet.folders.close', $this->folder))
            ->assertForbidden();

        $this->actingAs($this->manager, 'cabinet')
            ->patch(route('cabinet.folders.resume', $this->folder))
            ->assertForbidden();

        $this->assertSame(Folder::statusId(Folder::PENDING), $this->folder->refresh()->status_id);
    }

    public function test_a_closed_folder_is_final_and_can_only_be_consulted(): void
    {
        Storage::fake('local');
        $folder = Folder::factory()->closed()->managedBy($this->manager)->create();
        $task = Task::factory()->for($folder)->assignedTo($this->manager)->create();
        $document = Document::factory()->for($folder)->create();
        Storage::disk('local')->put($document->file_path, 'contenu');

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.folders.show', $folder))
            ->assertOk()
            ->assertSee('Ce dossier est terminé')
            ->assertDontSee('id="edit-folder-modal"', false)
            ->assertDontSee('id="create-task-modal"', false);

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.documents.download', $document))
            ->assertOk();

        foreach (['cabinet.folders.start', 'cabinet.folders.resume', 'cabinet.folders.suspend'] as $route) {
            $this->actingAs($this->manager, 'cabinet')->patch(route($route, $folder))->assertForbidden();
        }

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.tasks.store', $folder), ['title' => 'Nouvelle tâche'])
            ->assertForbidden();

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.documents.store', $folder), [
                'form' => 'document',
                'file' => UploadedFile::fake()->create('recu.pdf', 10, 'application/pdf'),
                'name' => 'Reçu',
                'category' => 'Reçu',
            ])
            ->assertForbidden();

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.document-requests.store', $folder), ['title' => 'Relevés', 'description' => 'Relevés bancaires'])
            ->assertForbidden();

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.tasks.comments.store', $task), ['content' => 'Un commentaire'])
            ->assertForbidden();
    }
}
