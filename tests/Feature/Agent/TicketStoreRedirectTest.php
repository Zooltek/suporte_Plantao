<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Support\Tickets\TicketStatusCatalog;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;
use Illuminate\Support\Facades\DB;

// ─── Helpers locais ───────────────────────────────────────────────────────────

function tsr_cat_pair(): array
{
    $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    $child = Category::factory()->create(['parent_id' => $parent->category_id, 'priority' => 'low']);

    return [$parent, $child];
}

/**
 * Monta payload mínimo válido para agent.ticket.store.
 * agent_id é sempre obrigatório para evitar conflito com a validação condicional
 * do SaveTicketRequest (que exige agente para alguns status_ids).
 */
function tsr_payload(Company $company, array $cats, Status $status, int $agentId): array
{
    [$parent, $child] = $cats;

    return [
        'company_id' => $company->id,
        'status_id' => $status->id,
        'category_id' => $parent->category_id,
        'sub_category_id' => $child->category_id,
        'contact' => 'JOSE SILVA',
        'agent_id' => $agentId,
        'trouble' => 'Problema de acesso',
        'solution' => 'Configurado corretamente',
    ];
}

// ─── Testes de redirect após salvar chamado ────────────────────────────────────

describe('TicketsController — redirect após salvar chamado', function () {

    beforeEach(function () {
        $this->seed(StatusSeeder::class);
    });

    it('status comum com agente redireciona para a lista de chamados', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $comum = Status::factory()->create(); // is_terminal=false, requires_schedule=false
        $cats = tsr_cat_pair();

        $response = $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $comum, $agent->id));

        $response->assertRedirect(route('agent.ticket.index'));
    });

    it('status Visita Técnica com agente redireciona para criação de agendamento', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $vt = Status::query()->findOrFail(TicketStatusCatalog::TECHNICAL_VISIT_ID);
        $cats = tsr_cat_pair();

        $response = $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $vt, $agent->id));

        $location = $response->headers->get('Location');
        expect($location)->toContain(route('agent.schedules.create'))
            ->and($location)->toContain('ticket_id=');
    });

    it('status terminal com agente redireciona para a lista de chamados', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $terminal = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);
        $cats = tsr_cat_pair();

        $response = $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $terminal, $agent->id));

        $response->assertRedirect(route('agent.ticket.index'));
    });

    it('status Visita Técnica persiste o status_id correto no banco', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $vt = Status::query()->findOrFail(TicketStatusCatalog::TECHNICAL_VISIT_ID);
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $vt, $agent->id));

        $this->assertDatabaseHas('ticketit', [
            'company_id' => $company->id,
            'status_id' => $vt->id,
            'agent_id' => $agent->id,
        ]);
    });

    it('status comum com agente persiste como Em Andamento (2) no banco', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $comum = Status::factory()->create();
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $comum, $agent->id));

        $this->assertDatabaseHas('ticketit', [
            'company_id' => $company->id,
            'status_id' => 2,
            'agent_id' => $agent->id,
        ]);
    });

    it('status terminal com agente persiste o status terminal no banco', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $terminal = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $terminal, $agent->id));

        $this->assertDatabaseHas('ticketit', [
            'company_id' => $company->id,
            'status_id' => $terminal->id,
        ]);
    });

    // ─── Testes do novo comportamento: redirect para listagem após salvar ────

    it('CREATE redireciona para a lista de chamados', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $comum = Status::factory()->create();
        $cats = tsr_cat_pair();

        $response = $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $comum, $agent->id));

        $response->assertRedirect(route('agent.ticket.index'));
    });

    it('CREATE exibe flash "criado com sucesso"', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $comum = Status::factory()->create();
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $comum, $agent->id))
            ->assertSessionHas('status', fn ($v) => str_contains($v, 'criado com sucesso'));
    });

    it('UPDATE redireciona para a lista de chamados', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $comum = Status::factory()->create();
        $cats = tsr_cat_pair();

        // Cria o ticket primeiro
        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $comum, $agent->id));
        $ticket = \App\Models\Ticket\Ticket::where('company_id', $company->id)->latest()->first();

        // Edita
        $response = $this->put(
            route('agent.ticket.update', $ticket->id),
            tsr_payload($company, $cats, $comum, $agent->id)
        );

        $response->assertRedirect(route('agent.ticket.index'));
    });

    it('UPDATE exibe flash "atualizado com sucesso"', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $comum = Status::factory()->create();
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $comum, $agent->id));
        $ticket = \App\Models\Ticket\Ticket::where('company_id', $company->id)->latest()->first();

        $this->put(route('agent.ticket.update', $ticket->id), tsr_payload($company, $cats, $comum, $agent->id))
            ->assertSessionHas('status', fn ($v) => str_contains($v, 'atualizado com sucesso'));
    });

    // ─── Testes de regressão: bug "Resolvido indo pro agendamento" ───────────

    it('status terminal com requires_schedule=true NÃO redireciona para agendamento', function () {
        // Garante que mesmo com dado inconsistente no banco (is_terminal=true E
        // requires_schedule=true), o redirect vai para a listagem.
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $terminal = Status::query()->findOrFail(TicketStatusCatalog::RESOLVED_ID);
        DB::table('ticketit_statuses')->where('id', $terminal->id)->update(['requires_schedule' => true]);
        $cats = tsr_cat_pair();

        $response = $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $terminal, $agent->id));

        $response->assertRedirect(route('agent.ticket.index'));
    });

    it('status pendente com agente preserva o status pendente', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $pendente = Status::query()->findOrFail(TicketStatusCatalog::PENDING_ID);
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $pendente, $agent->id))
            ->assertRedirect(route('agent.ticket.index'));

        $this->assertDatabaseHas('ticketit', [
            'company_id' => $company->id,
            'status_id' => $pendente->id,
            'agent_id' => $agent->id,
        ]);
    });

    it('status Solicitação preserva o status e cria a tarefa vinculada', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $solicitacao = Status::query()->findOrFail(TicketStatusCatalog::REQUEST_ID);
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $solicitacao, $agent->id))
            ->assertRedirect(route('agent.ticket.index'));

        $ticket = \App\Models\Ticket\Ticket::where('company_id', $company->id)->latest()->first();

        expect($ticket)->not->toBeNull()
            ->and((int) $ticket->status_id)->toBe($solicitacao->id)
            ->and($ticket->task_id)->not->toBeNull();
    });

    it('status terminal com agente define completed_at no banco', function () {
        // Garante que completed_at é preenchido com base no status efetivamente salvo
        // ($statusId), não no status solicitado pelo formulário ($requestedStatusId).
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $terminal = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $terminal, $agent->id));

        $ticket = \App\Models\Ticket\Ticket::where('company_id', $company->id)->latest()->first();
        expect($ticket->completed_at)->not->toBeNull();
    });

    it('status comum com agente NÃO define completed_at no banco', function () {
        // Status não-terminal não deve setar completed_at.
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $comum = Status::factory()->create(); // is_terminal=false
        $cats = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), tsr_payload($company, $cats, $comum, $agent->id));

        $ticket = \App\Models\Ticket\Ticket::where('company_id', $company->id)->latest()->first();
        expect($ticket->completed_at)->toBeNull();
    });

    it('requisição sem autenticação redireciona para login', function () {
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create();
        [$parent, $child] = tsr_cat_pair();

        $this->post(route('agent.ticket.store'), [
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $child->category_id,
            'contact' => 'TESTE',
        ])->assertRedirect();
    });

});
