<?php

use App\Models\Ticket\Ticket;
use App\Models\User;

function ct_agent(array $attributes = []): User
{
    return User::factory()->agent()->create($attributes);
}

function ct_ticket(array $overrides = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'agent_id' => ct_agent()->id,
        'completed_at' => null,
        'status_id' => 1,
    ], $overrides));
}

describe('Condensed — tickets com responsável', function () {

    it('agente vê chamados atribuídos a qualquer responsável na visão condensada', function () {
        $agent = actingAsAgent();
        $other = ct_agent();

        $myTicket = ct_ticket([
            'agent_id' => $agent->id,
            'subject' => 'Chamado do agente logado',
        ]);

        $otherTicket = ct_ticket([
            'agent_id' => $other->id,
            'subject' => 'Chamado de outro responsável',
        ]);

        $tickets = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('tickets');

        expect($tickets->pluck('id')->all())->toContain($myTicket->id, $otherTicket->id);
    });

    it('exibe a coluna responsável sem perder a coluna de contato', function () {
        actingAsAgent();
        $other = ct_agent(['name' => 'Suporte Secundário']);

        ct_ticket([
            'agent_id' => $other->id,
            'contact' => 'CLIENTE TESTE',
            'subject' => 'Chamado com responsável visível',
        ]);

        $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->assertSee('Responsável')
            ->assertSee('Contato')
            ->assertSee('Suporte Secundário')
            ->assertSee('CLIENTE TESTE');
    });

});
