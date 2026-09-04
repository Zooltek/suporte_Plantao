<?php

use App\Http\Controllers\Helpdesk\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('home');

    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::post('/', [ChatController::class, 'store'])->name('store');
        Route::get('{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('{conversation}/messages', [ChatController::class, 'storeMessage'])->name('message.store');
        Route::post('{conversation}/close', [ChatController::class, 'close'])->name('close');
    });
});
