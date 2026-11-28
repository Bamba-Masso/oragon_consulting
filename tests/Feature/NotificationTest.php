<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_the_bell_shows_the_unread_notifications_of_the_collaborator(): void
    {
        $user = User::factory()->withRole('manager')->create();
        $this->notify($user, 'Une nouvelle tâche vous a été assignée.');
        $this->notify(User::factory()->create(), 'Notification d\'un autre collaborateur');

        $this->actingAs($user, 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertOk()
            ->assertSee('Une nouvelle tâche vous a été assignée.')
            ->assertSee('Notifications (1 non lues)')
            ->assertDontSee('Notification d\'un autre collaborateur');
    }

    public function test_the_bell_is_empty_without_notification(): void
    {
        $this->actingAs(ClientUser::factory()->withRole()->create(), 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Aucune notification.');
    }

    public function test_all_notifications_are_marked_as_read(): void
    {
        $clientUser = ClientUser::factory()->withRole()->create();
        $notification = $this->notify($clientUser, 'Un document vous a été envoyé.');

        $this->actingAs($clientUser, 'client')
            ->from(route('client.dashboard'))
            ->patch(route('client.notifications.read'))
            ->assertRedirect(route('client.dashboard'));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_opening_a_notification_marks_it_as_read_and_opens_its_page(): void
    {
        $user = User::factory()->withRole('manager')->create();
        $notification = $this->notify($user, 'Une nouvelle tâche vous a été assignée.', route('folder.tasks'));

        $this->actingAs($user, 'cabinet')
            ->get(route('cabinet.dashboard'))
            ->assertSee(route('cabinet.notifications.open', $notification->id));

        $this->actingAs($user, 'cabinet')
            ->get(route('cabinet.notifications.open', $notification->id))
            ->assertRedirect(route('folder.tasks'));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_the_notification_of_someone_else_cannot_be_opened(): void
    {
        $notification = $this->notify(ClientUser::factory()->withRole()->create(), 'Un document vous a été envoyé.', route('client.documents.index'));

        $this->actingAs(ClientUser::factory()->withRole()->create(), 'client')
            ->get(route('client.notifications.open', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->refresh()->read_at);
    }

    public function test_only_the_notifications_read_for_more_than_a_week_are_pruned(): void
    {
        $user = User::factory()->create();
        $oldRead = $this->notify($user, 'Lue il y a 8 jours');
        $oldRead->forceFill(['read_at' => now()->subDays(8)])->save();
        $recentRead = $this->notify($user, 'Lue il y a 2 jours');
        $recentRead->forceFill(['read_at' => now()->subDays(2)])->save();
        $oldUnread = $this->notify($user, 'Jamais lue');
        $oldUnread->forceFill(['created_at' => now()->subMonths(3)])->save();

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertModelMissing($oldRead);
        $this->assertModelExists($recentRead);
        $this->assertModelExists($oldUnread);
    }

    private function notify(User|ClientUser $notifiable, string $message, ?string $url = null): DatabaseNotification
    {
        return $notifiable->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => ['message' => $message, 'url' => $url],
        ]);
    }
}
