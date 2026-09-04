<?php

use App\Models\Company;

describe('Máscara de CNPJ no formulário de empresa', function () {
    it('renderiza o componente de máscara CNPJ no formulário de edição', function () {
        actingAsAgent();
        $company = Company::factory()->create();

        $response = $this->get(route('agent.companies.manage.edit', $company))
            ->assertOk();

        $response->assertSee('companyEditCnpj(', false);
        $response->assertSee('x-model="cnpj"', false);
        $response->assertSee('onInput($event.target)', false);
        $response->assertSee('maxlength="18"', false);

        $response->assertSee("replace(/^(\\d{2})(\\d)/, '\$1.\$2')", false);
        $response->assertSee("replace(/^(\\d{2})\\.(\\d{3})(\\d)/, '\$1.\$2.\$3')", false);
        $response->assertSee("replace(/\\.(\\d{3})(\\d)/, '.\$1/\$2')", false);
        $response->assertSee("replace(/(\\d{4})(\\d)/, '\$1-\$2')", false);

        $response->assertDontSee("replace(/^(\\\\d{2})(\\\\d)/", false);
    });

    it('inicializa o componente Alpine com o CNPJ atual da empresa', function () {
        actingAsAgent();
        $company = Company::factory()->create(['cnpj' => '12.345.678/0001-90']);

        $this->get(route('agent.companies.manage.edit', $company))
            ->assertOk()
            ->assertSee('companyEditCnpj(', false)
            ->assertSee('12.345.678\/0001-90', false);
    });
});
