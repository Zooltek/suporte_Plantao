<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyModuleType;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Support\Tickets\TicketStatusCatalog;

// ── Helpers de Arrange ────────────────────────────────────────────────────────

/**
 * Cria a estrutura mínima necessária para submeter o formulário de chamado:
 * empresa, origem, prioridade, categoria-pai e sub-categoria.
 *
 * Retorna um array de dados prontos para POST em agent.ticket.store.
 */
function makeTicketPayload(array $overrides = []): array
{
    $company  = Company::factory()->create(['is_active' => true]);
    $origin   = Origin::factory()->create();
    Priority::firstOrCreate(['id' => 1], ['name' => 'Normal', 'color' => '#6b7280']);

    $parent = Category::factory()->create(['parent_id' => 0]);
    $sub    = Category::factory()->create(['parent_id' => $parent->category_id]);

    return array_merge([
        'company_id'      => $company->id,
        'origin_id'       => $origin->id,
        'status_id'       => 1,           // será resolvido pela regra de negócio
        'category_id'     => $parent->category_id,
        'sub_category_id' => $sub->category_id,
        'contact'         => 'CONTATO TESTE',
    ], $overrides);
}

function syncTicketStatusFromCatalog(int $statusId): Status
{
    $definition = TicketStatusCatalog::findById($statusId);

    if ($definition === null) {
        throw new InvalidArgumentException("Status canônico {$statusId} não encontrado.");
    }

    return Status::query()->updateOrCreate(
        ['id' => $definition['id']],
        [
            'name' => $definition['name'],
            'color' => $definition['color'],
            'is_terminal' => $definition['is_terminal'],
            'requires_schedule' => $definition['requires_schedule'],
            'requires_solution' => $definition['requires_solution'],
            'requires_agent' => $definition['requires_agent'],
        ]
    );
}

// ── Fluxo 2 — Novo Ticket e regras operacionais ───────────────────────────────

describe('Fluxo 2 — Novo Ticket e regras operacionais', function () {

    it('tela de novo ticket exibe os campos canônicos do formulário atual', function () {
        actingAsAgent();

        $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->assertSee('Empresa/Cliente')
            ->assertSee('Técnico/Suporte')
            ->assertSee('Observação Interna')
            ->assertSee('Módulo Contratado');
    });

    it('novo ticket salvo sem agente recebe status Pendente', function () {
        // Arrange
        actingAsAgent();
        $statusQualquer = Status::factory()->create(['requires_agent' => false]);
        $payload = makeTicketPayload(['status_id' => $statusQualquer->id]);

        // Act
        $this->post(route('agent.ticket.store'), $payload)->assertRedirect();

        // Assert — sem agente -> STATUS_PENDING_ID
        $this->assertDatabaseHas('ticketit', [
            'contact'   => 'CONTATO TESTE',
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id'  => null,
        ]);
    });

    it('"Em andamento" nao aciona o fluxo de visita técnica', function () {
        // O comportamento relevante é: um status sem requires_schedule
        // não dispara a criação de agendamento.

        $emAndamento = Status::factory()->emAndamento()->create();

        expect(Status::requiresSchedule($emAndamento->id))->toBeFalse()
            ->and(Status::isTerminal($emAndamento->id))->toBeFalse();

        $needsSchedule = Status::requiresSchedule($emAndamento->id) && ! Status::isTerminal($emAndamento->id);
        expect($needsSchedule)->toBeFalse();

        $totalAgendaveis = Status::where('requires_schedule', true)->count();
        expect($totalAgendaveis)->toBe(1);

        $nomeDoUnico = Status::where('requires_schedule', true)->value('name');
        expect($nomeDoUnico)->toBe('Visita Técnica');
    });

    it('novo ticket com agente e status nao terminal é salvo como Em Andamento', function () {
        // Arrange
        actingAsAgent();
        $agente      = User::factory()->agent()->create();
        $statusNormal = Status::factory()->emAndamento()->create();
        $payload      = makeTicketPayload([
            'status_id' => $statusNormal->id,
            'agent_id'  => $agente->id,
            'trouble'   => 'Problema relatado',
        ]);

        // Act
        $this->post(route('agent.ticket.store'), $payload)->assertRedirect();

        // Assert — regra de negócio força STATUS_IN_PROGRESS_ID
        $this->assertDatabaseHas('ticketit', [
            'contact'   => 'CONTATO TESTE',
            'status_id' => Ticket::STATUS_IN_PROGRESS_ID,
            'agent_id'  => $agente->id,
        ]);
    });

    it('status terminal permanece preservado com agente no novo ticket', function () {
        $status = syncTicketStatusFromCatalog(TicketStatusCatalog::UNRESOLVED_ID);

        actingAsAgent();
        $agente  = User::factory()->agent()->create();
        $payload = makeTicketPayload([
            'status_id' => $status->id,
            'agent_id'  => $agente->id,
            'trouble'   => 'Problema sem resolução',
        ]);

        // Act
        $this->post(route('agent.ticket.store'), $payload)->assertRedirect();

        // Assert — is_terminal=true -> status preservado
        $this->assertDatabaseHas('ticketit', [
            'contact'   => 'CONTATO TESTE',
            'status_id' => $status->id,
        ]);
    });

    it('Visita Técnica redireciona para a criação de agendamento', function () {
        // Arrange
        $visitaTecnica = Status::where('requires_schedule', true)->first()
            ?? Status::factory()->visitaTecnica()->create();

        actingAsAgent();
        $agente  = User::factory()->agent()->create();
        $payload = makeTicketPayload([
            'status_id' => $visitaTecnica->id,
            'agent_id'  => $agente->id,
            'trouble'   => 'Precisa de visita presencial',
        ]);

        // Act
        $response = $this->post(route('agent.ticket.store'), $payload);

        // Assert — redirect to agent.schedules.create
        $response->assertRedirect();
        $this->assertStringContainsString(
            route('agent.schedules.create'),
            $response->headers->get('Location')
        );
    });

    it('"Nao Resolvido" nao aciona o fluxo de agendamento', function () {
        $status = syncTicketStatusFromCatalog(TicketStatusCatalog::UNRESOLVED_ID);

        // Assert direto na regra de negócio
        expect(Status::requiresSchedule($status->id))->toBeFalse();
        expect(Status::isTerminal($status->id))->toBeTrue();

        $needsSchedule = Status::requiresSchedule($status->id) && ! Status::isTerminal($status->id);
        expect($needsSchedule)->toBeFalse();
    });

    it('formulário de novo ticket carrega moduleTypes por empresa via eager load', function () {
        // Arrange
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $module  = CompanyModuleType::factory()->create(['name' => 'TEF Online Homolog']);
        $company->moduleTypes()->attach($module->id);

        // Act
        $companies = $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->viewData('companies');

        // Assert
        $target = $companies->find($company->id);
        expect($target->relationLoaded('moduleTypes'))->toBeTrue()
            ->and($target->moduleTypes->pluck('name')->toArray())->toContain('TEF Online Homolog');
    });

    it('payload da view inclui module_types por empresa', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $module  = CompanyModuleType::factory()->create(['name' => 'CRM Integrado Homolog']);
        $company->moduleTypes()->attach($module->id);

        $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->assertSee('module_types')
            ->assertSee('module_id');
    });

});

