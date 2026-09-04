<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User autenticado via Sanctum
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ─────────────────────────────────────────────────────────────────────────────
// WhatsApp Webhook — público (sem autenticação), autenticado pelo provedor
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('webhook/whatsapp')
    ->name('webhook.whatsapp.')
    ->middleware('throttle:whatsapp:webhook')
    ->group(function () {
        // Verificação de endpoint (challenge GET — usado por provedores como Meta)
        Route::get('/', [\App\Http\Controllers\WhatsApp\WebhookController::class, 'verify'])
            ->name('verify');

        // Recebimento de mensagens inbound
        Route::post('/', [\App\Http\Controllers\WhatsApp\WebhookController::class, 'receive'])
            ->name('receive');
    });

// API V1 — consumida pelo frontend Blade (sessão web + guard admin)
Route::middleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Session\Middleware\StartSession::class,
    'auth:admin',
    'throttle:api',
])
    ->prefix('v1')
    ->group(function () {

        // Tickets — Atendimentos
        Route::get('tickets/{ticket}/attendances', [\App\Http\Controllers\API\V1\Tickets\AttendanceController::class, 'index']);
        Route::post('tickets/{ticket}/attendances', [\App\Http\Controllers\API\V1\Tickets\AttendanceController::class, 'store'])
            ->middleware('throttle:api:strict');

        // Tickets — Auditoria (histórico de modificações)
        Route::get('tickets/{ticket}/audits', [\App\Http\Controllers\API\V1\Tickets\TicketAuditController::class, 'index']);

        // Tickets — Múltiplos Problemas
        Route::get('tickets/{ticket}/issues', [\App\Http\Controllers\API\V1\Tickets\TicketIssueController::class, 'index']);
        Route::post('tickets/{ticket}/issues', [\App\Http\Controllers\API\V1\Tickets\TicketIssueController::class, 'store'])
            ->middleware('throttle:api:strict');
        Route::patch('tickets/{ticket}/issues/{issue}/resolve', [\App\Http\Controllers\API\V1\Tickets\TicketIssueController::class, 'resolve'])
            ->middleware('throttle:api:strict');
        Route::delete('tickets/{ticket}/issues/{issue}', [\App\Http\Controllers\API\V1\Tickets\TicketIssueController::class, 'destroy']);

        // Tickets — Anexos (upload, view, delete)
        Route::get('tickets/{ticket}/attachments', [\App\Http\Controllers\API\V1\Tickets\AttachmentController::class, 'index']);
        Route::post('attachments', [\App\Http\Controllers\API\V1\Tickets\AttachmentController::class, 'store'])
            ->middleware('throttle:api:strict');
        Route::get('attachments/{attachment}/view', [\App\Http\Controllers\API\V1\Tickets\AttachmentController::class, 'show']);
        Route::delete('attachments/{attachment}', [\App\Http\Controllers\API\V1\Tickets\AttachmentController::class, 'destroy']);

        // Agents
        Route::get('agents/birthdays', [\App\Http\Controllers\API\V1\Agent\AgentController::class, 'birthdays']);

        // Módulos de implantação por cliente
        Route::get('companies/{company}/schedule-modules',
            [\App\Http\Controllers\API\V1\Schedule\CompanyScheduleModulesController::class, 'index'])
            ->name('api.companies.schedule-modules');

        // Schedules
        Route::prefix('schedules')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\V1\Schedule\ScheduleController::class, 'index']);
            Route::post('{id}/finalize', [\App\Http\Controllers\API\V1\Schedule\ScheduleController::class, 'finalize'])
                ->middleware('throttle:api:strict');
            Route::get('import', [\App\Http\Controllers\API\V1\Schedule\ScheduleController::class, 'import'])
                ->middleware('throttle:api:strict');
        });

        // Record Elements (checklist por módulo)
        Route::get('records/elements', [\App\Http\Controllers\Agent\ElementController::class, 'index'])
            ->name('api.records.elements.index');

        // Records
        Route::prefix('schedules/{schedule}')->group(function () {
            Route::get('records', [\App\Http\Controllers\API\V1\Schedule\RecordController::class, 'index']);
            Route::get('records/{record}', [\App\Http\Controllers\API\V1\Schedule\RecordController::class, 'show']);
            Route::post('records/{record}/sync', [\App\Http\Controllers\API\V1\Schedule\RecordController::class, 'sync'])
                ->middleware('throttle:api:strict');
        });

        // System
        Route::get('version', [\App\Http\Controllers\API\V1\Tasks\VersionController::class, 'index']);
    });

// ─────────────────────────────────────────────────────────────────────────────
// API V1 — Integração com o sistema financeiro (inbound, servidor-a-servidor)
// Autenticação por API key (X-API-Key). Rate limit por IP roda ANTES do check da
// chave para conter brute-force (OWASP A07/A04).
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['throttle:integration', 'integration.apikey'])
    ->prefix('v1/integration')
    ->name('api.v1.integration.')
    ->group(function () {
        Route::post('customers', [\App\Http\Controllers\API\V1\Integration\CustomerController::class, 'store'])
            ->name('customers.store');
        Route::patch('customers/{id}/inactivate', [\App\Http\Controllers\API\V1\Integration\CustomerController::class, 'inactivate'])
            ->whereNumber('id')
            ->name('customers.inactivate');
        Route::patch('customers/{id}/reactivate', [\App\Http\Controllers\API\V1\Integration\CustomerController::class, 'reactivate'])
            ->whereNumber('id')
            ->name('customers.reactivate');
    });

// ─────────────────────────────────────────────────────────────────────────────
// API V1 — Sistema de Chamados de Plantão (Mobile Offline-first)
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('v1/oncall')
    ->name('api.v1.oncall.')
    ->group(function () {
        Route::get('master-data', [\App\Http\Controllers\API\V1\Oncall\OncallSyncController::class, 'masterData'])
            ->name('master-data');
        Route::post('sync', [\App\Http\Controllers\API\V1\Oncall\OncallSyncController::class, 'sync'])
            ->name('sync');
        Route::get('reports', [\App\Http\Controllers\API\V1\Oncall\OncallSyncController::class, 'reports'])
            ->name('reports');
    });

