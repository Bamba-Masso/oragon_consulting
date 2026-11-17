<?php

use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\StaffAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::prefix('auth')->group(function () {
    Route::middleware('guest:client')->group(function () {
        Route::get('client/login', [ClientAuthController::class, 'index'])->name('client.login');
        Route::post('client/login', [ClientAuthController::class, 'login'])->middleware('throttle:5,1')->name('client.login.store');
    });
    Route::post('client/logout', [ClientAuthController::class, 'logout'])->middleware('auth:client')->name('client.logout');

    Route::middleware('guest:cabinet')->group(function () {
        Route::get('Administration/login', [StaffAuthController::class, 'index'])->name('cabinet.login');
        Route::post('Administration/login', [StaffAuthController::class, 'login'])->middleware('throttle:5,1')->name('cabinet.login.store');
    });
    Route::post('Administration/logout', [StaffAuthController::class, 'logout'])->middleware('auth:cabinet')->name('cabinet.logout');
});

Route::prefix('clients')->middleware(['auth:client'])->group(function () {
    require __DIR__.'/clients.php';
});

Route::prefix('cabinet')->middleware(['auth:cabinet'])->group(function () {
    require __DIR__.'/cabinets.php';
});
