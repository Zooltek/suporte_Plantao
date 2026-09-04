<?php

use App\Models\Company;

describe('Empresas — contatos sincronizados pelo Financeiro', function () {
    it('preserva contatos do financeiro ao editar contatos do suporte', function () {
        actingAsAgent();
        $company = Company::factory()->create();
        $company->contacts()->create([
            'name' => 'Contato Financeiro',
            'email' => 'financeiro@empresa.com.br',
            'origin' => 'financeiro',
            'is_main' => false,
        ]);
        $company->contacts()->create([
            'name' => 'Contato Antigo do Suporte',
            'phone' => '(27) 99999-0000',
            'origin' => 'support',
            'is_main' => true,
        ]);

        $this->put(route('agent.companies.manage.update', $company), [
            'name' => $company->name,
            'contacts' => [[
                'name' => 'Novo Contato do Suporte',
                'phone' => '(27) 98888-1111',
                'email' => 'novo.suporte@empresa.com.br',
                'is_main' => 1,
            ]],
        ])->assertRedirect();

        expect($company->contacts()->where('origin', 'financeiro')->pluck('name')->all())
            ->toBe(['Contato Financeiro'])
            ->and($company->contacts()->where('origin', 'support')->pluck('name')->all())
            ->toBe(['Novo Contato do Suporte'])
            ->and($company->contacts()->where('origin', 'support')->value('email'))
            ->toBe('novo.suporte@empresa.com.br');
    });
});
