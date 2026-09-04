<?php

use App\Http\Controllers\Admin\Tasks\MobileTaskController;
use App\Http\Controllers\API\V1\Schedule\RecordController as ApiRecordController;
use App\Http\Controllers\API\V1\Schedule\ScheduleController as ApiScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (app()->runningUnitTests() || app()->runningInConsole()) {
        return response('OK', 200);
    }

    $user = \Illuminate\Support\Facades\Auth::guard('admin')->user()
        ?? \Illuminate\Support\Facades\Auth::user();

    if ($user) {
        $access = app(\App\Services\Access\AccessService::class);

        if ($access->isAdmin($user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($access->isCrmEmailUser($user)) {
            return redirect()->route('crm.index');
        }

        if ($access->hasStaffAccess($user)) {
            return redirect()->route('agent.index');
        }

        if ($access->isCrmDepartment($user)) {
            return redirect()->route('crm.index');
        }

        return redirect()->route('portal.home');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $user = \Illuminate\Support\Facades\Auth::guard('admin')->user()
        ?? \Illuminate\Support\Facades\Auth::user();

    if ($user) {
        $access = app(\App\Services\Access\AccessService::class);
        if ($access->hasStaffAccess($user)) {
            return redirect()->route('admin.dashboard');
        }
        if ($access->isCrmEmailUser($user) || $access->isCrmDepartment($user)) {
            return redirect()->route('crm.index');
        }
        if ($access->canAccessAgent($user)) {
            return redirect()->route('agent.index');
        }

        return redirect()->route('portal.home');
    }

    return redirect()->route('login');
})->middleware(['auth'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Rotas de Admin (Renderização de Views/Blade)
|--------------------------------------------------------------------------
*/
// (Moved to admin.php)

/*
|--------------------------------------------------------------------------
| Rotas de Agente (Suporte / Helpdesk)
|--------------------------------------------------------------------------
*/
// (Moved to agent.php)

/*
|--------------------------------------------------------------------------
| Rotas de CRM
|--------------------------------------------------------------------------
*/
// (Moved to crm.php)

/*
|--------------------------------------------------------------------------
| Rotas de API (Agente / Helpdesk) - Schedule V1
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('api/v1')->group(function () {
        Route::get('schedules/{id}/finalize', [ApiScheduleController::class, 'finalize']);
        Route::get('schedules/{id}/confirm', [ApiScheduleController::class, 'confirm']);
        Route::get('schedules/{id}/cancel', [ApiScheduleController::class, 'cancel']);
        Route::get('schedules/{id}/sync', [ApiScheduleController::class, 'sync']);
        Route::get('schedules/import', [ApiScheduleController::class, 'import']);

        Route::get('record/{id}/sync', [ApiRecordController::class, 'sync']);

        Route::middleware(['admin'])->group(function () {
            Route::get('schedules/{id}/remove', [ApiScheduleController::class, 'remove']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Atalhos de URL amigáveis
|--------------------------------------------------------------------------
*/
Route::redirect('/tickets', '/support/tickets', 301);

/*
|--------------------------------------------------------------------------
| Tarefas — Inbox compartilhada de staff
| Acessível a administradores e agentes autenticados no guard admin.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin', 'agent', 'password.changed'])->prefix('tasks')->name('tasks.')->group(function () {
    Route::get('/', [MobileTaskController::class, 'index'])->name('index');
    Route::get('catalog', [MobileTaskController::class, 'catalog'])->name('catalog');
    Route::post('/', [MobileTaskController::class, 'store'])->name('store');
    Route::patch('{task}', [MobileTaskController::class, 'update'])->name('update');
    Route::patch('{task}/status', [MobileTaskController::class, 'updateStatus'])->name('status.update');

    Route::get('mobile', function (Request $request) {
        return redirect()->route('tasks.index', $request->query(), 301);
    })->name('mobile');
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
