<?php

/**
 * Histórico de atendimento deve exibir os problemas e resoluções dos chamados
 * (ticket_issues) — tanto na tela de histórico da empresa quanto no partial
 * carregado via AJAX na tela de abertura de chamado.
 */

use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketIssue;
use Illuminate\Support\Facades\Cache;

describe('Company history — problemas e resoluções dos chamados', function () {

    it('exibe problemas e resoluções na tela de histórico de chamados', function () {
        actingAsAgent();

        $company = Company::factory()->create();
        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        TicketIssue::factory()->for($ticket)->create([
            'title' => 'Impressora fiscal travando',
            'description' => 'PDV congela ao emitir cupom.',
            'solution' => 'Atualizado driver da impressora e reiniciado o serviço.',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        $response = $this->get(route('agent.company.history', $company->id))
            ->assertOk();

        $html = $response->getContent();

        expect($html)
            ->toContain('Problemas e Resoluções')
            ->toContain('Impressora fiscal travando')
            ->toContain('Atualizado driver da impressora e reiniciado o serviço.');
    });

    it('exibe problema e resolução gravados diretamente no chamado', function () {
        actingAsAgent();

        $company = Company::factory()->create();
        Ticket::factory()->create([
            'company_id' => $company->id,
            'trouble' => 'PDV nao finaliza venda com NFC-e.',
            'solution' => 'Reemitido certificado digital e reiniciado o emissor fiscal.',
        ]);

        $response = $this->get(route('agent.company.history', $company->id))
            ->assertOk();

        expect($response->getContent())
            ->toContain('Problemas e Resoluções')
            ->toContain('PDV nao finaliza venda com NFC-e.')
            ->toContain('Reemitido certificado digital e reiniciado o emissor fiscal.');
    });

    it('exibe problemas e resoluções no partial AJAX usado na abertura de chamado', function () {
        actingAsAgent();

        $company = Company::factory()->create();
        $terminal = Status::factory()->terminal()->create();

        // Repository cacheia os IDs de status terminais — invalida o cache
        // primado por factories anteriores para enxergar o status recém-criado.
        Cache::forget('ticket.status_ids.terminal');

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $terminal->id,
            'completed_at' => now(),
        ]);

        TicketIssue::factory()->for($ticket)->create([
            'title' => 'Erro de sincronização do e-commerce',
            'description' => 'Pedidos não importavam.',
            'solution' => 'Token de integração renovado.',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('agent.company.history', $company->id))
            ->assertOk();

        $html = $response->getContent();

        expect($html)
            ->toContain('Problemas e Resoluções')
            ->toContain('Erro de sincronização do e-commerce')
            ->toContain('Token de integração renovado.');
    });

    it('exibe problema e resolução legados no partial AJAX de chamados finalizados', function () {
        actingAsAgent();

        $company = Company::factory()->create();
        $terminal = Status::factory()->terminal()->create();

        Cache::forget('ticket.status_ids.terminal');

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $terminal->id,
            'completed_at' => now(),
            'trouble' => 'Importacao de pedidos parou apos troca de token.',
            'solution' => 'Token atualizado e fila de pedidos reprocessada.',
        ]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('agent.company.history', $company->id))
            ->assertOk();

        expect($response->getContent())
            ->toContain('Problemas e Resoluções')
            ->toContain('Importacao de pedidos parou apos troca de token.')
            ->toContain('Token atualizado e fila de pedidos reprocessada.');
    });

    it('marca como pendente o problema ainda sem resolução', function () {
        actingAsAgent();

        $company = Company::factory()->create();
        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        TicketIssue::factory()->for($ticket)->open()->create([
            'title' => 'Relatório DRE com erro 500',
        ]);

        $response = $this->get(route('agent.company.history', $company->id))
            ->assertOk();

        expect($response->getContent())
            ->toContain('Relatório DRE com erro 500')
            ->toContain('Pendente');
    });

});
