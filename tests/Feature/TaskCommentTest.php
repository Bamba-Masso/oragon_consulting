<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->withoutVite();
    }

    public function test_a_collaborator_comments_a_task_they_can_see(): void
    {
        [$accountant, $task] = $this->assignedTask();

        $this->actingAs($accountant, 'cabinet')
            ->post(route('cabinet.tasks.comments.store', $task), ['form' => 'comment', 'content' => 'Relevés reçus du client.'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('folder.tasks.show', $task))
            ->assertSessionHas('status');

        $comment = $task->comments()->firstOrFail();
        $this->assertSame('Relevés reçus du client.', $comment->content);
        $this->assertSame($accountant->id, $comment->created_by);
    }

    public function test_an_empty_comment_is_refused(): void
    {
        [$accountant, $task] = $this->assignedTask();

        $this->actingAs($accountant, 'cabinet')
            ->post(route('cabinet.tasks.comments.store', $task), ['form' => 'comment', 'content' => ''])
            ->assertSessionHasErrorsIn('storeComment', 'content');

        $this->assertDatabaseCount('task_comments', 0);
    }

    public function test_the_author_updates_their_comment(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $comment = $this->commentBy($accountant, $task);

        $this->actingAs($accountant, 'cabinet')
            ->put(route('cabinet.tasks.comments.update', [$task, $comment]), ['content' => 'Texte corrigé.'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('folder.tasks.show', $task));

        $this->assertSame('Texte corrigé.', $comment->refresh()->content);
    }

    public function test_only_the_edited_comment_modal_reopens_after_a_failed_update(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $edited = $this->commentBy($accountant, $task);
        $other = $this->commentBy($accountant, $task);

        $this->actingAs($accountant, 'cabinet')
            ->from(route('folder.tasks.show', $task))
            ->followingRedirects()
            ->put(route('cabinet.tasks.comments.update', [$task, $edited]), [
                'form' => 'comment-edit',
                'comment_id' => $edited->id,
                'content' => '',
            ])
            ->assertOk()
            ->assertSee("getInstance('Modal', 'edit-comment-{$edited->id}')", false)
            ->assertDontSee("getInstance('Modal', 'edit-comment-{$other->id}')", false);
    }

    public function test_the_author_deletes_their_comment(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $comment = $this->commentBy($accountant, $task);

        $this->actingAs($accountant, 'cabinet')
            ->delete(route('cabinet.tasks.comments.destroy', [$task, $comment]))
            ->assertRedirect(route('folder.tasks.show', $task));

        $this->assertModelMissing($comment);
    }

    public function test_someone_else_cannot_update_nor_delete_the_comment(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $manager = User::factory()->withRole('manager')->create();
        $task->folder->assignees()->attach($manager->id, ['is_manager' => true]);
        $comment = $this->commentBy($accountant, $task);

        $this->actingAs($manager, 'cabinet')
            ->put(route('cabinet.tasks.comments.update', [$task, $comment]), ['content' => 'Réécrit.'])
            ->assertForbidden();

        $this->actingAs($manager, 'cabinet')
            ->delete(route('cabinet.tasks.comments.destroy', [$task, $comment]))
            ->assertForbidden();

        $this->actingAs($manager, 'cabinet')
            ->get(route('folder.tasks.show', $task))
            ->assertSee($comment->content)
            ->assertDontSee('id="edit-comment-'.$comment->id.'"', false)
            ->assertDontSee('id="delete-comment-'.$comment->id.'"', false);

        $this->assertModelExists($comment);
    }

    public function test_a_comment_of_another_task_cannot_be_reached_through_this_task(): void
    {
        [$accountant, $task] = $this->assignedTask();
        $otherTask = Task::factory()->for($task->folder)->assignedTo($accountant)->create();
        $otherComment = $this->commentBy($accountant, $otherTask);

        $this->actingAs($accountant, 'cabinet')
            ->delete(route('cabinet.tasks.comments.destroy', [$task, $otherComment]))
            ->assertNotFound();
    }

    public function test_a_task_outside_the_scope_cannot_be_commented(): void
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $task = Task::factory()->create();

        $this->actingAs($accountant, 'cabinet')
            ->post(route('cabinet.tasks.comments.store', $task), ['content' => 'Intrusion'])
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Task}
     */
    private function assignedTask(): array
    {
        $accountant = User::factory()->withRole('comptable')->create();
        $folder = Folder::factory()->assignedTo($accountant)->create();

        return [$accountant, Task::factory()->for($folder)->assignedTo($accountant)->create()];
    }

    private function commentBy(User $author, Task $task): TaskComment
    {
        $comment = new TaskComment(['content' => fake()->sentence()]);
        $comment->created_by = $author->id;
        $task->comments()->save($comment);

        return $comment;
    }
}
