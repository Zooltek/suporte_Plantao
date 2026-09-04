<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;

/**
 * Regressão para o ParseError reportado em
 * resources/views/agent/ticket/create.blade.php:130 ao acessar
 * /support/tickets/{id}/edit.
 *
 * O bug era causado pelo uso de `@php(...)` short syntax com o
 * operador nullsafe `?->` + null coalescing `??`, que não era
 * tokenizado corretamente pelo Blade e gerava
 * "syntax error, unexpected token @" em runtime.
 */
describe('Renderização das telas de criação/edição de chamado', function () {

    function tevr_setup_ticket(): Ticket
    {
        $company = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
        ]);
        $status = Status::factory()->create();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        return Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $cat->category_id,
        ]);
    }

    it('admin consegue abrir a tela de edição sem ParseError', function () {
        actingAsAdmin();
        $ticket = tevr_setup_ticket();

        $this->get(route('agent.ticket.edit', $ticket))
            ->assertOk()
            ->assertViewHas('canChooseDepartment', true)
            ->assertSee('Setor', false);
    });

    it('agente comum consegue abrir a tela de edição sem ParseError', function () {
        $agent = actingAsAgent();
        $ticket = tevr_setup_ticket();
        $ticket->update(['agent_id' => $agent->id]);

        $this->get(route('agent.ticket.edit', $ticket))
            ->assertOk()
            ->assertViewHas('canChooseDepartment', false);
    });

    it('admin consegue abrir a tela de criação sem ParseError', function () {
        actingAsAdmin();
        Department::factory()->create(['name' => 'Suporte Render']);

        $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->assertViewHas('canChooseDepartment', true);
    });

    it('agente comum consegue abrir a tela de criação sem ParseError', function () {
        actingAsAgent();

        $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->assertViewHas('canChooseDepartment', false);
    });

});
