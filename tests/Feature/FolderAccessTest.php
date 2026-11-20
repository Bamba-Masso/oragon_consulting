<?php

namespace Tests\Feature;

use App\Models\ClientUser;
use App\Models\Folder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FolderAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_a_client_only_sees_the_folders_of_their_own_organization(): void
    {
        $client = ClientUser::factory()->withRole()->create();
        $ownFolder = Folder::factory()->for($client->organization)->create();
        $otherFolder = Folder::factory()->create();

        $this->assertEqualsCanonicalizing([$ownFolder->id], Folder::visibleTo($client)->pluck('id')->all());
        $this->assertTrue($this->inspect($client, 'view', $ownFolder)->allowed());
        $this->assertHidden($client, 'view', $otherFolder);
    }

    public function test_a_client_cannot_perform_cabinet_actions_on_their_own_folder(): void
    {
        $client = ClientUser::factory()->withRole()->create();
        $folder = Folder::factory()->for($client->organization)->create();

        foreach (['update', 'assign', 'review', 'approve', 'publish', 'delete', 'restore'] as $ability) {
            $this->assertForbidden($client, $ability, $folder);
        }

        $this->assertFalse($client->can('create', Folder::class));
    }

    public function test_a_collaborator_only_sees_the_folders_they_manage_are_assigned_to_or_have_a_task_in(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();

        $managedFolder = Folder::factory()->managedBy($accountant)->create();
        $assignedFolder = Folder::factory()->assignedTo($accountant, 'Comptabilité')->create();
        $folderWithTask = Task::factory()->assignedTo($accountant)->create()->folder;
        $unrelatedFolder = Folder::factory()->create();

        $this->assertEqualsCanonicalizing(
            [$managedFolder->id, $assignedFolder->id, $folderWithTask->id],
            Folder::visibleTo($accountant)->pluck('id')->all(),
        );
        $this->assertHidden($accountant, 'view', $unrelatedFolder);
        $this->assertHidden($accountant, 'update', $unrelatedFolder);
    }

    public function test_a_deleted_task_no_longer_opens_access_to_its_folder(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $task = Task::factory()->assignedTo($accountant)->create();

        $task->delete();

        $this->assertHidden($accountant, 'view', $task->folder);
    }

    public function test_within_their_folders_collaborators_are_limited_by_the_permissions_of_their_role(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $auditor = User::factory()->withRole('auditeur')->create();
        $manager = User::factory()->withRole('manager')->create();
        $folder = Folder::factory()->managedBy($manager)->assignedTo($accountant)->assignedTo($auditor)->create();

        $this->assertTrue($this->inspect($accountant, 'update', $folder)->allowed());
        $this->assertForbidden($accountant, 'assign', $folder);
        $this->assertForbidden($accountant, 'publish', $folder);

        $this->assertTrue($this->inspect($auditor, 'review', $folder)->allowed());
        $this->assertForbidden($auditor, 'update', $folder);

        $this->assertTrue($this->inspect($manager, 'assign', $folder)->allowed());
        $this->assertTrue($this->inspect($manager, 'publish', $folder)->allowed());
    }

    public function test_the_direction_sees_every_folder_without_being_able_to_update_them(): void
    {
        $director = User::factory()->withRole('direction')->create();
        $folders = Folder::factory()->count(2)->create();

        $this->assertEqualsCanonicalizing($folders->pluck('id')->all(), Folder::visibleTo($director)->pluck('id')->all());
        $this->assertTrue($this->inspect($director, 'approve', $folders->first())->allowed());
        $this->assertForbidden($director, 'update', $folders->first());
    }

    public function test_the_administrator_does_not_see_the_content_of_folders_they_are_not_assigned_to(): void
    {
        $administrator = User::factory()->withRole('administrateur')->create();
        $folder = Folder::factory()->create();

        $this->assertTrue($administrator->can('create', Folder::class));
        $this->assertSame(0, Folder::visibleTo($administrator)->count());
        $this->assertHidden($administrator, 'view', $folder);
    }

    public function test_a_soft_deleted_folder_can_only_be_restored_by_an_authorized_collaborator_of_its_scope(): void
    {
        $administrator = User::factory()->withRole('administrateur')->create();
        $outsider = User::factory()->withRole('administrateur')->create();
        $folder = Folder::factory()->assignedTo($administrator)->create();

        $folder->delete();

        $this->assertTrue($this->inspect($administrator, 'restore', $folder)->allowed());
        $this->assertHidden($outsider, 'restore', $folder);
        $this->assertFalse($administrator->can('forceDelete', $folder));
    }

    public function test_an_assignment_stores_its_pole_on_the_pivot(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->assignedTo($accountant, 'Comptabilité')->create();

        $assignment = $folder->assignees()->first()->pivot;

        $this->assertNotNull($assignment->id);
        $this->assertSame('Comptabilité', $assignment->pole);
        $this->assertTrue($accountant->assignedFolders()->whereKey($folder->id)->exists());
    }

    /**
     * Refus 404 : le dossier est hors du périmètre de l'utilisateur.
     */
    private function assertHidden(User|ClientUser $user, string $ability, Folder $folder): void
    {
        $this->assertSame(404, $this->inspect($user, $ability, $folder)->status(), "« {$ability} » devrait répondre 404.");
    }

    /**
     * Refus 403 : le dossier est visible, mais le rôle n'a pas la permission.
     */
    private function assertForbidden(User|ClientUser $user, string $ability, Folder $folder): void
    {
        $response = $this->inspect($user, $ability, $folder);

        $this->assertTrue($response->denied(), "« {$ability} » devrait être refusé.");
        $this->assertNotSame(404, $response->status(), "« {$ability} » devrait répondre 403, pas 404.");
    }

    private function inspect(User|ClientUser $user, string $ability, Folder $folder): Response
    {
        return Gate::forUser($user)->inspect($ability, $folder);
    }
}
