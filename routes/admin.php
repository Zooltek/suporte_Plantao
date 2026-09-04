<?php

use App\Http\Controllers\Admin\API\V1\CategoryController as ApiCategoryController;
use App\Http\Controllers\Admin\API\V1\Crm\ElementTypeController;
use App\Http\Controllers\Admin\API\V1\UserController as ApiUserController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\Crm\FeedbackController;
use App\Http\Controllers\Admin\DashboardController; // <-- ADICIONADO AQUI
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\Helpdesk\CompanyModuleController;
use App\Http\Controllers\Admin\Helpdesk\DashboardController as HelpdeskDashboardController;
use App\Http\Controllers\Admin\Helpdesk\OriginController;
use App\Http\Controllers\Admin\Helpdesk\PriorityController;
use App\Http\Controllers\Admin\Helpdesk\StatusController;
use App\Http\Controllers\Admin\Implantacao\RatChecklistController;
use App\Http\Controllers\Admin\Implantacao\RatGroupController;
use App\Http\Controllers\Admin\Implantacao\RatModuleController;
use App\Http\Controllers\Admin\Implantacao\ScheduleModuleController;
use App\Http\Controllers\Admin\Implantacao\ScheduleTypeController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SlaConfigurationController;
use App\Http\Controllers\Admin\Tasks\MobileTaskController as AdminMobileTaskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\API\V1\Tasks\NotificationController as TaskNotificationController;
use App\Http\Controllers\API\V1\Tasks\ReportController as TaskReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. ROTAS DE VISITANTES (GUEST)
Route::prefix('admin')->middleware('guest:admin')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
    // A07 — brute-force: 5 tentativas/min por IP
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:auth');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('admin.password.request');
    // A07 — enumeração e flooding de reset: 3 req/min por IP
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('admin.password.email')->middleware('throttle:admin:password-reset');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('admin.password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('admin.password.update')->middleware('throttle:admin:password-reset');
});

Route::match(['get', 'post'], 'admin/logout', [AuthenticatedSessionController::class, 'destroy'])->name('admin.logout');

