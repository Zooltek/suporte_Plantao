<?php

/**
 * Testes de integração — module_id no formulário de chamados.
 *
 * Cobre:
 *  - Persistência de module_id no banco após salvar ticket
 *  - Validação: module_id inexistente é rejeitado
 *  - Ticket sem módulo selecionado → module_id=null no banco
 */

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyModuleType;
use App\Models\Schedule\Module as RatModule;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function stm_payload(array $overrides = []): array
{
    return array_merge([
        'contact' => 'TESTE MÓDULO',
        'agent_id' => null,
        'version' => null,
        'release' => null,
    ], $overrides);
}

// ─── Setup ────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->agent = actingAsAgent();
    $this->company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
    $this->parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    $this->child = Category::factory()->create(['parent_id' => $this->parent->category_id, 'priority' => 'low']);
    $this->status = Status::factory()->create([
        'requires_agent' => false,
        'is_terminal' => false,
        'requires_schedule' => false,
        'requires_solution' => false,
    ]);
    $this->module = CompanyModuleType::factory()->create(['is_active' => true]);
    $this->company->moduleTypes()->attach($this->module->id);
});

// ─── Persistência ─────────────────────────────────────────────────────────────

describe('SaveTicket — module_id', function () {

    it('persiste module_id no banco ao criar chamado', function () {
        $payload = stm_payload([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'module_id' => $this->module->id,
        ]);

        $response = $this->post(route('agent.ticket.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('ticketit', [
            'company_id' => $this->company->id,
            'module_id' => $this->module->id,
        ]);
    });

    it('persiste rat_module_id padrão quando o módulo contratado possui template vinculado', function () {
        $ratModule = RatModule::factory()->create(['name' => 'Financeiro Ticket', 'project' => 'ERP']);
        $module = CompanyModuleType::factory()->withRatModule($ratModule)->create(['is_active' => true]);
        $this->company->moduleTypes()->sync([$module->id]);

        $payload = stm_payload([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'module_id' => $module->id,
        ]);

        $this->post(route('agent.ticket.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('ticketit', [
            'company_id' => $this->company->id,
            'module_id' => $module->id,
            'rat_module_id' => $ratModule->id,
        ]);
    });

    it('persiste rat_module_id selecionado manualmente quando o módulo não possui template padrão', function () {
        $ratModule = RatModule::factory()->create(['name' => 'Estoque Ticket', 'project' => 'ERP']);
        $this->company->scheduleModules()->attach($ratModule->id);

        $payload = stm_payload([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'module_id' => $this->module->id,
            'rat_module_id' => $ratModule->id,
        ]);

        $this->post(route('agent.ticket.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('ticketit', [
            'company_id' => $this->company->id,
            'module_id' => $this->module->id,
            'rat_module_id' => $ratModule->id,
        ]);
    });

    it('module_id nulo é aceito e persiste null no banco', function () {
        $payload = stm_payload([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'module_id' => null,
        ]);

        $this->post(route('agent.ticket.store'), $payload)->assertRedirect();

        $ticket = Ticket::where('company_id', $this->company->id)->latest()->first();
        expect($ticket->module_id)->toBeNull();
    });

    it('module_id inexistente retorna erro de validação', function () {
        $payload = stm_payload([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'module_id' => 999999,
        ]);

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionHasErrors('module_id');
    });

    it('rejeita módulo contratado que não pertence ao cliente', function () {
        $otherModule = CompanyModuleType::factory()->create(['is_active' => true]);

        $payload = stm_payload([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'module_id' => $otherModule->id,
        ]);

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionHas('warning');
    });

    it('rejeita checklist RAT técnico fora do catálogo do cliente quando há módulos técnicos configurados', function () {
        $allowed = RatModule::factory()->create(['name' => 'Financeiro Permitido', 'project' => 'ERP']);
        $blocked = RatModule::factory()->create(['name' => 'Contábil Bloqueado', 'project' => 'ERP']);
        $this->company->scheduleModules()->attach($allowed->id);

        $payload = stm_payload([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'module_id' => $this->module->id,
            'rat_module_id' => $blocked->id,
        ]);

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionHas('warning');
    });

    it('persiste module_id ao editar chamado existente', function () {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'agent_id' => $this->agent->id,
            'status_id' => $this->status->id,
            'module_id' => null,
            'user_id' => $this->agent->id,
            'author_id' => $this->agent->id,
        ]);

        $payload = stm_payload([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'module_id' => $this->module->id,
        ]);

        $this->put(route('agent.ticket.update', $ticket->id), $payload)->assertRedirect();

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'module_id' => $this->module->id,
        ]);
    });

});
