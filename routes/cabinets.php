<?php

use App\Http\Controllers\Cabinet\ClientUserController;
use App\Http\Controllers\Cabinet\FolderController;
use App\Http\Controllers\Cabinet\FolderMemberController;
use App\Http\Controllers\Cabinet\FolderTaskController;
use App\Http\Controllers\Cabinet\OrganizationController;
use App\Http\Controllers\Cabinet\ProfileController;
use App\Http\Controllers\Cabinet\UserController;
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

Route::post('folders/{folder}/tasks', [FolderTaskController::class, 'store'])
    ->name('cabinet.folders.tasks.store');
Route::scopeBindings()->group(function () {
    Route::put('folders/{folder}/tasks/{task}', [FolderTaskController::class, 'update'])
        ->name('cabinet.folders.tasks.update');
    Route::delete('folders/{folder}/tasks/{task}', [FolderTaskController::class, 'destroy'])
        ->name('cabinet.folders.tasks.destroy');
});

Route::post('folders/{folder}/members', [FolderMemberController::class, 'store'])
    ->name('cabinet.folders.members.store');
Route::delete('folders/{folder}/members/{user}', [FolderMemberController::class, 'destroy'])
    ->name('cabinet.folders.members.destroy');
