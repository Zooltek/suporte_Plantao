<?php

use App\Models\Company;

describe('Máscara de telefone dos contatos da empresa', function () {
    it('renderiza a regex correta no formulário de criação', function () {
        actingAsAgent();

        $this->get(route('agent.companies.manage.create'))
            ->assertOk()
            ->assertSee("replace(/\\D/g, '').slice(0, 11)", false)
            ->assertDontSee("replace(/\\\\D/g, '').slice(0, 11)", false);
    });

    it('renderiza a regex correta no formulário de edição', function () {
        actingAsAgent();
        $company = Company::factory()->create();

        $this->get(route('agent.companies.manage.edit', $company))
            ->assertOk()
            ->assertSee("replace(/\\D/g, '').slice(0, 11)", false)
            ->assertDontSee("replace(/\\\\D/g, '').slice(0, 11)", false);
    });
});
