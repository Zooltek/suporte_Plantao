<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Repositories\TicketQueryRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function tqr_ticket(array $attrs = []): Ticket
{
    return Ticket::factory()->create($attrs);
}

function tqr_repo(): TicketQueryRepository
{
    return new TicketQueryRepository;
}

function tqr_category(string $name, array $attrs = []): Category
{
    return Category::factory()
        ->withDescription($name)
        ->create($attrs)
        ->refresh();
}

// ─── getAllStatuses ────────────────────────────────────────────────────────────

describe('TicketQueryRepository — getAllStatuses', function () {

    it('retorna todos os status ordenados por nome', function () {
        Status::factory()->create(['name' => 'Zebra']);
        Status::factory()->create(['name' => 'Alpha']);

        $result = tqr_repo()->getAllStatuses();

        $names = $result->pluck('name')->toArray();
        expect($names)->toBe(collect($names)->sort()->values()->toArray());
    });

    it('retorna uma Collection não vazia quando há status cadastrados', function () {
        Status::factory()->create(['name' => 'Presente']);

        $result = tqr_repo()->getAllStatuses();

        expect($result->count())->toBeGreaterThan(0);
    });

});

// ─── getCategoryFilterOptions ────────────────────────────────────────────────

describe('TicketQueryRepository — getCategoryFilterOptions', function () {

    it('retorna apenas categorias raiz (parent_id = 0)', function () {
        $root = tqr_category('Raiz Financeira', ['parent_id' => 0]);
        $child = tqr_category('Filha Técnica', ['parent_id' => $root->category_id]);

        $result = tqr_repo()->getCategoryFilterOptions();
        $ids = $result->pluck('category_id')->toArray();

        expect($ids)->toContain($root->category_id)
            ->and($ids)->not->toContain($child->category_id);
    });

    it('retorna categorias ordenadas pelo nome visível ao usuário', function () {
        tqr_category('Zebra Cat', ['parent_id' => 0]);
        tqr_category('Alpha Cat', ['parent_id' => 0]);

        $result = tqr_repo()->getCategoryFilterOptions();
        $labels = $result->map(fn (Category $category) => $category->display_name)->toArray();

        expect($labels)->toBe(collect($labels)->sort()->values()->toArray());
    });

    it('entrega a descrição eager-loaded para a view', function () {
        $category = tqr_category('ERP Financeiro', ['parent_id' => 0]);

        $result = tqr_repo()->getCategoryFilterOptions();
        $loadedCategory = $result->firstWhere('category_id', $category->category_id);

        expect($loadedCategory)->not->toBeNull()
            ->and($loadedCategory->relationLoaded('description'))->toBeTrue()
            ->and($loadedCategory->display_name)->toBe('ERP Financeiro');
    });

});

// ─── getAllCompanies ───────────────────────────────────────────────────────────

describe('TicketQueryRepository — getAllCompanies', function () {

    it('retorna empresas ordenadas por trade_name', function () {
        Company::factory()->create(['trade_name' => 'Zebra Ltda']);
        Company::factory()->create(['trade_name' => 'Alpha SA']);

        $result = tqr_repo()->getAllCompanies();
        $names = $result->pluck('trade_name')->toArray();

        expect($names)->toBe(collect($names)->sort()->values()->toArray());
    });

    it('inclui as empresas criadas no resultado', function () {
        $company = Company::factory()->create(['trade_name' => 'Nova Empresa']);

        $result = tqr_repo()->getAllCompanies();
        $ids = $result->pluck('id')->toArray();

        expect($ids)->toContain($company->id);
    });

});

// ─── getActiveAgents ──────────────────────────────────────────────────────────

describe('TicketQueryRepository — getActiveAgents', function () {

    it('retorna apenas usuários com ticketit_agent = 1', function () {
        $agent = User::factory()->agent()->create(['name' => 'Agente Ativo']);
        $nonAgent = User::factory()->create(['name' => 'Usuário Comum', 'ticketit_agent' => 0]);

        $result = tqr_repo()->getActiveAgents();
        $ids = $result->pluck('id')->toArray();

        expect($ids)->toContain($agent->id)
            ->and($ids)->not->toContain($nonAgent->id);
    });

    it('retorna agentes ordenados por nome', function () {
        User::factory()->agent()->create(['name' => 'Zebra Agente']);
        User::factory()->agent()->create(['name' => 'Alpha Agente']);

        $result = tqr_repo()->getActiveAgents();
        $names = $result->pluck('name')->toArray();

        expect($names)->toBe(collect($names)->sort()->values()->toArray());
    });

});

// ─── paginateTickets ──────────────────────────────────────────────────────────

