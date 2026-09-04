<?php

/**
 * Testes de integração — PATCH /tickets/{ticket}/quick-update
 *
 * Cobre os dois campos suportados pelo QuickUpdateTicketRequest:
 *   - status_id: mudança pontual de status com regras de negócio
 *   - agent_id:  mudança pontual de agente com reset de status quando necessário
 *
 * Também verifica autenticação, autorização e validação.
 */

use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function qut_ticket(User $agent, Status $status, Company $company): Ticket
{
    return Ticket::factory()->create([
        'company_id' => $company->id,
        'agent_id' => $agent->id,
        'status_id' => $status->id,
        'user_id' => $agent->id,
        'author_id' => $agent->id,
    ]);
}

function qut_company(): Company
{
    return Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    // Garante que status ID=2 (STATUS_IN_PROGRESS_ID) existe sem flags especiais
    DB::table('ticketit_statuses')
        ->where('id', 2)
        ->update([
            'name' => 'Em andamento',
            'requires_schedule' => false,
            'requires_solution' => false,
            'requires_agent' => true,
            'is_terminal' => false,
        ]);
});

// ─── Autenticação e Autorização ──────────────────────────────────────────────

describe('TicketQuickUpdate — autenticação e autorização', function () {

    it('sem autenticação redireciona para login', function () {
        $agent = User::factory()->agent()->create();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => $status->id])
            ->assertRedirect();
    });

    it('agente não-dono recebe 403', function () {
        $owner = User::factory()->agent()->create();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($owner, $status, $company);

        actingAsAgent(); // agente diferente do dono

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => $status->id])
            ->assertStatus(403);
    });

});

// ─── Validação ───────────────────────────────────────────────────────────────

describe('TicketQuickUpdate — validação', function () {

    it('sem nenhum campo retorna erro de validação', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), [])
            ->assertSessionHasErrors('field');
    });

    it('status_id inexistente retorna erro de validação', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => 999999])
            ->assertSessionHasErrors('status_id');
    });

    it('agent_id inexistente retorna erro de validação', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['agent_id' => 999999])
            ->assertSessionHasErrors('agent_id');
    });

});

// ─── Atualização de Status ────────────────────────────────────────────────────

describe('TicketQuickUpdate — atualização de status', function () {

    it('persiste o novo status no banco', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $atual = Status::factory()->create();
        $novo = Status::factory()->create();
        $ticket = qut_ticket($agent, $atual, $company);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => $novo->id]);

        $this->assertDatabaseHas('ticketit', ['id' => $ticket->id, 'status_id' => $novo->id]);
    });

    it('status comum redireciona para a tela do chamado', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);
        $novo = Status::factory()->create();

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => $novo->id])
            ->assertRedirect(route('agent.ticket.show', $ticket->id));
    });

    it('status Visita Técnica redireciona para criação de agendamento', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $comum = Status::factory()->create();
        $ticket = qut_ticket($agent, $comum, $company);
        $vt = Status::factory()->visitaTecnica()->create();

        $location = $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => $vt->id])
            ->headers->get('Location');

        expect($location)->toContain(route('agent.schedules.create'))
            ->and($location)->toContain('ticket_id=');
    });

    it('status terminal redireciona de volta com aviso para usar o fluxo de fechamento', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $comum = Status::factory()->create();
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'agent_id' => $agent->id,
            'status_id' => $comum->id,
            'user_id' => $agent->id,
            'author_id' => $agent->id,
        ]);

        $terminal = Status::factory()->requiresSolution()->create(['name' => 'Resolvido']);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => $terminal->id])
            ->assertRedirect()
            ->assertSessionHas('warning');
    });

    it('status terminal não altera completed_at pelo quick update', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $comum = Status::factory()->create();
        $ticket = qut_ticket($agent, $comum, $company);
        $terminal = Status::factory()->terminal()->create(['name' => 'Não Resolvido']);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => $terminal->id]);

        expect(Ticket::find($ticket->id)->completed_at)->toBeNull()
            ->and(Ticket::find($ticket->id)->status_id)->toBe($comum->id);
    });

    it('status não-terminal não preenche completed_at', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);
        $novo = Status::factory()->create();

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['status_id' => $novo->id]);

        expect(Ticket::find($ticket->id)->completed_at)->toBeNull();
    });

});

