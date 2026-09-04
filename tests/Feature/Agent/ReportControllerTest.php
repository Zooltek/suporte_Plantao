<?php

describe('Agent ReportController — implementationClients()', function () {

    it('retorna a view correta para agente autenticado', function () {
        actingAsAgent();

        $this->get(route('agent.report.implementation-clients'))
            ->assertOk()
            ->assertViewIs('agent.reports.implementation-clients');
    });

    it('expõe os dados resumidos esperados para a view', function () {
        actingAsAgent();

        $response = $this->get(route('agent.report.implementation-clients'))
            ->assertOk();

        expect($response->viewData('clients'))->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($response->viewData('totalClients'))->toBeInt();
        expect($response->viewData('totalOpenTickets'))->toBeInt();
        expect($response->viewData('totalSchedules'))->toBeInt();
        expect($response->viewData('totalImplementationMinutes'))->toBeInt();
        expect($response->viewData('totalImplementationFormatted'))->toBeString();
    });

    it('mantém o relatório no contexto visual de implantação', function () {
        actingAsAgent();

        $this->get(route('agent.report.implementation-clients'))
            ->assertOk()
            ->assertSee('Relatório de Implantação')
            ->assertSee('Clientes em Implantação')
            ->assertSee('Voltar para Implantação');
    });

    it('exibe estado vazio sem perder o contexto da navegação', function () {
        actingAsAgent();

        $this->get(route('agent.report.implementation-clients'))
            ->assertOk()
            ->assertSee('Nenhum cliente em implantação')
            ->assertSee('Todos os clientes estão com atividades concluídas.');
    });

    it('redireciona visitante não autenticado', function () {
        $this->get(route('agent.report.implementation-clients'))
            ->assertRedirect();
    });

});
