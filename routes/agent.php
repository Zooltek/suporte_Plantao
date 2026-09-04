<?php

use App\Http\Controllers\Agent\AccountController;
use App\Http\Controllers\Agent\CategoryController;
use App\Http\Controllers\Agent\CommentsController;
use App\Http\Controllers\Agent\CompanyController;
use App\Http\Controllers\Agent\DashboardController;
use App\Http\Controllers\Agent\ImplantacaoController;
use App\Http\Controllers\Agent\KnowledgeController;
use App\Http\Controllers\Agent\MonitorController;
use App\Http\Controllers\Agent\RecordController;
use App\Http\Controllers\Agent\ReportController;
use App\Http\Controllers\Agent\ScheduleController;
use App\Http\Controllers\Agent\TicketsController;
use App\Http\Controllers\Agent\WhatsAppConversationController;
use App\Http\Controllers\Helpdesk\CategoryController as HelpDeskCategoryController;
use Illuminate\Support\Facades\Route;

// Módulo Agent: prefix 'support', grupo de nomes 'agent.'
Route::middleware(['web', 'auth:admin', 'agent', 'password.changed'])
    ->prefix('support')
    ->name('agent.')
    ->group(function () {

        // Dashboard principal
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('helper', [DashboardController::class, 'helper'])->name('helper');
        Route::get('calendar/condensed', [DashboardController::class, 'calendarCondensed'])->name('calendar.condensed');

        // Helpdesk category childs
        Route::post('category/childs/{id}', [HelpDeskCategoryController::class, 'getChilds']);
        Route::post('category/childs/new/{id}', [HelpDeskCategoryController::class, 'getChildsNew']);

        // Clientes / Empresas
        Route::get('customer.search', [CompanyController::class, 'search'])->name('customers.search');
        Route::get('company/{id}/history', [CompanyController::class, 'history'])->name('company.history');

        // Tickets
        Route::get('tickets/totalizadores', [ReportController::class, 'generate'])->name('ticket.totalizadores');
        Route::get('tickets/{id}/attendances-partial', [TicketsController::class, 'attendancesPartial'])->name('ticket.attendances-partial');
        Route::get('tickets/attachments/{attachment}/download', [\App\Http\Controllers\API\V1\Tickets\AttachmentController::class, 'show'])->name('tickets.attachments.download');
        Route::post('tickets/{id}/capture', [TicketsController::class, 'capture'])->name('ticket.capture');
        Route::post('tickets/{ticket}/release', [TicketsController::class, 'release'])->name('ticket.release');
        Route::patch('tickets/{ticket}/quick-update', [TicketsController::class, 'quickUpdate'])->name('ticket.quick-update');
        Route::post('tickets/{ticket}/whatsapp/start', [WhatsAppConversationController::class, 'start'])->name('ticket.whatsapp.start');
        Route::get('tickets/{ticket}/whatsapp/messages', [WhatsAppConversationController::class, 'messages'])->name('ticket.whatsapp.messages');
        Route::post('tickets/{ticket}/whatsapp/messages', [WhatsAppConversationController::class, 'store'])->name('ticket.whatsapp.messages.store');
        Route::put('tickets/{ticket}/whatsapp/messages/{message}', [WhatsAppConversationController::class, 'update'])->name('ticket.whatsapp.messages.update');
        Route::delete('tickets/{ticket}/whatsapp/messages/{message}', [WhatsAppConversationController::class, 'destroy'])->name('ticket.whatsapp.messages.destroy');
        Route::get('tickets/{ticket}/whatsapp/messages/{message}/download', [WhatsAppConversationController::class, 'download'])->name('ticket.whatsapp.messages.download');
        Route::post('whatsapp/{conversation}/release', [WhatsAppConversationController::class, 'release'])->name('whatsapp.release');
        Route::post('whatsapp/{conversation}/pause', [WhatsAppConversationController::class, 'pause'])->name('whatsapp.pause');
        Route::resource('tickets', TicketsController::class)->names([
            'index' => 'ticket.index',
            'create' => 'ticket.create',
            'show' => 'ticket.show',
            'edit' => 'ticket.edit',
            'store' => 'ticket.store',
            'update' => 'ticket.update',
            'destroy' => 'ticket.destroy',
        ]);

        // Comentários e ações do ticket (rotas nativas — substitui pacote legado)
        Route::post('tickets/{ticket}/comments', [CommentsController::class, 'store'])->name('ticket.comment.store');
        Route::post('tickets/{ticket}/close', [TicketsController::class, 'close'])->name('ticket.close');

        Route::post('report/generate', [ReportController::class, 'generate'])->name('report.generate');
        Route::get('report/implementation-clients', [ReportController::class, 'implementationClients'])->name('report.implementation-clients');
        Route::get('report/by-department', [ReportController::class, 'byDepartment'])->name('report.by-department');

        // Endpoint JSON de filhos de categoria (usado pelo formulário de ticket)
        Route::get('settings/categories/{id}/children', [CategoryController::class, 'getChildren'])->name('settings.categories.children');
        Route::get('settings/categories/{id}/articles', [CategoryController::class, 'getArticles'])->name('settings.categories.articles');

        // Monitor
        Route::get('monitor', [MonitorController::class, 'index'])->name('monitor');

        // Gerenciamento de Clientes (Empresas)
        Route::get('companies', [CompanyController::class, 'manageIndex'])->name('companies.manage.index');
        Route::get('companies/search', [CompanyController::class, 'manageSearch'])->name('companies.manage.search');
        Route::get('companies/create', [CompanyController::class, 'manageCreate'])->name('companies.manage.create');
        Route::post('companies', [CompanyController::class, 'manageStore'])->name('companies.manage.store');
        Route::get('companies/{company}/edit', [CompanyController::class, 'manageEdit'])->name('companies.manage.edit');
        Route::put('companies/{company}', [CompanyController::class, 'manageUpdate'])->name('companies.manage.update');
        Route::delete('companies/{company}', [CompanyController::class, 'manageDestroy'])->name('companies.manage.destroy');
        Route::patch('companies/{company}/toggle-active', [CompanyController::class, 'toggleActive'])->name('companies.toggle-active');
        Route::patch('companies/{company}/toggle-ecommerce', [CompanyController::class, 'toggleEcommerce'])->name('companies.toggle-ecommerce');
        Route::patch('companies/{company}/toggle-crm', [CompanyController::class, 'toggleCrm'])->name('companies.toggle-crm');
        Route::patch('companies/{company}/toggle-tef', [CompanyController::class, 'toggleTef'])->name('companies.toggle-tef');
        Route::get('api/v1/companies/search', [CompanyController::class, 'apiSearch'])->name('api.v1.companies.search');

        // EasyWiki — Base de Conhecimento
        Route::post('knowledge/media-upload', [KnowledgeController::class, 'uploadMedia'])->name('knowledge.media-upload');
        Route::post('knowledge/notion/settings', [KnowledgeController::class, 'saveNotionSettings'])->name('knowledge.notion.settings');
        Route::post('knowledge/notion/test', [KnowledgeController::class, 'testNotionConnection'])->name('knowledge.notion.test');
        Route::get('knowledge/notion/{pageId}', [KnowledgeController::class, 'showNotion'])->name('knowledge.notion.show');
        Route::resource('knowledge', KnowledgeController::class)
            ->names([
                'index' => 'knowledge.index',
                'create' => 'knowledge.create',
                'store' => 'knowledge.store',
                'show' => 'knowledge.show',
                'edit' => 'knowledge.edit',
                'update' => 'knowledge.update',
                'destroy' => 'knowledge.destroy',
            ]);

        // Implantação — módulo dedicado
        Route::prefix('implantacao')->name('implantacao.')->group(function () {
            Route::get('/', [ImplantacaoController::class, 'index'])->name('index');
            Route::get('/agendamentos', [ImplantacaoController::class, 'schedules'])->name('schedules');
        });

        // Minha Conta
        Route::get('account', [AccountController::class, 'index'])->name('account');
        Route::put('account', [AccountController::class, 'update'])->name('account.update');

        // Agendamentos e Registros
        Route::resource('schedules', ScheduleController::class);
        Route::post('schedules/{schedule}/confirm', [ScheduleController::class, 'confirm'])->name('schedules.confirm');
        Route::post('schedules/{schedule}/confirm-own', [ScheduleController::class, 'confirmOwn'])->name('schedules.confirm-own');
        Route::post('schedules/{schedule}/cancel', [ScheduleController::class, 'cancel'])->name('schedules.cancel');
        Route::post('schedules/{schedule}/finalize', [ScheduleController::class, 'finalize'])->name('schedules.finalize');
        Route::get('schedules/{schedule}/record/{record}/print', [RecordController::class, 'print'])->name('record.print');
        Route::resource('schedules/{schedule}/record', RecordController::class);
    });
