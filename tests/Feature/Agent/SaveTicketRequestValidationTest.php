<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Support\Tickets\TicketStatusCatalog;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;

// ─── Helpers locais ───────────────────────────────────────────────────────────

function svr_cat_pair(): array
{
    $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    $child = Category::factory()->create(['parent_id' => $parent->category_id, 'priority' => 'low']);

    return [$parent, $child];
}

/**
 * Payload base válido para os testes de validação.
 * Inclui solution por padrão; omita-o nos testes que precisam verificar a ausência.
 */
function svr_payload(Company $company, array $cats, Status $status, int $agentId, array $overrides = []): array
{
    [$parent, $child] = $cats;

    return array_merge([
        'company_id' => $company->id,
        'status_id' => $status->id,
        'category_id' => $parent->category_id,
        'sub_category_id' => $child->category_id,
        'contact' => 'JOSE SILVA',
        'agent_id' => $agentId,
        'trouble' => 'Descrição do problema',
        'solution' => 'Descrição da solução',
    ], $overrides);
}

// ─── Suite de validação do SaveTicketRequest ──────────────────────────────────

describe('SaveTicketRequest — validação condicional dos campos', function () {

    beforeEach(function () {
        $this->seed(StatusSeeder::class);
    });

    // ── solution ─────────────────────────────────────────────────────────────

    it('status que requer solução sem solution falha com 422', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $resolvido = Status::query()->findOrFail(TicketStatusCatalog::RESOLVED_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $resolvido, $agent->id, [
            'solution' => '',
        ]))->assertSessionHasErrors('solution');
    });

    it('status que requer solução com solution passa a validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $resolvido = Status::query()->findOrFail(TicketStatusCatalog::RESOLVED_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $resolvido, $agent->id))
            ->assertSessionDoesntHaveErrors('solution');
    });

    it('status Em Andamento sem solution passa a validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $emAndamento = Status::query()->findOrFail(TicketStatusCatalog::IN_PROGRESS_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $emAndamento, $agent->id, [
            'solution' => null,
        ]))->assertSessionDoesntHaveErrors('solution');
    });

    it('status terminal sem requires_solution não exige solution', function () {
        // Não Resolvido: is_terminal=true mas requires_solution=false
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $naoResolvido = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $naoResolvido, $agent->id, [
            'solution' => null,
        ]))->assertSessionDoesntHaveErrors('solution');
    });

    // ── trouble ───────────────────────────────────────────────────────────────

    it('status com agente sem trouble falha com erro no campo trouble', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $status, $agent->id, [
            'trouble' => '',
        ]))->assertSessionHasErrors('trouble');
    });

    // ── agent_id ─────────────────────────────────────────────────────────────

    it('status terminal sem agent_id falha com erro no campo agent_id', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $status, $agent->id, [
            'agent_id' => null,
        ]))->assertSessionHasErrors('agent_id');
    });

    // ── Visita Técnica: status separado do "Em Andamento" ────────────────────

    it('Visita Técnica sem agent_id falha a validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $vt = Status::query()->findOrFail(TicketStatusCatalog::TECHNICAL_VISIT_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $vt, $agent->id, [
            'agent_id' => null,
        ]))->assertSessionHasErrors('agent_id');
    });

    it('Visita Técnica sem trouble falha a validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $vt = Status::query()->findOrFail(TicketStatusCatalog::TECHNICAL_VISIT_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $vt, $agent->id, [
            'trouble' => '',
        ]))->assertSessionHasErrors('trouble');
    });

    it('Visita Técnica com trouble e agent_id passa a validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $vt = Status::query()->findOrFail(TicketStatusCatalog::TECHNICAL_VISIT_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $vt, $agent->id))
            ->assertSessionDoesntHaveErrors(['trouble', 'agent_id', 'solution']);
    });

    it('Em Andamento sem solution passa a validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $emAndamento = Status::query()->findOrFail(TicketStatusCatalog::IN_PROGRESS_ID);
        $cats = svr_cat_pair();

        $this->post(route('agent.ticket.store'), svr_payload($company, $cats, $emAndamento, $agent->id, [
            'solution' => null,
        ]))->assertSessionDoesntHaveErrors('solution');
    });

    it('agent_id inexistente via acesso por ip falha com erro de validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::query()->findOrFail(TicketStatusCatalog::IN_PROGRESS_ID);
        $cats = svr_cat_pair();

        $this
            ->withServerVariables([
                'HTTP_HOST' => '192.168.0.15:8090',
                'REMOTE_ADDR' => '192.168.0.9',
            ])
            ->post(route('agent.ticket.store'), svr_payload($company, $cats, $status, $agent->id, [
                'agent_id' => 999999,
            ]))
            ->assertSessionHasErrors('agent_id');
    });

    it('origin_id inexistente via acesso por ip falha com erro de validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::query()->findOrFail(TicketStatusCatalog::IN_PROGRESS_ID);
        $cats = svr_cat_pair();

        $this
            ->withServerVariables([
                'HTTP_HOST' => '192.168.0.15:8090',
                'REMOTE_ADDR' => '192.168.0.9',
            ])
            ->post(route('agent.ticket.store'), svr_payload($company, $cats, $status, $agent->id, [
                'origin_id' => 999999,
            ]))
            ->assertSessionHasErrors('origin_id');
    });

    it('status_id inexistente via acesso por ip falha com erro de validação', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::query()->findOrFail(TicketStatusCatalog::IN_PROGRESS_ID);
        $cats = svr_cat_pair();

        $this
            ->withServerVariables([
                'HTTP_HOST' => '192.168.0.15:8090',
                'REMOTE_ADDR' => '192.168.0.9',
            ])
            ->post(route('agent.ticket.store'), svr_payload($company, $cats, $status, $agent->id, [
                'status_id' => 999999,
            ]))
            ->assertSessionHasErrors('status_id');
    });

});
