<?php

use App\Models\CompanyModuleType;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use App\Services\Admin\Helpdesk\HelpdeskService;
use Illuminate\Support\Facades\Cache;


// ──────────────────────────────────────────────────────────────────────────────
// STATUS
// ──────────────────────────────────────────────────────────────────────────────

describe('Status', function () {

    it('getAllStatuses retorna todos os status ordenados por nome', function () {
        $expected = ['Spec Alpha', 'Spec Medio', 'Spec Zebra'];

        Status::factory()->create(['name' => $expected[2]]);
        Status::factory()->create(['name' => $expected[0]]);
        Status::factory()->create(['name' => $expected[1]]);

        $orderedSubset = app(HelpdeskService::class)->getAllStatuses()
            ->pluck('name')
            ->filter(fn (string $name) => in_array($name, $expected, true))
            ->values();

        expect($orderedSubset->all())->toBe($expected);
    });

    it('findStatus retorna status pelo id', function () {
        $status = Status::factory()->create(['name' => 'Aberto']);

        $found = app(HelpdeskService::class)->findStatus($status->id);

        expect($found->name)->toBe('Aberto');
    });

    it('findStatus lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => app(HelpdeskService::class)->findStatus(99999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('createStatus persiste status no banco', function () {
        $status = app(HelpdeskService::class)->createStatus([
            'name'  => 'Em Análise',
            'color' => '#6366f1',
        ]);

        expect($status->name)->toBe('Em Análise')
            ->and($status->color)->toBe('#6366f1');

        $this->assertDatabaseHas('ticketit_statuses', ['name' => 'Em Análise']);
    });

    it('createStatus usa cor padrão quando não informada', function () {
        $status = app(HelpdeskService::class)->createStatus(['name' => 'Novo']);

        expect($status->color)->toBe('#6366f1');
    });

    it('updateStatus altera nome e cor', function () {
        $status = Status::factory()->create(['name' => 'Antigo', 'color' => '#000']);

        $updated = app(HelpdeskService::class)->updateStatus($status->id, [
            'name'  => 'Atualizado',
            'color' => '#ff0000',
        ]);

        expect($updated->name)->toBe('Atualizado')
            ->and($updated->color)->toBe('#ff0000');
    });

    it('deleteStatus remove status sem tickets', function () {
        $status = Status::factory()->create();

        app(HelpdeskService::class)->deleteStatus($status->id);

        $this->assertDatabaseMissing('ticketit_statuses', ['id' => $status->id]);
    });

    it('deleteStatus lança DomainException quando há tickets vinculados', function () {
        $status   = Status::factory()->create();
        $priority = Priority::factory()->create();
        $user     = \App\Models\User::factory()->agent()->create();

        $categoryId = \Illuminate\Support\Facades\DB::table('ticketit_categories')->insertGetId([
            'name'  => 'Cat Teste',
            'color' => '#000000',
        ]);

        \Illuminate\Support\Facades\DB::table('ticketit')->insert([
            'subject'     => 'Ticket de teste',
            'content'     => 'Conteúdo',
            'status_id'   => $status->id,
            'priority_id' => $priority->id,
            'user_id'     => $user->id,
            'agent_id'    => $user->id,
            'category_id' => $categoryId,
            'company_id'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        expect(fn () => app(HelpdeskService::class)->deleteStatus($status->id))
            ->toThrow(\DomainException::class);
    });

});

// ──────────────────────────────────────────────────────────────────────────────
// PRIORITY
// ──────────────────────────────────────────────────────────────────────────────

describe('Priority', function () {

    it('getAllPriorities retorna todas as prioridades ordenadas', function () {
        Priority::factory()->create(['name' => 'Urgente']);
        Priority::factory()->create(['name' => 'Baixa']);

        $priorities = app(HelpdeskService::class)->getAllPriorities();

        expect($priorities->first()->name)->toBe('Baixa')
            ->and($priorities->count())->toBeGreaterThanOrEqual(2);
    });

    it('findPriority lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => app(HelpdeskService::class)->findPriority(99999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('createPriority persiste prioridade no banco', function () {
        $priority = app(HelpdeskService::class)->createPriority([
            'name'  => 'Crítica',
            'color' => '#dc2626',
        ]);

        expect($priority->name)->toBe('Crítica');
        $this->assertDatabaseHas('ticketit_priorities', ['name' => 'Crítica']);
    });

    it('createPriority usa cor padrão quando não informada', function () {
        $priority = app(HelpdeskService::class)->createPriority(['name' => 'Normal']);

        expect($priority->color)->toBe('#6366f1');
    });

    it('updatePriority altera os dados', function () {
        $priority = Priority::factory()->create(['name' => 'Velha', 'color' => '#aaa']);

        $updated = app(HelpdeskService::class)->updatePriority($priority->id, [
            'name'  => 'Nova',
            'color' => '#111',
        ]);

        expect($updated->name)->toBe('Nova');
    });

    it('deletePriority remove prioridade sem tickets', function () {
        $priority = Priority::factory()->create();

        app(HelpdeskService::class)->deletePriority($priority->id);

        $this->assertDatabaseMissing('ticketit_priorities', ['id' => $priority->id]);
    });

    it('deletePriority lança DomainException quando há tickets vinculados', function () {
        $status   = Status::factory()->create();
        $priority = Priority::factory()->create();
        $user     = \App\Models\User::factory()->agent()->create();

        $categoryId = \Illuminate\Support\Facades\DB::table('ticketit_categories')->insertGetId([
            'name'  => 'Cat Teste',
            'color' => '#000000',
        ]);

        \Illuminate\Support\Facades\DB::table('ticketit')->insert([
            'subject'     => 'Ticket de teste',
            'content'     => 'Conteúdo',
            'status_id'   => $status->id,
            'priority_id' => $priority->id,
            'user_id'     => $user->id,
            'agent_id'    => $user->id,
            'category_id' => $categoryId,
            'company_id'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        expect(fn () => app(HelpdeskService::class)->deletePriority($priority->id))
            ->toThrow(\DomainException::class);
    });

});

// ──────────────────────────────────────────────────────────────────────────────
// ORIGIN
// ──────────────────────────────────────────────────────────────────────────────

describe('Origin', function () {

    it('getAllOrigins retorna origens ordenadas por nome', function () {
        Origin::factory()->create(['name' => 'Telegram']);
        Origin::factory()->create(['name' => 'Email']);

        $origins = app(HelpdeskService::class)->getAllOrigins();

        expect($origins->first()->name)->toBe('Email');
    });

    it('createOrigin persiste origem no banco', function () {
        $origin = app(HelpdeskService::class)->createOrigin([
            'name'        => 'WhatsApp',
            'description' => 'Canal WhatsApp',
            'status'      => 1,
        ]);

        expect($origin->name)->toBe('WhatsApp');
        $this->assertDatabaseHas('ticketit_origin', ['name' => 'WhatsApp']);
    });

    it('createOrigin funciona sem description e status', function () {
        $origin = app(HelpdeskService::class)->createOrigin(['name' => 'Presencial']);

        expect($origin->name)->toBe('Presencial')
            ->and($origin->description)->toBeNull();
    });

    it('updateOrigin altera os dados', function () {
        $origin = Origin::factory()->create(['name' => 'Antigo']);

        $updated = app(HelpdeskService::class)->updateOrigin($origin->id, ['name' => 'Novo']);

        expect($updated->name)->toBe('Novo');
    });

    it('deleteOrigin remove origem sem tickets', function () {
        $origin = Origin::factory()->create();

        app(HelpdeskService::class)->deleteOrigin($origin->id);

        $this->assertDatabaseMissing('ticketit_origin', ['id' => $origin->id]);
    });

    it('deleteOrigin lança DomainException quando há tickets vinculados', function () {
        $status   = Status::factory()->create();
        $priority = Priority::factory()->create();
        $origin   = Origin::factory()->create();
        $user     = \App\Models\User::factory()->agent()->create();

        $categoryId = \Illuminate\Support\Facades\DB::table('ticketit_categories')->insertGetId([
            'name'  => 'Cat Teste',
            'color' => '#000000',
        ]);

        \Illuminate\Support\Facades\DB::table('ticketit')->insert([
            'subject'     => 'Ticket de teste',
            'content'     => 'Conteúdo',
            'status_id'   => $status->id,
            'priority_id' => $priority->id,
            'origin_id'   => $origin->id,
            'user_id'     => $user->id,
            'agent_id'    => $user->id,
            'category_id' => $categoryId,
            'company_id'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        expect(fn () => app(HelpdeskService::class)->deleteOrigin($origin->id))
            ->toThrow(\DomainException::class);
    });

});

// ──────────────────────────────────────────────────────────────────────────────
// COMPANY MODULE TYPE
// ──────────────────────────────────────────────────────────────────────────────

describe('CompanyModuleType', function () {

    it('getAllModuleTypes retorna módulos ordenados por sort_order e nome', function () {
        CompanyModuleType::factory()->create(['name' => 'Zebra', 'sort_order' => 2]);
        CompanyModuleType::factory()->create(['name' => 'Alpha', 'sort_order' => 1]);

        $modules = app(HelpdeskService::class)->getAllModuleTypes();

        expect($modules->first()->name)->toBe('Alpha');
    });

    it('createModuleType persiste e gera slug automaticamente', function () {
        $module = app(HelpdeskService::class)->createModuleType([
            'name'       => 'Folha de Pagamento',
            'is_active'  => true,
            'sort_order' => 0,
        ]);

        expect($module->name)->toBe('Folha de Pagamento')
            ->and($module->slug)->toBe('folha_de_pagamento');
    });

    it('updateModuleType altera os dados', function () {
        $module = CompanyModuleType::factory()->create(['name' => 'Antigo']);

        $updated = app(HelpdeskService::class)->updateModuleType($module->id, [
            'name'       => 'Novo Nome',
            'is_active'  => true,
            'sort_order' => 3,
        ]);

        expect($updated->name)->toBe('Novo Nome')
            ->and($updated->sort_order)->toBe(3);
    });

    it('deleteModuleType remove o módulo', function () {
        $module = CompanyModuleType::factory()->create();

        app(HelpdeskService::class)->deleteModuleType($module->id);

        $this->assertDatabaseMissing('company_module_types', ['id' => $module->id]);
    });

    it('findModuleType lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => app(HelpdeskService::class)->findModuleType(99999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ──────────────────────────────────────────────────────────────────────────────
// SETTINGS / DASHBOARD
// ──────────────────────────────────────────────────────────────────────────────

describe('Settings', function () {

    it('getSettingsSections retorna estrutura com statuses e priorities', function () {
        Status::factory()->create(['name' => 'Aberto']);
        Priority::factory()->create(['name' => 'Alta']);

        $sections = app(HelpdeskService::class)->getSettingsSections();

        expect($sections)->toHaveKeys(['statuses', 'priorities', 'settings']);
        expect($sections['statuses'])->not->toBeEmpty();
    });

    it('updateSetting persiste configuração no banco', function () {
        app(HelpdeskService::class)->updateSetting('test_key', 'test_value');

        $this->assertDatabaseHas('ticketit_settings', [
            'slug'  => 'test_key',
            'value' => 'test_value',
        ]);
    });

    it('updateSetting atualiza configuração existente', function () {
        app(HelpdeskService::class)->updateSetting('my_key', 'first');
        app(HelpdeskService::class)->updateSetting('my_key', 'second');

        $this->assertDatabaseHas('ticketit_settings', ['slug' => 'my_key', 'value' => 'second']);
        expect(\App\Models\Ticket\Setting::where('slug', 'my_key')->count())->toBe(1);
    });

});

// ──────────────────────────────────────────────────────────────────────────────
// DASHBOARD METRICS
// ──────────────────────────────────────────────────────────────────────────────

it('getDashboardMetrics retorna contadores de tickets e agrupa por status', function () {
    Cache::flush();

    // Status base
    $openStatus   = Status::factory()->create(['name' => 'Aberto']);
    $closedStatus = Status::factory()->terminal()->create(['name' => 'Fechado']);

    $priority = Priority::factory()->create();
    $agent    = \App\Models\User::factory()->agent()->create();

    $categoryId = DB::table('ticketit_categories')->insertGetId([
        'name'  => 'Categoria Teste',
        'color' => '#000000',
    ]);

    // Tickets: 2 abertos, 1 fechado hoje
    DB::table('ticketit')->insert([
        [
            'subject'     => 'Ticket 1',
            'content'     => 'Conteúdo 1',
            'status_id'   => $openStatus->id,
            'priority_id' => $priority->id,
            'user_id'     => $agent->id,
            'agent_id'    => $agent->id,
            'category_id' => $categoryId,
            'company_id'  => 1,
            'created_at'  => now()->subDay(),
            'updated_at'  => now()->subDay(),
        ],
        [
            'subject'     => 'Ticket 2',
            'content'     => 'Conteúdo 2',
            'status_id'   => $openStatus->id,
            'priority_id' => $priority->id,
            'user_id'     => $agent->id,
            'agent_id'    => $agent->id,
            'category_id' => $categoryId,
            'company_id'  => 1,
            'created_at'  => now()->subHours(2),
            'updated_at'  => now()->subHours(1),
        ],
        [
            'subject'     => 'Ticket 3',
            'content'     => 'Conteúdo 3',
            'status_id'   => $closedStatus->id,
            'priority_id' => $priority->id,
            'user_id'     => $agent->id,
            'agent_id'    => $agent->id,
            'category_id' => $categoryId,
            'company_id'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ],
    ]);

    $metrics = app(HelpdeskService::class)->getDashboardMetrics();

    expect($metrics['total'])->toBe(3)
        ->and($metrics['open'])->toBe(2)
        ->and($metrics['closed'])->toBe(1)
        ->and($metrics['today'])->toBeGreaterThanOrEqual(1)
        ->and($metrics['byStatus']->first()['name'])->toBe('Aberto')
        ->and($metrics['topCategories'])->toBeIterable();

    // Chamada subsequente deve vir do cache (sem queries extras)
    Cache::shouldReceive('remember')->once()->andReturn($metrics);
    app(HelpdeskService::class)->getDashboardMetrics();
});
