<?php

use App\Models\Company;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module as ScheduleModule;
use App\Models\State;
use Symfony\Component\DomCrawler\Crawler;

describe('Empresas — busca e listagem AJAX', function () {
    it('mantem a tabela de empresas dentro do escopo Alpine da busca', function () {
        actingAsAgent();
        Company::factory()->create(['trade_name' => 'Empresa Visivel']);

        $response = $this->get(route('agent.companies.manage.index'))
            ->assertOk()
            ->assertViewIs('agent.companies.index');

        $crawler = new Crawler($response->getContent());
        $tableContainer = $crawler->filterXPath('//*[@x-ref="tableContainer"]')->getNode(0);

        expect($tableContainer)->not->toBeNull();

        $hasCompanySearchScope = false;
        $node = $tableContainer;

        while ($node !== null) {
            if ($node instanceof DOMElement && str_contains($node->getAttribute('x-data'), 'companySearch')) {
                $hasCompanySearchScope = true;
                break;
            }

            $node = $node->parentNode;
        }

        expect($hasCompanySearchScope)->toBeTrue();

        $response->assertSee('/support/company/${company.id}/history', false);
    });

    it('usa busca incremental sem recarregar a pagina a cada tecla', function () {
        actingAsAgent();

        $this->get(route('agent.companies.manage.index'))
            ->assertOk()
            ->assertSee('type="search"', false)
            ->assertSee('@input.debounce.300ms="handleSearchInput()"', false)
            ->assertSee('new AbortController()', false)
            ->assertSee('window.history.replaceState', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee(':aria-busy="isSearching ? \'true\' : \'false\'"', false)
            ->assertDontSee('@input.debounce.500ms="performSearch()"', false);
    });

    it('usa a rota correta de historico nos resultados da busca rapida', function () {
        actingAsAgent();

        $this->get(route('agent.companies.manage.search'))
            ->assertOk()
            ->assertSee("support/company/' + company.id + '/history", false);
    });

    it('retorna resultados na busca rapida quando a empresa possui estado e modulo RAT', function () {
        actingAsAgent();

        $state = State::factory()->create(['abbreviation' => 'ES']);
        $company = Company::factory()->create([
            'name' => 'Alpha Sistemas LTDA',
            'trade_name' => 'Alpha Sistemas',
            'state_id' => $state->id,
            'city' => 'Vitória',
        ]);
        $module = ScheduleModule::factory()->create([
            'name' => 'Implantação PDV',
            'project' => 'EasyMaster',
        ]);

        ElementType::factory()->create(['module_id' => $module->id]);
        $company->scheduleModules()->attach($module->id);

        $this->getJson(route('agent.api.v1.companies.search', ['q' => 'Alpha']))
            ->assertOk()
            ->assertJsonPath('0.id', $company->id)
            ->assertJsonPath('0.state_abbr', 'ES')
            ->assertJsonPath('0.schedule_rat_modules.0.id', $module->id)
            ->assertJsonPath('0.schedule_rat_modules.0.element_count', 1);
    });
});
