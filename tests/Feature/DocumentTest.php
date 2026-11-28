<?php

namespace Tests\Feature;

use App\Mail\DocumentPublished;
use App\Models\ClientUser;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private ClientUser $client;

    private Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $this->manager = User::factory()->withRole('manager')->create();
        $this->client = ClientUser::factory()->withRole()->for($organization)->create();
        $this->folder = Folder::factory()->for($organization)->managedBy($this->manager)->create();
    }

    public function test_a_collaborator_adds_an_internal_document_to_the_folder(): void
    {
        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.documents.store', $this->folder), $this->validData())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cabinet.folders.show', $this->folder))
            ->assertSessionHas('status');

        $document = $this->folder->documents()->firstOrFail();
        $this->assertSame('Reçu de dépôt TVA', $document->name);
        $this->assertSame('Reçu', $document->category);
        $this->assertSame('recu-tva.pdf', $document->original_filename);
        $this->assertSame($this->folder->organization_id, $document->organization_id);
        $this->assertTrue($document->uploader->is($this->manager));
        $this->assertFalse($document->is_published_to_client);
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.folders.show', $this->folder))
            ->assertSee('Reçu de dépôt TVA')
            ->assertSee('À envoyer');
    }

    public function test_a_document_needs_a_file_a_name_and_a_category(): void
    {
        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.documents.store', $this->folder), ['form' => 'document'])
            ->assertSessionHasErrorsIn('storeDocument', ['file', 'name', 'category']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_collaborators_without_the_permission_or_outside_the_folder_cannot_add_a_document(): void
    {
        $auditor = User::factory()->withRole('auditeur')->create();
        $this->folder->assignees()->attach($auditor->id, ['is_manager' => false]);
        $otherManager = User::factory()->withRole('manager')->create();

        $this->actingAs($auditor, 'cabinet')
            ->post(route('cabinet.folders.documents.store', $this->folder), $this->validData())
            ->assertForbidden();

        $this->actingAs($otherManager, 'cabinet')
            ->post(route('cabinet.folders.documents.store', $this->folder), $this->validData())
            ->assertNotFound();
    }

    public function test_sending_a_document_publishes_it_and_emails_the_active_client_users(): void
    {
        Mail::fake();
        $inactiveClient = ClientUser::factory()->withRole()->inactive()->for($this->folder->organization)->create();
        $document = Document::factory()->for($this->folder)->create();

        $this->actingAs($this->manager, 'cabinet')
            ->from(route('cabinet.folders.show', $this->folder))
            ->patch(route('cabinet.documents.publish', $document))
            ->assertRedirect(route('cabinet.folders.show', $this->folder));

        $document->refresh();
        $this->assertTrue($document->is_published_to_client);
        $this->assertNotNull($document->published_at);
        $this->assertSame($this->manager->id, $document->published_by);

        Mail::assertSent(DocumentPublished::class, fn (DocumentPublished $mail) => $mail->hasTo($this->client->email));
        Mail::assertNotSent(DocumentPublished::class, fn (DocumentPublished $mail) => $mail->hasTo($inactiveClient->email));

        $this->actingAs($this->manager, 'cabinet')
            ->patch(route('cabinet.documents.publish', $document))
            ->assertForbidden();
    }

    public function test_a_document_forgotten_at_closure_can_still_be_sent_but_nothing_else(): void
    {
        Mail::fake();
        $this->folder->update(['closed_at' => now(), 'status_id' => Folder::statusId(Folder::CLOSED)]);
        $forgotten = Document::factory()->for($this->folder)->create();
        $sent = Document::factory()->for($this->folder)->published()->create();

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.documents.index'))
            ->assertSee('id="publish-document-'.$forgotten->id.'"', false);

        $this->actingAs($this->manager, 'cabinet')
            ->patch(route('cabinet.documents.publish', $forgotten))
            ->assertRedirect();

        $this->assertTrue($forgotten->refresh()->is_published_to_client);
        Mail::assertSent(DocumentPublished::class, fn (DocumentPublished $mail) => $mail->hasTo($this->client->email));

        $this->actingAs($this->manager, 'cabinet')
            ->delete(route('cabinet.documents.unpublish', $sent))
            ->assertForbidden();

        $admin = User::factory()->withRole('administrateur')->create();
        $this->folder->assignees()->attach($admin);
        $this->actingAs($admin, 'cabinet')
            ->delete(route('cabinet.documents.destroy', $forgotten))
            ->assertForbidden();
    }

    public function test_only_collaborators_allowed_to_publish_can_send_a_document(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $this->folder->assignees()->attach($accountant->id, ['is_manager' => false]);
        $document = Document::factory()->for($this->folder)->create();

        $this->actingAs($accountant, 'cabinet')
            ->patch(route('cabinet.documents.publish', $document))
            ->assertForbidden();

        $this->actingAs($accountant, 'cabinet')
            ->get(route('cabinet.folders.show', $this->folder))
            ->assertDontSee('id="publish-document-'.$document->id.'"', false);

        $this->assertFalse($document->refresh()->is_published_to_client);
    }

    public function test_a_document_sent_by_mistake_is_withdrawn_from_the_client_space(): void
    {
        $document = Document::factory()->for($this->folder)->published()->create();

        $this->actingAs($this->manager, 'cabinet')
            ->delete(route('cabinet.documents.unpublish', $document))
            ->assertRedirect();

        $document->refresh();
        $this->assertFalse($document->is_published_to_client);
        $this->assertNull($document->published_at);
    }

    public function test_only_collaborators_allowed_to_delete_can_delete_a_document(): void
    {
        $administrator = User::factory()->withRole('administrateur')->create();
        $this->folder->assignees()->attach($administrator->id, ['is_manager' => false]);
        $document = Document::factory()->for($this->folder)->create();

        $this->actingAs($this->manager, 'cabinet')
            ->delete(route('cabinet.documents.destroy', $document))
            ->assertForbidden();

        $this->actingAs($administrator, 'cabinet')
            ->delete(route('cabinet.documents.destroy', $document))
            ->assertRedirect();

        $this->assertSoftDeleted($document);
    }

    public function test_the_cabinet_lists_and_downloads_the_documents_of_its_folders(): void
    {
        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.documents.store', $this->folder), $this->validData());
        $document = $this->folder->documents()->firstOrFail();
        Document::factory()->create(['name' => 'Bilan d\'une autre entreprise']);

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.documents.index'))
            ->assertOk()
            ->assertSee('Reçu de dépôt TVA')
            ->assertDontSee('Bilan d\'une autre entreprise');

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.documents.download', $document))
            ->assertOk()
            ->assertDownload('recu-tva.pdf');
    }

    public function test_the_cabinet_filters_documents_by_company_and_sends_them_from_the_list(): void
    {
        $otherFolder = Folder::factory()->managedBy($this->manager)->create();
        $internal = Document::factory()->for($this->folder)->create(['name' => 'Reçu de dépôt TVA']);
        Document::factory()->for($otherFolder)->published()->create(['name' => 'Bilan de l\'autre entreprise']);

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.documents.index', ['organization' => $this->folder->organization_id]))
            ->assertOk()
            ->assertSee('Reçu de dépôt TVA')
            ->assertDontSee('Bilan de l\'autre entreprise')
            ->assertSee('id="publish-document-'.$internal->id.'"', false);
    }

    public function test_the_client_only_sees_and_downloads_the_documents_sent_to_their_company(): void
    {
        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.documents.store', $this->folder), $this->validData());
        $sent = $this->folder->documents()->firstOrFail();
        $sent->forceFill(['is_published_to_client' => true, 'published_at' => now()])->save();
        $internal = Document::factory()->for($this->folder)->create(['name' => 'Note interne']);
        $otherCompany = Document::factory()->published()->create(['name' => 'Document d\'une autre entreprise']);

        $this->actingAs($this->client, 'client')
            ->get(route('client.documents.index'))
            ->assertOk()
            ->assertSee('Reçu de dépôt TVA')
            ->assertDontSee('Note interne')
            ->assertDontSee('Document d\'une autre entreprise');

        $this->actingAs($this->client, 'client')
            ->get(route('client.documents.download', $sent))
            ->assertOk()
            ->assertDownload('recu-tva.pdf');

        $this->actingAs($this->client, 'client')
            ->get(route('client.documents.download', $internal))
            ->assertNotFound();

        $this->actingAs($this->client, 'client')
            ->get(route('client.documents.download', $otherCompany))
            ->assertNotFound();
    }

    public function test_the_client_cannot_add_documents(): void
    {
        $this->assertFalse($this->client->can('upload-documents'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'form' => 'document',
            'file' => UploadedFile::fake()->create('recu-tva.pdf', 150, 'application/pdf'),
            'name' => 'Reçu de dépôt TVA',
            'category' => 'Reçu',
            'period' => 'T3 2026',
        ];
    }
}
