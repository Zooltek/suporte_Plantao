<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Ticket;

describe('Ticket show — resumo de problemas e resoluções', function () {

    it('exibe problema e resolução gravados diretamente no chamado', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
        ]);
        $category = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'agent_id' => $agent->id,
            'author_id' => $agent->id,
            'user_id' => $agent->id,
            'department_id' => $agent->department_id,
            'category_id' => $category->category_id,
            'trouble' => 'Cliente nao consegue emitir NFC-e.',
            'solution' => 'Reconfigurado certificado digital e validada emissao.',
        ]);

        $response = $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk();

        expect($response->getContent())
            ->toContain('Problemas e Resoluções')
            ->toContain('Cliente nao consegue emitir NFC-e.')
            ->toContain('Reconfigurado certificado digital e validada emissao.');
    });
});