// ── Fluxo 3 — Meus Chamados e Todos os Chamados ───────────────────────────────

describe('Fluxo 3 — Meus Chamados e Todos os Chamados', function () {

    it('agente acessa Meus Chamados por padrao', function () {
        actingAsAgent();

        $response = $this->get(route('agent.ticket.index'));

        $response->assertOk()
            ->assertViewIs('agent.ticket.index')
            ->assertSee('Meus Chamados')
            ->assertSee('Em Meus Chamados o responsável fica fixo no usuário logado.');

        expect($response->viewData('isMineView'))->toBeTrue();
    });

    it('admin acessa Todos os Chamados e pode filtrar por agente', function () {
        // Arrange
        actingAsAdmin();
        $agente = User::factory()->agent()->create();
        $outro  = User::factory()->agent()->create();

        $ticketDoAgente = Ticket::factory()->create(['agent_id' => $agente->id]);
        $ticketDoOutro  = Ticket::factory()->create(['agent_id' => $outro->id]);

        // Act — parâmetro correto conforme TicketIndexRequest é 'agent' (não 'agent_id')
        $response = $this->get(route('agent.ticket.index', ['agent' => $agente->id]));
        $response->assertOk()
            ->assertSee('Todos os Chamados')
            ->assertSee('Agente');

        $tickets = $response->viewData('tickets');

        // Assert — o ticket do agente alvo está presente; o do outro não
        $ids = $tickets->pluck('id')->toArray();
        expect($ids)->toContain($ticketDoAgente->id)
            ->and($ids)->not->toContain($ticketDoOutro->id);
        expect($response->viewData('isMineView'))->toBeFalse();
    });

    it('listagem disponibiliza os dados auxiliares de filtro esperados pela interface', function () {
        actingAsAgent();

        $response = $this->get(route('agent.ticket.index'))->assertOk();

        $response->assertViewHasAll(['tickets', 'agents', 'statuses']);
    });

});
