<?php
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\FeedbackController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:admin'])->group(function () {

    Route::middleware(['feedback'])->group(function () {
        Route::get('crm', [DashboardController::class, 'index'])->name('crm.index');

        Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('feedback/create', [FeedbackController::class, 'create'])->name('feedback.create');
        Route::post('feedback', [FeedbackController::class, 'store'])->name('feedback.store');
        Route::delete('feedback/{id}', [FeedbackController::class, 'destroy'])->name('feedback.destroy');
        Route::get('feedback/customer/{id}/history', [FeedbackController::class, 'history'])->name('feedback.customer.history');

        Route::get('feedback/{id}/retorno', [FeedbackController::class, 'retorno'])->name('feedback.retorno.show');
        Route::post('feedback/{id}/retorno', [FeedbackController::class, 'retornoUpdate'])->name('feedback.retorno');
        Route::post('feedback/cancel', [FeedbackController::class, 'cancel'])->name('feedback.cancel');
    });
});