// ─── Atualização de Agente ────────────────────────────────────────────────────

describe('TicketQuickUpdate — atualização de agente', function () {

    it('persiste o novo agente no banco', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);
        $novoAgt = User::factory()->agent()->create();

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['agent_id' => $novoAgt->id]);

        $this->assertDatabaseHas('ticketit', ['id' => $ticket->id, 'agent_id' => $novoAgt->id]);
    });

    it('redireciona para a tela do chamado ao trocar agente', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);
        $novo = User::factory()->agent()->create();

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['agent_id' => $novo->id])
            ->assertRedirect(route('agent.ticket.show', $ticket->id));
    });

    it('ao remover agente de chamado com status Visita Técnica, reseta status para Pendente', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $vt = Status::factory()->visitaTecnica()->create();
        $ticket = qut_ticket($agent, $vt, $company);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['agent_id' => '']);

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'agent_id' => null,
            'status_id' => Ticket::STATUS_PENDING_ID,
        ]);
    });

    it('ao remover agente de chamado com status comum, retorna para Pendente', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $comum = Status::factory()->create(['requires_schedule' => false]);
        $ticket = qut_ticket($agent, $comum, $company);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['agent_id' => '']);

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'agent_id' => null,
            'status_id' => Ticket::STATUS_PENDING_ID,
        ]);
    });

    it('ao remover agente de chamado finalizado, limpa completed_at e devolve para Pendente', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $terminal = Status::factory()->terminal()->create();
        $ticket = qut_ticket($agent, $terminal, $company);
        $ticket->forceFill(['completed_at' => now()])->save();

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['agent_id' => '']);

        $ticket->refresh();

        expect($ticket->agent_id)->toBeNull()
            ->and($ticket->status_id)->toBe(Ticket::STATUS_PENDING_ID)
            ->and($ticket->completed_at)->toBeNull();
    });

    it('ao trocar agente em chamado com status Visita Técnica, status não muda', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $vt = Status::factory()->visitaTecnica()->create();
        $ticket = qut_ticket($agent, $vt, $company);
        $novo = User::factory()->agent()->create();

        $this->patch(route('agent.ticket.quick-update', $ticket->id), ['agent_id' => $novo->id]);

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'agent_id' => $novo->id,
            'status_id' => $vt->id,
        ]);
    });

});

// ─── Atualização de Categoria e Subcategoria ─────────────────────────────────

describe('TicketQuickUpdate — categoria e subcategoria', function () {

    it('atualiza categoria e subcategoria com sucesso', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);

        $cat = \App\Models\Category::factory()->create(['parent_id' => 0]);
        $subCat = \App\Models\Category::factory()->create(['parent_id' => $cat->category_id]);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), [
            'category_id' => $cat->category_id,
            'sub_category_id' => $subCat->category_id,
        ])
            ->assertRedirect(route('agent.ticket.show', $ticket->id))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'category_id' => $cat->category_id,
            'sub_category_id' => $subCat->category_id,
        ]);
    });

    it('atualiza apenas categoria mantendo subcategoria nula', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);

        $cat = \App\Models\Category::factory()->create(['parent_id' => 0]);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), [
            'category_id' => $cat->category_id,
            'sub_category_id' => '',
        ])
            ->assertRedirect(route('agent.ticket.show', $ticket->id));

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'category_id' => $cat->category_id,
            'sub_category_id' => null,
        ]);
    });

    it('rejeita category_id inexistente', function () {
        $agent = actingAsAdmin();
        $company = qut_company();
        $status = Status::factory()->create();
        $ticket = qut_ticket($agent, $status, $company);

        $this->patch(route('agent.ticket.quick-update', $ticket->id), [
            'category_id' => 999999,
        ])
            ->assertSessionHasErrors('category_id');
    });

});