describe('TicketQueryRepository — paginateTickets', function () {

    it('retorna LengthAwarePaginator', function () {
        $result = tqr_repo()->paginateTickets([], null, null, false, 0, 15);

        expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    });

    it('sem agentScope retorna todos os tickets', function () {
        $a1 = User::factory()->agent()->create();
        $a2 = User::factory()->agent()->create();

        tqr_ticket(['agent_id' => $a1->id, 'subject' => 'T de A1']);
        tqr_ticket(['agent_id' => $a2->id, 'subject' => 'T de A2']);

        $result = tqr_repo()->paginateTickets([], null, null, false, 0, 15);

        $subjects = $result->pluck('subject')->toArray();
        expect($subjects)->toContain('T de A1')->and($subjects)->toContain('T de A2');
    });

    it('agentScope restringe ao agent_id informado', function () {
        $a1 = User::factory()->agent()->create();
        $a2 = User::factory()->agent()->create();

        tqr_ticket(['agent_id' => $a1->id, 'subject' => 'Meu Ticket']);
        tqr_ticket(['agent_id' => $a2->id, 'subject' => 'Outro Ticket']);

        $result = tqr_repo()->paginateTickets([], $a1->id, null, false, 0, 15);

        $subjects = $result->pluck('subject')->toArray();
        expect($subjects)->toContain('Meu Ticket')->and($subjects)->not->toContain('Outro Ticket');
    });

    it('filtro status filtra por status_id', function () {
        $status = Status::factory()->create();
        $agent = User::factory()->agent()->create();

        tqr_ticket(['agent_id' => $agent->id, 'status_id' => $status->id, 'subject' => 'Com Status']);
        tqr_ticket(['agent_id' => $agent->id, 'status_id' => 9999,         'subject' => 'Sem Status']);

        $result = tqr_repo()->paginateTickets(['status' => $status->id], null, null, false, 0, 15);

        $subjects = $result->pluck('subject')->toArray();
        expect($subjects)->toContain('Com Status')->and($subjects)->not->toContain('Sem Status');
    });

    it('filtro q busca por subject', function () {
        $agent = User::factory()->agent()->create();

        tqr_ticket(['agent_id' => $agent->id, 'subject' => 'Impressora quebrada']);
        tqr_ticket(['agent_id' => $agent->id, 'subject' => 'Problema de rede']);

        $result = tqr_repo()->paginateTickets(['q' => 'Impressora'], null, null, false, 0, 15);

        $subjects = $result->pluck('subject')->toArray();
        expect($subjects)->toContain('Impressora quebrada')->and($subjects)->not->toContain('Problema de rede');
    });

    it('filtro q busca por contact', function () {
        $agent = User::factory()->agent()->create();

        tqr_ticket(['agent_id' => $agent->id, 'subject' => 'Chamado X', 'contact' => 'MARCELO SILVA']);
        tqr_ticket(['agent_id' => $agent->id, 'subject' => 'Chamado Y', 'contact' => 'JOAO PEREIRA']);

        $result = tqr_repo()->paginateTickets(['q' => 'MARCELO'], null, null, false, 0, 15);

        $subjects = $result->pluck('subject')->toArray();
        expect($subjects)->toContain('Chamado X')->and($subjects)->not->toContain('Chamado Y');
    });

    it('filtro company filtra por company_id', function () {
        $company = Company::factory()->create();
        $agent = User::factory()->agent()->create();

        tqr_ticket(['agent_id' => $agent->id, 'company_id' => $company->id, 'subject' => 'Da Empresa']);
        tqr_ticket(['agent_id' => $agent->id, 'company_id' => 9999,          'subject' => 'Outra Empresa']);

        $result = tqr_repo()->paginateTickets(['company' => $company->id], null, null, false, 0, 15);

        $subjects = $result->pluck('subject')->toArray();
        expect($subjects)->toContain('Da Empresa')->and($subjects)->not->toContain('Outra Empresa');
    });

    it('filtro agent filtra por agent_id', function () {
        $a1 = User::factory()->agent()->create();
        $a2 = User::factory()->agent()->create();

        tqr_ticket(['agent_id' => $a1->id, 'subject' => 'Ticket Agente 1']);
        tqr_ticket(['agent_id' => $a2->id, 'subject' => 'Ticket Agente 2']);

        $result = tqr_repo()->paginateTickets(['agent' => $a1->id], null, null, false, 0, 15);

        $subjects = $result->pluck('subject')->toArray();
        expect($subjects)->toContain('Ticket Agente 1')->and($subjects)->not->toContain('Ticket Agente 2');
    });

    it('order=1 ordena por updated_at desc', function () {
        $agent = User::factory()->agent()->create();

        tqr_ticket([
            'agent_id' => $agent->id,
            'subject' => 'Atualizado Hoje',
            'created_at' => now()->subDays(10),
            'updated_at' => now(),
        ]);
        tqr_ticket([
            'agent_id' => $agent->id,
            'subject' => 'Atualizado Ontem',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDay(),
        ]);

        $result = tqr_repo()->paginateTickets([], null, null, false, 1, 15);
        $subjects = $result->pluck('subject')->toArray();

        expect(array_search('Atualizado Hoje', $subjects))
            ->toBeLessThan(array_search('Atualizado Ontem', $subjects));
    });

    it('order=2 ordena por created_at desc e prioriza tickets pendentes antes de finalizados', function () {
        $agent = User::factory()->agent()->create();
        $statusPendente = Status::factory()->create(['name' => 'Em Aberto', 'is_terminal' => false]);
        $statusFinalizado = Status::factory()->create(['name' => 'Resolvido', 'is_terminal' => true]);

        tqr_ticket([
            'agent_id' => $agent->id,
            'status_id' => $statusFinalizado->id,
            'subject' => 'Finalizado Recente',
            'created_at' => now()->subHour(),
            'updated_at' => now(),
        ]);
        tqr_ticket([
            'agent_id' => $agent->id,
            'status_id' => $statusPendente->id,
            'subject' => 'Pendente Antigo',
            'created_at' => now()->subHours(5),
            'updated_at' => now(),
        ]);
        tqr_ticket([
            'agent_id' => $agent->id,
            'status_id' => $statusPendente->id,
            'subject' => 'Pendente Recente',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now(),
        ]);
        tqr_ticket([
            'agent_id' => $agent->id,
            'status_id' => $statusFinalizado->id,
            'subject' => 'Finalizado Antigo',
            'created_at' => now()->subHours(10),
            'updated_at' => now(),
        ]);

        $result = tqr_repo()->paginateTickets([], null, null, false, 2, 15);
        $subjects = $result->pluck('subject')->toArray();

        expect(array_search('Pendente Recente', $subjects))
            ->toBeLessThan(array_search('Pendente Antigo', $subjects))
            ->and(array_search('Pendente Antigo', $subjects))
            ->toBeLessThan(array_search('Finalizado Recente', $subjects))
            ->and(array_search('Finalizado Recente', $subjects))
            ->toBeLessThan(array_search('Finalizado Antigo', $subjects));
    });

    it('filtro date_from e date_to filtra chamados pelo período de criação', function () {
        $agent = User::factory()->agent()->create();

        tqr_ticket([
            'agent_id' => $agent->id,
            'subject' => 'Ticket de Hoje',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        tqr_ticket([
            'agent_id' => $agent->id,
            'subject' => 'Ticket Semana Passada',
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(7),
        ]);
        tqr_ticket([
            'agent_id' => $agent->id,
            'subject' => 'Ticket Mês Passado',
            'created_at' => now()->subDays(35),
            'updated_at' => now()->subDays(35),
        ]);

        $result = tqr_repo()->paginateTickets([
            'date_from' => now()->subDays(10)->toDateString(),
            'date_to' => now()->subDays(2)->toDateString(),
        ], null, null, false, 0, 15);

        $subjects = $result->pluck('subject')->toArray();

        expect($subjects)->toContain('Ticket Semana Passada')
            ->and($subjects)->not->toContain('Ticket de Hoje')
            ->and($subjects)->not->toContain('Ticket Mês Passado');
    });

    it('order=0 (padrão) ordena ticket urgente antes de baixa prioridade', function () {
        $agent = User::factory()->agent()->create();
        $catUrgente = Category::factory()->create(['parent_id' => 0, 'priority' => 'urgent']);
        $catBaixa = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        tqr_ticket([
            'agent_id' => $agent->id,
            'subject' => 'Ticket Urgente',
            'category_id' => $catUrgente->category_id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        tqr_ticket([
            'agent_id' => $agent->id,
            'subject' => 'Ticket Baixa',
            'category_id' => $catBaixa->category_id,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $result = tqr_repo()->paginateTickets([], null, null, false, 0, 15);
        $subjects = $result->pluck('subject')->toArray();

        expect(array_search('Ticket Urgente', $subjects))
            ->toBeLessThan(array_search('Ticket Baixa', $subjects));
    });

    it('respeita o perPage informado', function () {
        $agent = User::factory()->agent()->create();

        for ($i = 1; $i <= 10; $i++) {
            tqr_ticket(['agent_id' => $agent->id, 'subject' => "Ticket #{$i}"]);
        }

        $result = tqr_repo()->paginateTickets([], null, null, false, 0, 5);

        expect($result->perPage())->toBe(5)
            ->and($result->count())->toBe(5);
    });

});
