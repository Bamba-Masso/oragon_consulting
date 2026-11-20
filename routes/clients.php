<?php

use App\Http\Controllers\Client\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', function () {
    return view('client.dashboard');
})->name('client.dashboard');

Route::controller(ProfileController::class)->prefix('profile')->name('client.profile')->group(function () {
    Route::get('/', 'show');
    Route::put('/', 'update')->name('.update');
    Route::put('password', 'updatePassword')->name('.password');
});
