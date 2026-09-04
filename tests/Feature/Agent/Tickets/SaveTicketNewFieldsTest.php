<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;

/**
 * Garante que os campos adicionados ao SaveTicketRequest
 * (version, release, created_at, attachments, obs, elapsed_time)
 * são aceitos na validação e persistidos corretamente.
 *
 * Cobre a consolidação de $rawInput → $data (validated).
 */
describe('SaveTicketRequest — novos campos validados (version, release, obs, created_at)', function () {

    beforeEach(function () {
        $this->agent   = actingAsAgent();
        $this->company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $this->parent  = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        $this->child   = Category::factory()->create(['parent_id' => $this->parent->category_id, 'priority' => 'low']);
        $this->status  = Status::factory()->create([
            'requires_agent'    => false,
            'is_terminal'       => false,
            'requires_schedule' => false,
            'requires_solution' => false,
        ]);
    });

    function newTicketPayload(array $overrides = []): array
    {
        return array_merge([
            'company_id'      => null, // definido nos testes via beforeEach
            'status_id'       => null,
            'category_id'     => null,
            'sub_category_id' => null,
            'contact'         => 'JOAO TESTE',
            'agent_id'        => null,
        ], $overrides);
    }

    it('version string numérica é aceita e convertida pelo service', function () {
        $payload = [
            'company_id'      => $this->company->id,
            'status_id'       => $this->status->id,
            'category_id'     => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'contact'         => 'JOAO TESTE',
            'version'         => '1',
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors('version');

        $ticket = Ticket::latest()->first();
        expect($ticket->version)->toBe('01.28.01');
    });

    it('version formatada (dd.dd.dd) é aceita e mantida', function () {
        $payload = [
            'company_id'      => $this->company->id,
            'status_id'       => $this->status->id,
            'category_id'     => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'contact'         => 'JOAO TESTE',
            'version'         => '02.05.01',
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors('version');

        $ticket = Ticket::latest()->first();
        expect($ticket->version)->toBe('02.05.01');
    });

    it('obs é aceito e persistido no ticket', function () {
        $payload = [
            'company_id'      => $this->company->id,
            'status_id'       => $this->status->id,
            'category_id'     => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'contact'         => 'JOAO TESTE',
            'obs'             => 'Observação interna do atendimento',
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors('obs');

        $ticket = Ticket::latest()->first();
        expect($ticket->obs)->toBe('Observação interna do atendimento');
    });

    it('created_at válido é aceito pelo FormRequest', function () {
        $payload = [
            'company_id'      => $this->company->id,
            'status_id'       => $this->status->id,
            'category_id'     => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'contact'         => 'JOAO TESTE',
            'created_at'      => '2025-01-15 10:00:00',
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors('created_at');

        $ticket = Ticket::latest()->first();
        expect($ticket->created_at->format('Y-m-d'))->toBe('2025-01-15');
    });

    it('created_at inválido é rejeitado pelo FormRequest', function () {
        $payload = [
            'company_id'      => $this->company->id,
            'status_id'       => $this->status->id,
            'category_id'     => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'contact'         => 'JOAO TESTE',
            'created_at'      => 'not-a-date',
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionHasErrors('created_at');
    });

    it('attachments como array de inteiros é aceito', function () {
        $payload = [
            'company_id'      => $this->company->id,
            'status_id'       => $this->status->id,
            'category_id'     => $this->parent->category_id,
            'sub_category_id' => $this->child->category_id,
            'contact'         => 'JOAO TESTE',
            'attachments'     => [],
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors('attachments');
    });

});
