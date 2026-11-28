<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\Folder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDashboardTest extends TestCase
{
    use RefreshDatabase;

    private ClientUser $clientUser;

    private Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();

        $this->clientUser = ClientUser::factory()->withRole()->create(['first_name' => 'Yao']);
        $this->folder = Folder::factory()->inProgress()->create(['organization_id' => $this->clientUser->organization_id]);
    }

    public function test_the_dashboard_shows_the_key_figures_of_the_company(): void
    {
        Folder::factory()->closed()->create(['organization_id' => $this->clientUser->organization_id]);
        Folder::factory()->create();
        DocumentRequest::factory()->for($this->folder)->create();
        DocumentRequest::factory()->for($this->folder)->received()->create();
        DocumentRequest::factory()->for($this->folder)->closed()->create();
        Document::factory()->published()->for($this->folder)->count(2)->create();
        Document::factory()->published()->for($this->folder)->create(['published_at' => now()->subMonths(2)]);
        Document::factory()->for($this->folder)->create();

        $response = $this->actingAs($this->clientUser, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Bonjour Yao');

        $this->assertSame(1, $response->viewData('activeFoldersCount'));
        $this->assertSame(2, $response->viewData('openRequestsCount'));
        $this->assertSame(3, $response->viewData('documentsCount'));
        $this->assertSame(2, $response->viewData('recentDocumentsCount'));
    }

    public function test_the_dashboard_lists_the_open_requests_and_the_latest_documents(): void
    {
        $pending = DocumentRequest::factory()->for($this->folder)->create(['title' => 'Relevés bancaires de mars']);
        DocumentRequest::factory()->for($this->folder)->closed()->create(['title' => 'Demande déjà clôturée']);
        $sent = Document::factory()->published()->for($this->folder)->create(['name' => 'Bilan signé']);
        Document::factory()->for($this->folder)->create(['name' => 'Note interne']);
        Document::factory()->published()->create(['name' => 'Document d\'une autre entreprise']);

        $this->actingAs($this->clientUser, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Relevés bancaires de mars')
            ->assertSee(route('client.document-requests.show', $pending))
            ->assertDontSee('Demande déjà clôturée')
            ->assertSee('Bilan signé')
            ->assertSee(route('client.documents.download', $sent))
            ->assertDontSee('Note interne')
            ->assertDontSee('Document d\'une autre entreprise');
    }

    public function test_the_dashboard_shows_empty_states(): void
    {
        $this->actingAs($this->clientUser, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Aucune pièce à fournir pour le moment.')
            ->assertSee('Aucun document reçu pour le moment.');
    }
}
