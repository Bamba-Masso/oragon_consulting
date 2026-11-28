<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFolderTest extends TestCase
{
    use RefreshDatabase;

    private ClientUser $clientUser;

    private Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();

        $this->clientUser = ClientUser::factory()->withRole()->create();
        $this->folder = Folder::factory()
            ->managedBy(User::factory()->create(['first_name' => 'Awa', 'last_name' => 'Koné']))
            ->create(['organization_id' => $this->clientUser->organization_id, 'title' => 'Bilan annuel 2026']);
    }

    public function test_the_client_only_sees_the_folders_of_their_company_without_progress(): void
    {
        Document::factory()->published()->for($this->folder)->count(2)->create();
        Document::factory()->for($this->folder)->create();
        Task::factory()->for($this->folder)->create();
        Folder::factory()->create(['title' => 'Dossier d\'une autre entreprise']);

        $this->actingAs($this->clientUser, 'client')
            ->get(route('client.folders.index'))
            ->assertOk()
            ->assertSee('Bilan annuel 2026')
            ->assertSee('2 documents')
            ->assertSee('Suivi par Awa Koné')
            ->assertDontSee('tâches')
            ->assertDontSee('Dossier d\'une autre entreprise');
    }

    public function test_the_folder_detail_only_lists_the_documents_sent_to_the_client(): void
    {
        $sent = Document::factory()->published()->for($this->folder)->create(['name' => 'Bilan signé']);
        Document::factory()->for($this->folder)->create(['name' => 'Note interne']);
        Document::factory()->published()->create(['name' => 'Document d\'un autre dossier']);

        $this->actingAs($this->clientUser, 'client')
            ->get(route('client.folders.show', $this->folder))
            ->assertOk()
            ->assertSee('Bilan annuel 2026')
            ->assertSee('Bilan signé')
            ->assertSee(route('client.documents.download', $sent))
            ->assertDontSee('Note interne')
            ->assertDontSee('Document d\'un autre dossier')
            ->assertDontSee('Modifier')
            ->assertDontSee('Demander des pièces');
    }

    public function test_the_client_cannot_open_the_folder_of_another_company(): void
    {
        $this->actingAs($this->clientUser, 'client')
            ->get(route('client.folders.show', Folder::factory()->create()))
            ->assertNotFound();
    }

    public function test_the_menu_links_to_the_folders(): void
    {
        $this->actingAs($this->clientUser, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee(route('client.folders.index'));
    }
}
