<?php

namespace App\Providers;

use App\Models\Anomaly;
use App\Models\ClientUser;
use App\Models\Document;
use App\Models\DocumentMessageAttachment;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestMessage;
use App\Models\Folder;
use App\Models\FolderAssignment;
use App\Models\FolderStatusChange;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Status;
use App\Models\StatusType;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        /*
         * Alias stables stockés dans les colonnes polymorphes (model_has_roles.model_type,
         * documents.uploader_type, ...) : renommer une classe ne casse plus les données.
         */
        Relation::enforceMorphMap([
            'user' => User::class,
            'client_user' => ClientUser::class,
            'organization' => Organization::class,
            'role' => Role::class,
            'permission' => Permission::class,
            'status_type' => StatusType::class,
            'status' => Status::class,
            'priority' => Priority::class,
            'folder' => Folder::class,
            'folder_assignment' => FolderAssignment::class,
            'folder_status_change' => FolderStatusChange::class,
            'task' => Task::class,
            'task_checklist' => TaskChecklist::class,
            'task_comment' => TaskComment::class,
            'document' => Document::class,
            'document_request' => DocumentRequest::class,
            'document_request_message' => DocumentRequestMessage::class,
            'document_message_attachment' => DocumentMessageAttachment::class,
            'anomaly' => Anomaly::class,
        ]);

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));
    }
}
