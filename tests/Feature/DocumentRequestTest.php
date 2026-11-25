<?php

namespace Tests\Feature;

use App\Mail\DocumentRequestSent;
use App\Models\ClientUser;
use App\Models\DocumentRequest;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentRequestTest extends TestCase
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

        $organization = Organization::factory()->create();
        $this->manager = User::factory()->withRole('manager')->create();
        $this->client = ClientUser::factory()->withRole()->for($organization)->create();
        $this->folder = Folder::factory()->for($organization)->managedBy($this->manager)->create();
    }

    public function test_a_manager_sends_a_pending_request_and_the_active_client_users_are_emailed(): void
    {
        Mail::fake();
        $inactiveClient = ClientUser::factory()->withRole()->inactive()->for($this->folder->organization)->create();

        $response = $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.document-requests.store', $this->folder), [
                'form' => 'document-request',
                'title' => 'Relevés bancaires T3',
                'description' => 'Relevés de juillet à septembre.',
            ])
            ->assertSessionHasNoErrors();

        $documentRequest = DocumentRequest::firstOrFail();
        $response->assertRedirect(route('cabinet.document-requests.show', $documentRequest));

        $this->assertTrue($documentRequest->hasStatus(DocumentRequest::PENDING));
        $this->assertStringStartsWith('DEM-'.now()->year.'-', $documentRequest->reference);
        $this->assertSame($this->manager->id, $documentRequest->requested_by);

        Mail::assertSent(DocumentRequestSent::class, fn (DocumentRequestSent $mail) => $mail->hasTo($this->client->email) && ! $mail->isReminder);
        Mail::assertNotSent(DocumentRequestSent::class, fn (DocumentRequestSent $mail) => $mail->hasTo($inactiveClient->email));
    }

    public function test_the_request_needs_a_title_and_the_expected_documents(): void
    {
        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.folders.document-requests.store', $this->folder), ['form' => 'document-request', 'title' => '', 'description' => ''])
            ->assertSessionHasErrorsIn('storeDocumentRequest', ['title', 'description']);

        $this->assertDatabaseCount('document_requests', 0);
    }

    public function test_collaborators_without_the_permission_or_outside_the_folder_cannot_send_a_request(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $this->folder->assignees()->attach($accountant->id, ['is_manager' => false]);
        $otherManager = User::factory()->withRole('manager')->create();
        $data = ['title' => 'Relevés', 'description' => 'Relevés bancaires.'];

        $this->actingAs($accountant, 'cabinet')
            ->post(route('cabinet.folders.document-requests.store', $this->folder), $data)
            ->assertForbidden();

        $this->actingAs($otherManager, 'cabinet')
            ->post(route('cabinet.folders.document-requests.store', $this->folder), $data)
            ->assertNotFound();
    }

    public function test_the_client_only_sees_the_requests_of_their_company(): void
    {
        $ownRequest = DocumentRequest::factory()->for($this->folder)->create(['title' => 'Factures de vente']);
        $otherRequest = DocumentRequest::factory()->create(['title' => 'Bulletins de paie']);

        $this->actingAs($this->client, 'client')
            ->get(route('client.document-requests.index'))
            ->assertOk()
            ->assertSee('Factures de vente')
            ->assertDontSee('Bulletins de paie');

        $this->actingAs($this->client, 'client')
            ->get(route('client.document-requests.show', $otherRequest))
            ->assertNotFound();

        $this->actingAs($this->client, 'client')
            ->get(route('client.document-requests.show', $ownRequest))
            ->assertOk()
            ->assertSee('Marquer comme reçue');
    }

    public function test_the_client_marks_the_request_as_received_and_the_discussion_opens(): void
    {
        $documentRequest = DocumentRequest::factory()->for($this->folder)->create();

        $this->actingAs($this->client, 'client')
            ->patch(route('client.document-requests.accept', $documentRequest))
            ->assertRedirect(route('client.document-requests.show', $documentRequest));

        $documentRequest->refresh();
        $this->assertTrue($documentRequest->hasStatus(DocumentRequest::RECEIVED));
        $this->assertSame($this->client->id, $documentRequest->responded_by);

        $this->actingAs($this->client, 'client')
            ->get(route('client.document-requests.show', $documentRequest))
            ->assertSee(route('client.document-requests.messages.store', $documentRequest))
            ->assertDontSee('id="accept-request-modal"', false);
    }

    public function test_a_rejection_needs_a_reason(): void
    {
        $documentRequest = DocumentRequest::factory()->for($this->folder)->create();

        $this->actingAs($this->client, 'client')
            ->patch(route('client.document-requests.reject', $documentRequest), ['rejection_reason' => ''])
            ->assertSessionHasErrorsIn('rejectDocumentRequest', 'rejection_reason');

        $this->assertTrue($documentRequest->refresh()->hasStatus(DocumentRequest::PENDING));
    }

    public function test_a_rejected_request_stays_listed_but_its_detail_is_closed_to_the_client(): void
    {
        $documentRequest = DocumentRequest::factory()->for($this->folder)->create(['title' => 'Factures de vente']);

        $this->actingAs($this->client, 'client')
            ->patch(route('client.document-requests.reject', $documentRequest), ['rejection_reason' => 'Pièces déjà transmises.'])
            ->assertRedirect(route('client.document-requests.index'));

        $documentRequest->refresh();
        $this->assertTrue($documentRequest->hasStatus(DocumentRequest::REJECTED));
        $this->assertSame('Pièces déjà transmises.', $documentRequest->rejection_reason);

        $this->actingAs($this->client, 'client')
            ->get(route('client.document-requests.index'))
            ->assertSee('Factures de vente')
            ->assertDontSee(route('client.document-requests.show', $documentRequest));

        $this->actingAs($this->client, 'client')
            ->get(route('client.document-requests.show', $documentRequest))
            ->assertForbidden();

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.document-requests.show', $documentRequest))
            ->assertOk()
            ->assertSee('Pièces déjà transmises.')
            ->assertDontSee('id="remind-request-modal"', false)
            ->assertDontSee('id="close-request-modal"', false);
    }

    public function test_a_request_already_answered_cannot_be_answered_again(): void
    {
        $documentRequest = DocumentRequest::factory()->for($this->folder)->received()->create();

        $this->actingAs($this->client, 'client')
            ->patch(route('client.document-requests.reject', $documentRequest), ['rejection_reason' => 'Trop tard.'])
            ->assertForbidden();
    }

    public function test_no_message_can_be_sent_before_the_request_is_received(): void
    {
        $documentRequest = DocumentRequest::factory()->for($this->folder)->create();

        $this->actingAs($this->client, 'client')
            ->post(route('client.document-requests.messages.store', $documentRequest), ['message' => 'Bonjour'])
            ->assertForbidden();

        $this->assertDatabaseCount('document_request_messages', 0);
    }

    public function test_both_sides_discuss_and_exchange_files_once_the_request_is_received(): void
    {
        Storage::fake('local');
        $documentRequest = DocumentRequest::factory()->for($this->folder)->received()->create();

        $this->actingAs($this->client, 'client')
            ->post(route('client.document-requests.messages.store', $documentRequest), [
                'message' => 'Voici les relevés.',
                'attachments' => [UploadedFile::fake()->create('releves-t3.pdf', 120, 'application/pdf')],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('client.document-requests.show', $documentRequest));

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.document-requests.messages.store', $documentRequest), ['message' => 'Merci, bien reçu.'])
            ->assertRedirect(route('cabinet.document-requests.show', $documentRequest));

        $attachment = $documentRequest->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->file_path);
        $this->assertSame('releves-t3.pdf', $attachment->file_name);

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.document-requests.show', $documentRequest))
            ->assertSee('Voici les relevés.')
            ->assertSee('Merci, bien reçu.')
            ->assertSee('releves-t3.pdf');

        $this->actingAs($this->manager, 'cabinet')
            ->get(route('cabinet.document-requests.attachments.download', [$documentRequest, $attachment]))
            ->assertOk()
            ->assertDownload('releves-t3.pdf');
    }

    public function test_an_empty_message_without_file_is_refused(): void
    {
        $documentRequest = DocumentRequest::factory()->for($this->folder)->received()->create();

        $this->actingAs($this->client, 'client')
            ->post(route('client.document-requests.messages.store', $documentRequest), ['message' => ''])
            ->assertSessionHasErrorsIn('storeMessage', 'message');
    }

    public function test_a_file_of_another_request_cannot_be_downloaded_through_this_request(): void
    {
        Storage::fake('local');
        $documentRequest = DocumentRequest::factory()->for($this->folder)->received()->create();
        $otherRequest = DocumentRequest::factory()->received()->create();

        $this->actingAs($this->client, 'client')
            ->post(route('client.document-requests.messages.store', $documentRequest), [
                'attachments' => [UploadedFile::fake()->create('releves.pdf', 10, 'application/pdf')],
            ]);
        $attachment = $documentRequest->attachments()->firstOrFail();

        $this->actingAs($this->client, 'client')
            ->get(route('client.document-requests.attachments.download', [$otherRequest, $attachment]))
            ->assertNotFound();
    }

    public function test_the_cabinet_reminds_a_pending_request_only(): void
    {
        Mail::fake();
        $pending = DocumentRequest::factory()->for($this->folder)->create();
        $received = DocumentRequest::factory()->for($this->folder)->received()->create();

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.document-requests.remind', $pending))
            ->assertRedirect(route('cabinet.document-requests.show', $pending));

        $this->assertNotNull($pending->refresh()->last_reminded_at);
        Mail::assertSent(DocumentRequestSent::class, fn (DocumentRequestSent $mail) => $mail->hasTo($this->client->email) && $mail->isReminder);

        $this->actingAs($this->manager, 'cabinet')
            ->post(route('cabinet.document-requests.remind', $received))
            ->assertForbidden();
    }

    public function test_a_closed_request_can_still_be_read_but_no_action_is_possible(): void
    {
        $documentRequest = DocumentRequest::factory()->for($this->folder)->received()->create();

        $this->actingAs($this->manager, 'cabinet')
            ->patch(route('cabinet.document-requests.close', $documentRequest))
            ->assertRedirect(route('cabinet.document-requests.show', $documentRequest));

        $documentRequest->refresh();
        $this->assertTrue($documentRequest->hasStatus(DocumentRequest::CLOSED));
        $this->assertSame($this->manager->id, $documentRequest->closed_by);

        $this->actingAs($this->client, 'client')
            ->get(route('client.document-requests.show', $documentRequest))
            ->assertOk()
            ->assertSee('la discussion est en lecture seule')
            ->assertDontSee(route('client.document-requests.messages.store', $documentRequest));

        $this->actingAs($this->client, 'client')
            ->post(route('client.document-requests.messages.store', $documentRequest), ['message' => 'Encore une pièce'])
            ->assertForbidden();

        $this->actingAs($this->manager, 'cabinet')
            ->patch(route('cabinet.document-requests.close', $documentRequest))
            ->assertForbidden();
    }
}
