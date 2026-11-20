<?php

use App\Http\Controllers\Cabinet\ClientUserController;
use App\Http\Controllers\Cabinet\OrganizationController;
use App\Http\Controllers\Cabinet\ProfileController;
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
