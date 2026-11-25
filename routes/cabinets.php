<?php

use App\Http\Controllers\Cabinet\ClientUserController;
use App\Http\Controllers\Cabinet\DocumentController;
use App\Http\Controllers\Cabinet\FolderController;
use App\Http\Controllers\Cabinet\FolderMemberController;
use App\Http\Controllers\Cabinet\FolderStatusController;
use App\Http\Controllers\Cabinet\FolderTaskController;
use App\Http\Controllers\Cabinet\OrganizationController;
use App\Http\Controllers\Cabinet\ProfileController;
use App\Http\Controllers\Cabinet\RequestController;
use App\Http\Controllers\Cabinet\TaskChecklistController;
use App\Http\Controllers\Cabinet\TaskCommentController;
use App\Http\Controllers\Cabinet\TaskController;
use App\Http\Controllers\Cabinet\TaskStatusController;
use App\Http\Controllers\Cabinet\UserController;
use App\Http\Controllers\DocumentRequestMessageController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', function () {
    return view('cabinet.dashboard');
})->name('cabinet.dashboard');

Route::controller(ProfileController::class)->prefix('profile')->name('cabinet.profile')->group(function () {
    Route::get('/', 'show');
    Route::put('/', 'update')->name('.update');
    Route::put('password', 'updatePassword')->name('.password');
});

Route::patch('organizations/{organization}/activation', [OrganizationController::class, 'toggleActivation'])
    ->name('cabinet.organizations.activation');
Route::resource('organizations', OrganizationController::class)
    ->except(['create'])
    ->names('cabinet.organizations');

Route::patch('client-users/{client_user}/activation', [ClientUserController::class, 'toggleActivation'])
    ->name('cabinet.client-users.activation');
Route::resource('client-users', ClientUserController::class)
    ->only(['index', 'store', 'show', 'update'])
    ->names('cabinet.client-users');

Route::patch('users/{user}/activation', [UserController::class, 'toggleActivation'])
    ->name('cabinet.users.activation');
Route::resource('users', UserController::class)
    ->only(['index', 'store', 'show', 'update'])
    ->names('cabinet.users');

Route::resource('folders', FolderController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy'])
    ->names('cabinet.folders');

Route::patch('folders/{folder}/start', [FolderStatusController::class, 'start'])->name('cabinet.folders.start');
Route::patch('folders/{folder}/suspension', [FolderStatusController::class, 'suspend'])->name('cabinet.folders.suspend');
Route::patch('folders/{folder}/resumption', [FolderStatusController::class, 'resume'])->name('cabinet.folders.resume');
Route::patch('folders/{folder}/closure', [FolderStatusController::class, 'close'])->name('cabinet.folders.close');

Route::post('folders/{folder}/tasks', [FolderTaskController::class, 'store'])
    ->name('cabinet.folders.tasks.store');
Route::scopeBindings()->group(function () {
    Route::put('folders/{folder}/tasks/{task}', [FolderTaskController::class, 'update'])
        ->name('cabinet.folders.tasks.update');
    Route::delete('folders/{folder}/tasks/{task}', [FolderTaskController::class, 'destroy'])
        ->name('cabinet.folders.tasks.destroy');
});
Route::get('my_tasks', [TaskController::class, 'index'])->name('folder.tasks');
Route::get('task/{task}', [TaskController::class, 'show'])->name('folder.tasks.show');

Route::patch('tasks/{task}/start', [TaskStatusController::class, 'start'])->name('cabinet.tasks.start');
Route::patch('tasks/{task}/completion', [TaskStatusController::class, 'complete'])->name('cabinet.tasks.complete');
Route::patch('tasks/{task}/cancellation', [TaskStatusController::class, 'cancel'])->name('cabinet.tasks.cancel');

Route::post('tasks/{task}/checklist-items', [TaskChecklistController::class, 'store'])
    ->name('cabinet.tasks.checklist-items.store');
Route::scopeBindings()->group(function () {
    Route::patch('tasks/{task}/checklist-items/{checklistItem}/completion', [TaskChecklistController::class, 'toggleCompletion'])
        ->name('cabinet.tasks.checklist-items.completion');
    Route::delete('tasks/{task}/checklist-items/{checklistItem}', [TaskChecklistController::class, 'destroy'])
        ->name('cabinet.tasks.checklist-items.destroy');
});

Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])
    ->name('cabinet.tasks.comments.store');
Route::scopeBindings()->group(function () {
    Route::put('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'update'])
        ->name('cabinet.tasks.comments.update');
    Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])
        ->name('cabinet.tasks.comments.destroy');
});

Route::post('folders/{folder}/members', [FolderMemberController::class, 'store'])
    ->name('cabinet.folders.members.store');
Route::delete('folders/{folder}/members/{user}', [FolderMemberController::class, 'destroy'])
    ->name('cabinet.folders.members.destroy');

Route::get('document-requests', [RequestController::class, 'index'])->name('cabinet.document-requests.index');
Route::post('folders/{folder}/document-requests', [RequestController::class, 'store'])
    ->name('cabinet.folders.document-requests.store');
Route::get('document-requests/{documentRequest}', [RequestController::class, 'show'])->name('cabinet.document-requests.show');
Route::post('document-requests/{documentRequest}/reminders', [RequestController::class, 'remind'])
    ->name('cabinet.document-requests.remind');
Route::patch('document-requests/{documentRequest}/closure', [RequestController::class, 'close'])
    ->name('cabinet.document-requests.close');
Route::post('document-requests/{documentRequest}/messages', [DocumentRequestMessageController::class, 'store'])
    ->name('cabinet.document-requests.messages.store');
Route::get('document-requests/{documentRequest}/attachments/{attachment}', [DocumentRequestMessageController::class, 'download'])
    ->scopeBindings()
    ->name('cabinet.document-requests.attachments.download');

Route::get('documents', [DocumentController::class, 'index'])->name('cabinet.documents.index');
Route::post('folders/{folder}/documents', [DocumentController::class, 'store'])->name('cabinet.folders.documents.store');
Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('cabinet.documents.download');
Route::patch('documents/{document}/publication', [DocumentController::class, 'publish'])->name('cabinet.documents.publish');
Route::delete('documents/{document}/publication', [DocumentController::class, 'unpublish'])->name('cabinet.documents.unpublish');
Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('cabinet.documents.destroy');
