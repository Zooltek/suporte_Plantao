<?php

use App\Models\Company;
use App\Models\CompanyModuleType;
use Database\Seeders\Ticket\TicketRatHomologationSeeder;

describe('TicketRatHomologationSeeder', function () {
    it('cria empresas e vinculos controlados para homologacao visual do fluxo RAT', function () {
        $this->seed(TicketRatHomologationSeeder::class);

        $autoCompany = Company::query()
            ->with(['moduleTypes', 'scheduleModules'])
            ->where('cnpj', '90.000.000/0001-01')
            ->firstOrFail();

        $manualCompany = Company::query()
            ->with(['moduleTypes', 'scheduleModules'])
            ->where('cnpj', '90.000.000/0001-02')
            ->firstOrFail();

        $mixedCompany = Company::query()
            ->with(['moduleTypes', 'scheduleModules'])
            ->where('cnpj', '90.000.000/0001-03')
            ->firstOrFail();

        $autoModule = CompanyModuleType::query()
            ->where('slug', 'homologacao_financeiro')
            ->firstOrFail();

        expect($autoModule->rat_module_id)->not->toBeNull()
            ->and($autoCompany->moduleTypes->pluck('slug')->all())->toContain('homologacao_financeiro')
            ->and($autoCompany->scheduleModules->pluck('name')->all())->toContain('Financeiro')
            ->and($manualCompany->moduleTypes->pluck('slug')->all())->toContain('aplicativo')
            ->and($manualCompany->scheduleModules->pluck('name')->all())->toContain('Cadastro', 'Estoque')
            ->and($mixedCompany->moduleTypes->pluck('slug')->all())->toContain('ecommerce', 'homologacao_cadastro')
            ->and($mixedCompany->scheduleModules->pluck('name')->all())->toContain('Vendas RC', 'Cadastro', 'Financeiro');
    });

    it('eh idempotente e nao duplica empresas de homologacao', function () {
        $this->seed(TicketRatHomologationSeeder::class);
        $this->seed(TicketRatHomologationSeeder::class);

        expect(Company::query()->whereIn('cnpj', [
            '90.000.000/0001-01',
            '90.000.000/0001-02',
            '90.000.000/0001-03',
        ])->count())->toBe(3);
    });
});
