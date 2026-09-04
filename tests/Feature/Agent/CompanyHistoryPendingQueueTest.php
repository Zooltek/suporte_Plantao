<?php

use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\User;

describe('Company history — variação por tipo de requisição', function () {

    it('retorna o partial sem master layout quando a requisição é AJAX', function () {
        actingAsAgent();

        $company = Company::factory()->create();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('agent.company.history', $company->id))
            ->assertOk();

        $html = $response->getContent();

        // Marcadores do partial estão presentes
        expect($html)->toContain('Histórico da Empresa');

        // Marcadores do master layout (layouts.agent) NÃO devem estar presentes
        // — caso contrário a tela "Novo RAT" exibe o sistema aninhado dentro dela.
        expect($html)
            ->not->toContain('<!DOCTYPE html>')
            ->not->toContain('Painel do Agente')
            ->not->toContain('Meus Chamados');
    });

    it('retorna a view completa com layout quando acessada via navegação direta', function () {
        actingAsAgent();

        $company = Company::factory()->create();

        $response = $this->get(route('agent.company.history', $company->id))
            ->assertOk();

        $html = $response->getContent();

        expect($html)
            ->toContain('<!DOCTYPE html>')
            ->toContain('Histórico da Empresa');
    });

});

describe('Company history — fila de pendências', function () {

    it('alerta apenas quando existe ticket pendente sem responsável', function () {
        actingAsAgent();

        $company = Company::factory()->create();

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => null,
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => User::factory()->agent()->create()->id,
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => 1,
            'agent_id' => null,
        ]);

        $this->get(route('agent.company.history', $company->id))
            ->assertOk()
            ->assertSee('Fila de Pendências sem responsável');
    });

    it('não exibe alerta quando há apenas tickets fora da fila de pendências', function () {
        actingAsAgent();

        $company = Company::factory()->create();

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => 1,
            'agent_id' => null,
        ]);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => User::factory()->agent()->create()->id,
        ]);

        $this->get(route('agent.company.history', $company->id))
            ->assertOk()
            ->assertDontSee('Fila de Pendências sem responsável');
    });

});