// Logout e endpoints acessíveis a todos usuários autenticados (sem restrição de role admin)
Route::prefix('admin')->middleware(['auth:admin'])->group(function () {

    // Troca obrigatória de senha no primeiro acesso (must_change_password=true)
    // Sem o middleware 'password.changed' para evitar loop de redirecionamento
    Route::get('password/force-change', [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::post('password/force-change', [ForcePasswordChangeController::class, 'update'])->name('password.force-change.update');

    // Notificações de Tarefas — acessível a qualquer usuário autenticado (agents incluídos)
    Route::prefix('tasks')->name('admin.tasks.')->group(function () {
        Route::get('notifications', [TaskNotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/{id}/seen', [TaskNotificationController::class, 'seen'])->name('notifications.seen');
    });
});

// 2. ROTAS AUTENTICADAS (AUTH)
Route::prefix('admin')->middleware(['auth:admin', 'admin', 'password.changed'])->group(function () {

    // Rota Principal (Dashboard)
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::prefix('api/v1/users')->middleware('throttle:admin:api')->group(function () {
        Route::get('/', [ApiUserController::class, 'index'])->name('admin.users.api.index');
        Route::post('/', [ApiUserController::class, 'store'])->name('admin.users.api.store');
        Route::get('/{user}/deletion-preview', [ApiUserController::class, 'deletionPreview'])->name('admin.users.api.deletion-preview');
        // A07 — reset de senha de usuário: limite mais restrito por IP
        Route::put('/{user}/password-reset', [ApiUserController::class, 'resetPassword'])
            ->name('admin.users.api.password-reset')->middleware('throttle:admin:password-reset');
        Route::put('/{user}', [ApiUserController::class, 'update'])->name('admin.users.api.update');
        Route::delete('/{user}', [ApiUserController::class, 'destroy'])->name('admin.users.api.destroy');
    });

    // Test Data (Admin Only)
    Route::prefix('api/v1/test-data')->group(function () {
        Route::get('agents', function () {
            return response()->json(\App\Models\User::where('ticketit_agent', 1)->get());
        })->name('admin.test-data.agents');

        Route::get('crm-feedback', function () {
            return response()->json(\App\Models\Crm\Feedback::all());
        })->name('admin.test-data.crm-feedback');
    });
    Route::get('users', [AdminUserController::class, 'index'])->name('admin.users.index');

    // Configurações de Conta
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::put('settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');

    // Categorias
    Route::get('categories', [CategoryController::class, 'index'])->name('admin.categories.index');

    // SLA (Gestão)
    Route::get('sla', [SlaConfigurationController::class, 'index'])->name('admin.sla.index');
    Route::put('sla', [SlaConfigurationController::class, 'update'])->name('admin.sla.update');

    // API Categorias
    Route::prefix('api/v1/categories')->name('admin.categories.api.')->middleware('throttle:admin:api')->group(function () {
        Route::get('/', [ApiCategoryController::class, 'index'])->name('list');
        Route::post('/root', [ApiCategoryController::class, 'storeRoot'])->name('store-root');
        Route::post('/subcategory', [ApiCategoryController::class, 'storeSubcategory'])->name('store-subcategory');
        Route::post('/', [ApiCategoryController::class, 'store'])->name('store');
        Route::get('{category}', [ApiCategoryController::class, 'show'])->name('show');
        Route::put('{category}', [ApiCategoryController::class, 'update'])->name('update');
        Route::delete('{category}', [ApiCategoryController::class, 'destroy'])->name('destroy');
    });

    // CRM Feedback
    Route::get('crm/feedback', [FeedbackController::class, 'index'])->name('admin.crm.feedback.index');
    Route::prefix('api/v1/crm/feedback')->middleware('throttle:admin:api')->group(function () {
        Route::get('element-types', [ElementTypeController::class, 'index']);
        Route::post('element-types', [ElementTypeController::class, 'store']);
        Route::put('element-types/{elementType}', [ElementTypeController::class, 'update']);
        Route::delete('element-types/{elementType}', [ElementTypeController::class, 'destroy']);
    });

    // Tarefas - Módulos
    Route::name('company.')->prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('/create', [CompanyController::class, 'create'])->name('create');
        Route::post('/store', [CompanyController::class, 'store'])->name('store');
        Route::post('/vincular', [CompanyController::class, 'vincular'])->name('vincular');
        Route::post('/cities', [CompanyController::class, 'cities'])->name('cities');
        Route::get('/{id}/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CompanyController::class, 'update'])->name('update');
    });

    // Departamentos
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
    });

    // API Departamentos
    Route::prefix('api/v1/departments')->name('api.v1.departments.')->middleware('throttle:admin:api')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\API\V1\DepartmentController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\API\V1\DepartmentController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Admin\API\V1\DepartmentController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Admin\API\V1\DepartmentController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\API\V1\DepartmentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tasks')->name('admin.tasks.')->group(function () {
        Route::get('/', function (Request $request) {
            return redirect()->route('tasks.index', $request->query(), 301);
        })->name('index');

        // Alias legado da inbox de tarefas
        Route::get('mobile', function (Request $request) {
            return redirect()->route('tasks.index', $request->query(), 301);
        })->name('mobile');

        Route::post('/', [AdminMobileTaskController::class, 'store'])->name('store');

        // Relatórios de Tarefas
        Route::get('report/carlos', [TaskReportController::class, 'carlos'])->name('report.carlos');
        Route::get('report/por-cliente', [TaskReportController::class, 'porCliente'])->name('report.por-cliente');
        Route::get('report/por-modulo', [TaskReportController::class, 'porModulo'])->name('report.por-modulo');

    });

    // Helpdesk Nativo (admin.helpdesk.*)
    Route::prefix('helpdesk')->name('admin.helpdesk.')->middleware('helpdesk.admin')->group(function () {
        Route::get('/', [HelpdeskDashboardController::class, 'index'])->name('dashboard');

        Route::resource('status', StatusController::class)->names([
            'index' => 'status.index',
            'create' => 'status.create',
            'store' => 'status.store',
            'edit' => 'status.edit',
            'update' => 'status.update',
            'destroy' => 'status.destroy',
        ]);

        Route::resource('priority', PriorityController::class)->names([
            'index' => 'priority.index',
            'create' => 'priority.create',
            'store' => 'priority.store',
            'edit' => 'priority.edit',
            'update' => 'priority.update',
            'destroy' => 'priority.destroy',
        ]);

        Route::resource('origins', OriginController::class)->names([
            'index' => 'origins.index',
            'create' => 'origins.create',
            'store' => 'origins.store',
            'edit' => 'origins.edit',
            'update' => 'origins.update',
            'destroy' => 'origins.destroy',
        ])->except(['show']);

        Route::resource('modules', CompanyModuleController::class)->names([
            'index' => 'modules.index',
            'create' => 'modules.create',
            'store' => 'modules.store',
            'edit' => 'modules.edit',
            'update' => 'modules.update',
            'destroy' => 'modules.destroy',
        ])->except(['show']);
    });

    // Implantação — configurações administrativas
    Route::prefix('implantacao')->name('admin.implantacao.')->group(function () {
        Route::get('modulos', [ScheduleModuleController::class, 'index'])->name('modules.index');
        Route::get('modulos/{company}/editar', [ScheduleModuleController::class, 'edit'])->name('modules.edit');
        Route::put('modulos/{company}', [ScheduleModuleController::class, 'update'])->name('modules.update');

        // RAT Checklist — CRUD de itens por módulo
        Route::resource('rat', RatChecklistController::class)->parameters(['rat' => 'rat']);

        // RAT Grupos — CRUD de agrupamentos de itens
        Route::resource('groups', RatGroupController::class)->parameters(['groups' => 'group']);

        // RAT Módulos — CRUD de módulos/projetos do RAT
        Route::resource('rat-modules', RatModuleController::class)->parameters(['rat-modules' => 'module']);

        // Tipos de Agendamento — CRUD configurável (substitui o enum hardcoded)
        Route::resource('schedule-types', ScheduleTypeController::class)
            ->parameters(['schedule-types' => 'schedule_type'])
            ->except(['show']);
    });

    // Relatórios
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        Route::get('daily-problems', [AdminReportController::class, 'dailyProblems'])->name('daily-problems');
        Route::get('implementation-clients', [AdminReportController::class, 'implementationClients'])->name('implementation-clients');
        Route::get('clients-without-attendance', [AdminReportController::class, 'clientsWithoutAttendance'])->name('clients-without-attendance');
        Route::get('client-updates', [AdminReportController::class, 'clientUpdates'])->name('client-updates');
        Route::get('client-updates/export', [AdminReportController::class, 'exportClientUpdates'])->name('client-updates.export');
    });

    // Relatórios e Manutenção de Plantão & Sobreaviso
    Route::prefix('oncall')->name('admin.oncall.')->group(function () {
        Route::get('reports', [\App\Http\Controllers\Admin\OncallReportController::class, 'index'])->name('reports');
        Route::get('reports/export-csv', [\App\Http\Controllers\Admin\OncallReportController::class, 'exportCsv'])->name('reports.export');
        Route::put('attendances/{attendance}', [\App\Http\Controllers\Admin\OncallReportController::class, 'updateAttendance'])->name('attendances.update');
        Route::delete('attendances/{attendance}', [\App\Http\Controllers\Admin\OncallReportController::class, 'destroyAttendance'])->name('attendances.destroy');
    });

    // WhatsApp — configuração e monitoramento
    Route::prefix('whatsapp')->name('admin.whatsapp.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\WhatsAppController::class, 'index'])->name('index');
        Route::get('/conversations/recent', [\App\Http\Controllers\Admin\WhatsAppController::class, 'recentConversations'])->name('conversations.recent');
        Route::get('/connection-state', [\App\Http\Controllers\Admin\WhatsAppController::class, 'connectionState'])->name('connection-state');
        Route::get('/qr-code', [\App\Http\Controllers\Admin\WhatsAppController::class, 'qrCode'])->name('qr-code');
        Route::post('/logout', [\App\Http\Controllers\Admin\WhatsAppController::class, 'logout'])->name('logout');
        Route::post('/settings', [\App\Http\Controllers\Admin\WhatsAppController::class, 'updateSettings'])->name('settings.update');
        Route::post('/bot-messages', [\App\Http\Controllers\Admin\WhatsAppController::class, 'saveBotMessage'])->name('bot-messages.save');
        Route::post('/macros', [\App\Http\Controllers\Admin\WhatsAppController::class, 'saveMacro'])->name('macros.save');
        Route::delete('/macros/{macro}', [\App\Http\Controllers\Admin\WhatsAppController::class, 'deleteMacro'])->name('macros.delete');
        Route::get('/{conversation}/messages', [\App\Http\Controllers\Admin\WhatsAppController::class, 'messages'])->name('messages');
        Route::get('/{conversation}', [\App\Http\Controllers\Admin\WhatsAppController::class, 'show'])->name('show');
        Route::post('/{conversation}/release', [\App\Http\Controllers\Admin\WhatsAppController::class, 'release'])->name('release');
        Route::post('/{conversation}/pause', [\App\Http\Controllers\Admin\WhatsAppController::class, 'pause'])->name('pause');
    });
});

// Dashboard TV (Opção A: Acesso via query token sem login obrigatório)
Route::get('admin/dashboard-tv', [\App\Http\Controllers\Admin\DashboardTvController::class, 'show'])->name('admin.dashboard-tv');
Route::get('admin/api/v1/dashboard-tv/data', [\App\Http\Controllers\Admin\DashboardTvController::class, 'data'])->name('admin.dashboard-tv.data');

