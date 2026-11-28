<?php

use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\DocumentController;
use App\Http\Controllers\Client\DocumentRequestController;
use App\Http\Controllers\Client\FolderController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\DocumentRequestMessageController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class)->name('client.dashboard');

Route::controller(ProfileController::class)->prefix('profile')->name('client.profile')->group(function () {
    Route::get('/', 'show');
    Route::put('/', 'update')->name('.update');
    Route::put('password', 'updatePassword')->name('.password');
});

Route::get('document-requests', [DocumentRequestController::class, 'index'])->name('client.document-requests.index');
Route::get('document-requests/{documentRequest}', [DocumentRequestController::class, 'show'])->name('client.document-requests.show');
Route::patch('document-requests/{documentRequest}/acceptance', [DocumentRequestController::class, 'accept'])
    ->name('client.document-requests.accept');
Route::patch('document-requests/{documentRequest}/rejection', [DocumentRequestController::class, 'reject'])
    ->name('client.document-requests.reject');
Route::post('document-requests/{documentRequest}/messages', [DocumentRequestMessageController::class, 'store'])
    ->name('client.document-requests.messages.store');
Route::get('document-requests/{documentRequest}/attachments/{attachment}', [DocumentRequestMessageController::class, 'download'])
    ->scopeBindings()
    ->name('client.document-requests.attachments.download');

Route::patch('notifications/read', [NotificationController::class, 'markAllAsRead'])->name('client.notifications.read');
Route::get('notifications/{notification}', [NotificationController::class, 'open'])->name('client.notifications.open');

Route::get('folders', [FolderController::class, 'index'])->name('client.folders.index');
Route::get('folders/{folder}', [FolderController::class, 'show'])->name('client.folders.show');

Route::get('documents', [DocumentController::class, 'index'])->name('client.documents.index');
Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('client.documents.download');
